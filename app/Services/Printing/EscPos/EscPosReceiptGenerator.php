<?php

namespace App\Services\Printing\EscPos;

use App\Services\Printing\ReceiptData;

/**
 * Generates complete ESC/POS receipt binary from ReceiptData.
 * Reproduces the exact layout and content of the existing thermal PDF receipt (pos.blade.php).
 */
class EscPosReceiptGenerator
{
    private EscPosBuilder $builder;
    private int $paperWidth;
    private string $encoding;

    public function __construct(?array $config = null)
    {
        $this->paperWidth = $config['paper_width'] ?? config('printing.escpos.paper_width', 80);
        $this->encoding = $config['encoding'] ?? config('printing.escpos.encoding', 'UTF-8');
        $this->builder = new EscPosBuilder($this->paperWidth, $this->encoding);
    }

    /**
     * Generate complete ESC/POS binary for a receipt.
     */
    public function generate(ReceiptData $receiptData): string
    {
        $this->builder->reset();
        $this->builder->initialize();

        // Main receipt page
        $this->renderMainReceipt($receiptData);

        // Picker pages (if applicable)
        if ($receiptData->picker !== null) {
            $this->builder->cut();
            $this->renderPickerPage($receiptData);
        }

        if ($receiptData->onlinePicker !== null) {
            foreach ($receiptData->onlinePicker['pages'] as $page) {
                $this->builder->cut();
                $this->renderOnlinePickerPage($receiptData, $page);
            }
        }

        // Final cut
        $this->builder->feed(3)->cut();

        return $this->builder->getOutput();
    }

    /**
     * Render the main receipt page.
     * Mirrors pos.blade.php layout exactly.
     */
    private function renderMainReceipt(ReceiptData $data): void
    {
        $company = $data->company;
        $transaction = $data->transaction;
        $customer = $data->customer;

        // ---- LOGO ----
        $this->renderLogo($company);

        // ---- RC NUMBER (below logo if logo exists, else top) ----
        if (!empty($company['rc_number'])) {
            $this->builder->setAlignment(EscPosBuilder::ALIGN_CENTER);
            $this->builder->setFont(1); // Small font
            $this->builder->textLine('RC: ' . $company['rc_number']);
            $this->builder->setFont(0); // Back to normal
        }

        // ---- COMPANY NAME ----
        $this->builder->setAlignment(EscPosBuilder::ALIGN_CENTER);
        $this->builder->setBold(true);
        $this->builder->setDoubleHeight(true);
        $this->builder->textLine($company['name']);
        $this->builder->setDoubleHeight(false);
        $this->builder->setBold(false);

        // ---- ADDRESS ----
        $this->builder->setAlignment(EscPosBuilder::ALIGN_CENTER);
        $this->builder->textLine($company['first_address']);
        if (!empty($company['second_address'])) {
            $this->builder->textLine($company['second_address']);
        }
        if (!empty($company['contact_number'])) {
            $this->builder->textLine($company['contact_number']);
        }

        $this->builder->newLine();

        // ---- TRANSACTION INFO ----
        $this->builder->setAlignment(EscPosBuilder::ALIGN_LEFT);
        $this->renderLabelValue('Reference Number', $transaction['invoice_number']);
        $this->renderLabelValue('Invoice Date', $transaction['invoice_date'] . ' ' . $transaction['sales_time']);
        $this->renderLabelValue('Customer', $customer['fullname']);
        $this->renderLabelValue('Sales Rep.', $transaction['sales_rep']);
        $this->renderLabelValue('Invoice Status', $transaction['status']);

        // ---- SEPARATOR ----
        $this->builder->dashedLine();

        // ---- PRODUCT TABLE HEADER ----
        $cols = $this->getProductColumnWidths();
        $this->builder->horizontalBorderLine($cols);
        $this->renderProductHeader();
        $this->builder->horizontalBorderLine($cols);

        // ---- PRODUCT ROWS ----
        foreach ($data->items as $item) {
            $this->renderProductRow($item);
            $this->builder->horizontalBorderLine($cols);
        }

        // ---- TOTALS ----
        $this->builder->dashedLine();

        if ($data->onlineOrderTotals !== null) {
            foreach ($data->onlineOrderTotals as $orderTotal) {
                $this->builder->twoColumnLine($orderTotal['name'], number_format($orderTotal['value'], 2));
            }
        } else {
            $this->builder->twoColumnLine('Sub Total', number_format($data->totals['sub_total'], 2));
        }

        $this->builder->twoColumnLine('Discount', '-' . number_format($data->totals['discount_amount'], 2));

        if ($data->totals['has_membership_discount']) {
            $this->builder->twoColumnLine('Membership Discount', '-' . number_format($data->totals['membership_discount'], 2));
        }

        $this->builder->setBold(true);
        $this->builder->twoColumnLine('Total', number_format($data->totals['total'], 2));
        $this->builder->setBold(false);

        // ---- SEPARATOR ----
        $this->builder->dashedLine();

        // ---- FOOTER ----
        if (!empty($company['footer_notes'])) {
            $this->builder->setAlignment(EscPosBuilder::ALIGN_CENTER);
            $this->builder->textLine($company['footer_notes']);
        }

        // ---- GENERATED TIMESTAMP ----
        $this->builder->setAlignment(EscPosBuilder::ALIGN_CENTER);
        $this->builder->textLine('Generated @ ' . $data->meta['generated_at']);

        $this->builder->newLine();

        // ---- BARCODE ----
        $this->renderBarcode($data->barcode);

        $this->builder->newLine();

        // ---- DEVELOPER CREDIT ----
        $this->builder->setAlignment(EscPosBuilder::ALIGN_CENTER);
        $this->builder->textLine($data->meta['developer_credit']);
    }

    /**
     * Render the picker page (non-online orders).
     * Mirrors pos_picker.blade.php.
     */
    private function renderPickerPage(ReceiptData $data): void
    {
        $company = $data->company;
        $transaction = $data->transaction;
        $picker = $data->picker;

        $this->builder->initialize();

        // ---- LOGO + HEADER (same as main) ----
        $this->renderLogo($company);

        if (!empty($company['rc_number'])) {
            $this->builder->setAlignment(EscPosBuilder::ALIGN_CENTER);
            $this->builder->setFont(1);
            $this->builder->textLine('RC: ' . $company['rc_number']);
            $this->builder->setFont(0);
        }

        $this->builder->setAlignment(EscPosBuilder::ALIGN_CENTER);
        $this->builder->setBold(true);
        $this->builder->setDoubleHeight(true);
        $this->builder->textLine($company['name']);
        $this->builder->setDoubleHeight(false);
        $this->builder->setBold(false);

        $this->builder->setAlignment(EscPosBuilder::ALIGN_CENTER);
        $this->builder->textLine($company['first_address']);
        if (!empty($company['second_address'])) {
            $this->builder->textLine($company['second_address']);
        }
        if (!empty($company['contact_number'])) {
            $this->builder->textLine($company['contact_number']);
        }
        $this->builder->newLine();

        // Transaction info
        $this->builder->setAlignment(EscPosBuilder::ALIGN_LEFT);
        $this->renderLabelValue('Reference Number', $transaction['invoice_number']);
        $this->renderLabelValue('Invoice Date', $transaction['invoice_date'] . ' ' . $transaction['sales_time']);
        $this->renderLabelValue('Customer', $data->customer['fullname']);
        $this->renderLabelValue('Sales Rep.', $transaction['sales_rep']);
        $this->renderLabelValue('Invoice Status', $transaction['status']);

        $this->builder->dashedLine();

        // Picker columns: #, Name, Location, Dept, Qty
        $cols = $this->getPickerColumnWidths();
        $this->builder->multiColumnLine([
            ['text' => '#', 'width' => $cols[0]],
            ['text' => 'Name', 'width' => $cols[1]],
            ['text' => 'Loc', 'width' => $cols[2], 'align' => 'center'],
            ['text' => 'Dept', 'width' => $cols[3]],
            ['text' => 'Qty', 'width' => $cols[4], 'align' => 'right'],
        ]);
        $this->builder->dashedLine();

        foreach ($picker['items'] as $item) {
            $this->builder->multiColumnLine([
                ['text' => (string) $item['number'], 'width' => $cols[0]],
                ['text' => mb_substr($item['name'], 0, $cols[1]), 'width' => $cols[1]],
                ['text' => mb_substr($item['location'] ?? '', 0, $cols[2]), 'width' => $cols[2], 'align' => 'center'],
                ['text' => mb_substr($item['department_label'], 0, $cols[3]), 'width' => $cols[3]],
                ['text' => (string) $item['quantity'], 'width' => $cols[4], 'align' => 'right'],
            ]);

            // Product options
            if (!empty($item['options'])) {
                $optionText = $this->formatOptions($item['options']);
                $this->builder->setFont(1);
                $this->builder->textLine('  ' . $optionText);
                $this->builder->setFont(0);
            }
        }

        $this->builder->dashedLine();

        // Footer
        if (!empty($company['footer_notes'])) {
            $this->builder->setAlignment(EscPosBuilder::ALIGN_CENTER);
            $this->builder->textLine($company['footer_notes']);
        }

        // Barcode (uses invoice_number for picker)
        $this->builder->newLine();
        $this->renderBarcode([
            'type' => 'C39',
            'value' => $picker['barcode_value'],
        ]);

        $this->builder->newLine();
        $this->builder->setAlignment(EscPosBuilder::ALIGN_CENTER);
        $this->builder->textLine('Generated @ ' . $data->meta['generated_at']);
    }

    /**
     * Render online picker page for a specific department.
     */
    private function renderOnlinePickerPage(ReceiptData $data, array $page): void
    {
        $company = $data->company;
        $transaction = $data->transaction;

        $this->builder->initialize();

        // Same header as picker
        $this->renderLogo($company);

        if (!empty($company['rc_number'])) {
            $this->builder->setAlignment(EscPosBuilder::ALIGN_CENTER);
            $this->builder->setFont(1);
            $this->builder->textLine('RC: ' . $company['rc_number']);
            $this->builder->setFont(0);
        }

        $this->builder->setAlignment(EscPosBuilder::ALIGN_CENTER);
        $this->builder->setBold(true);
        $this->builder->setDoubleHeight(true);
        $this->builder->textLine($company['name']);
        $this->builder->setDoubleHeight(false);
        $this->builder->setBold(false);

        $this->builder->setAlignment(EscPosBuilder::ALIGN_CENTER);
        $this->builder->textLine($company['first_address']);
        if (!empty($company['second_address'])) {
            $this->builder->textLine($company['second_address']);
        }
        if (!empty($company['contact_number'])) {
            $this->builder->textLine($company['contact_number']);
        }
        $this->builder->newLine();

        $this->builder->setAlignment(EscPosBuilder::ALIGN_LEFT);
        $this->renderLabelValue('Reference Number', $transaction['invoice_number']);
        $this->renderLabelValue('Invoice Date', $transaction['invoice_date'] . ' ' . $transaction['sales_time']);
        $this->renderLabelValue('Customer', $data->customer['fullname']);
        $this->renderLabelValue('Sales Rep.', $transaction['sales_rep']);
        $this->renderLabelValue('Invoice Status', $transaction['status']);

        $this->builder->dashedLine();

        $cols = $this->getPickerColumnWidths();
        $this->builder->multiColumnLine([
            ['text' => '#', 'width' => $cols[0]],
            ['text' => 'Name', 'width' => $cols[1]],
            ['text' => 'Loc', 'width' => $cols[2], 'align' => 'center'],
            ['text' => 'Dept', 'width' => $cols[3]],
            ['text' => 'Qty', 'width' => $cols[4], 'align' => 'right'],
        ]);
        $this->builder->dashedLine();

        foreach ($page['items'] as $item) {
            $this->builder->multiColumnLine([
                ['text' => (string) $item['number'], 'width' => $cols[0]],
                ['text' => mb_substr($item['name'], 0, $cols[1]), 'width' => $cols[1]],
                ['text' => mb_substr($item['location'] ?? '', 0, $cols[2]), 'width' => $cols[2], 'align' => 'center'],
                ['text' => mb_substr($item['department_label'], 0, $cols[3]), 'width' => $cols[3]],
                ['text' => (string) $item['quantity'], 'width' => $cols[4], 'align' => 'right'],
            ]);

            if (!empty($item['options'])) {
                $optionText = $this->formatOptions($item['options']);
                $this->builder->setFont(1);
                $this->builder->textLine('  ' . $optionText);
                $this->builder->setFont(0);
            }
        }

        $this->builder->dashedLine();

        if (!empty($company['footer_notes'])) {
            $this->builder->setAlignment(EscPosBuilder::ALIGN_CENTER);
            $this->builder->textLine($company['footer_notes']);
        }

        $this->builder->newLine();
        $this->renderBarcode([
            'type' => 'C39',
            'value' => $data->onlinePicker['barcode_value'],
        ]);

        $this->builder->newLine();
        $this->builder->setAlignment(EscPosBuilder::ALIGN_CENTER);
        $this->builder->textLine('Generated @ ' . $data->meta['generated_at']);
    }

    // ------- HELPER METHODS -------

    /**
     * Render company logo as a raster bitmap image.
     */
    private function renderLogo(array $company): void
    {
        if (!$company['has_logo'] || empty($company['logo_path'])) {
            return;
        }

        try {
            $maxWidth = $this->builder->getMaxDots();
            // Scale logo to half the width for a reasonable size (similar to max-height:30px in CSS)
            $logoMaxWidth = (int) ($maxWidth * 0.5);
            $imageData = ImageConverter::convert($company['logo_path'], $logoMaxWidth);

            $this->builder->setAlignment(EscPosBuilder::ALIGN_CENTER);
            $this->builder->newLine();
            $this->builder->printRasterImage($imageData);
            $this->builder->newLine();
        } catch (\Exception $e) {
            // If logo fails to convert, skip silently and continue with text
            \Log::warning('ESC/POS logo conversion failed: ' . $e->getMessage());
        }
    }

    /**
     * Render a barcode using native ESC/POS commands.
     * Code 39 is used to match the existing PDF receipt.
     */
    private function renderBarcode(array $barcode): void
    {
        $value = $barcode['value'] ?? '';
        if (empty($value)) {
            return;
        }

        // Code 39 only supports: 0-9, A-Z, space, and special chars: - . $ / + %
        // The invoice ID is numeric so this is safe.
        // For invoice_number (which may contain prefixes), sanitize:
        $sanitized = preg_replace('/[^0-9A-Z \-\.\/\+\%\$]/i', '', strtoupper($value));

        if (empty($sanitized)) {
            return;
        }

        $this->builder->setAlignment(EscPosBuilder::ALIGN_CENTER);

        try {
            $this->builder->printBarcode($sanitized, EscPosBuilder::BARCODE_CODE39, 80);
        } catch (\Exception $e) {
            // If native barcode fails, print as text
            $this->builder->textLine('[' . $value . ']');
        }
    }

    /**
     * Render label:value pair, left-aligned.
     */
    private function renderLabelValue(string $label, string $value): void
    {
        $maxLabelWidth = 18; // "Reference Number" is 16 chars
        $paddedLabel = str_pad($label, $maxLabelWidth);
        $this->builder->textLine($paddedLabel . $value);
    }

    /**
     * Render product table header.
     * Columns: #, Name, Qty, Rate, Dis.Rate, Total
     */
    private function renderProductHeader(): void
    {
        $cols = $this->getProductColumnWidths();
        $this->builder->setBold(true);
        $this->builder->borderedMultiColumnLine([
            ['text' => '#', 'width' => $cols[0]],
            ['text' => 'Name', 'width' => $cols[1]],
            ['text' => 'Qty', 'width' => $cols[2], 'align' => 'center'],
            ['text' => 'Rate', 'width' => $cols[3], 'align' => 'right'],
            ['text' => 'Dis.', 'width' => $cols[4], 'align' => 'right'],
            ['text' => 'Total', 'width' => $cols[5], 'align' => 'right'],
        ]);
        $this->builder->setBold(false);
    }

    /**
     * Render a single product row.
     */
    private function renderProductRow(array $item): void
    {
        $cols = $this->getProductColumnWidths();
        $name = $item['name'];

        // Word-wrap the name into lines that fit the Name column width
        $nameLines = $this->wrapText($name, $cols[1]);

        // First line: full row with all columns
        $this->builder->borderedMultiColumnLine([
            ['text' => (string) $item['number'], 'width' => $cols[0]],
            ['text' => $nameLines[0], 'width' => $cols[1]],
            ['text' => (string) $item['quantity'], 'width' => $cols[2], 'align' => 'center'],
            ['text' => $this->formatMoney($item['selling_price']), 'width' => $cols[3], 'align' => 'right'],
            ['text' => $this->formatMoney($item['discounted_price']), 'width' => $cols[4], 'align' => 'right'],
            ['text' => $this->formatMoney($item['line_total']), 'width' => $cols[5], 'align' => 'right'],
        ]);

        // Continuation lines for long product names (same normal font, inside borders)
        for ($i = 1; $i < count($nameLines); $i++) {
            $this->builder->borderedMultiColumnLine([
                ['text' => '', 'width' => $cols[0]],
                ['text' => $nameLines[$i], 'width' => $cols[1]],
                ['text' => '', 'width' => $cols[2]],
                ['text' => '', 'width' => $cols[3]],
                ['text' => '', 'width' => $cols[4]],
                ['text' => '', 'width' => $cols[5]],
            ]);
        }

        // Product options
        if (!empty($item['options'])) {
            $optionText = $this->formatOptions($item['options']);
            $this->builder->borderedMultiColumnLine([
                ['text' => '', 'width' => $cols[0]],
                ['text' => mb_substr($optionText, 0, $cols[1] + $cols[2] + $cols[3] + $cols[4] + $cols[5] + 4), 'width' => $cols[1] + $cols[2] + $cols[3] + $cols[4] + $cols[5] + 4],
            ]);
        }
    }

    /**
     * Format product options as a single text line.
     * Example: "Color: Red (+₦500) | Size: Large"
     */
    private function formatOptions(array $options): string
    {
        $parts = [];
        foreach ($options as $opt) {
            $text = ($opt['name'] ?? '') . ': ' . ($opt['value'] ?? '');
            $amount = (int) ($opt['amount'] ?? 0);
            if ($amount !== 0) {
                $sign = $opt['sign'] ?? '+';
                $text .= ' (' . $sign . $this->currencySymbol() . number_format((float) $amount) . ')';
            }
            $parts[] = $text;
        }
        return implode(' | ', $parts);
    }

    /**
     * Format money value.
     */
    private function formatMoney($amount): string
    {
        return number_format((float) $amount, 2);
    }

    /**
     * Currency symbol based on encoding capability.
     */
    private function currencySymbol(): string
    {
        if ($this->encoding === 'UTF-8') {
            return '₦';
        }
        return 'NGN';
    }

    /**
     * Word-wrap text to fit within a given character width.
     * Returns an array of lines.
     */
    private function wrapText(string $text, int $width): array
    {
        if (mb_strlen($text) <= $width) {
            return [$text];
        }

        $lines = [];
        $remaining = $text;

        while (mb_strlen($remaining) > 0) {
            if (mb_strlen($remaining) <= $width) {
                $lines[] = $remaining;
                break;
            }

            // Try to break at a space
            $chunk = mb_substr($remaining, 0, $width);
            $lastSpace = mb_strrpos($chunk, ' ');

            if ($lastSpace !== false && $lastSpace > (int)($width * 0.3)) {
                // Break at the last space
                $lines[] = mb_substr($remaining, 0, $lastSpace);
                $remaining = ltrim(mb_substr($remaining, $lastSpace));
            } else {
                // No good space, hard break
                $lines[] = $chunk;
                $remaining = mb_substr($remaining, $width);
            }
        }

        return $lines;
    }

    /**
     * Get product column widths based on paper width.
     * Total must equal charsPerLine.
     */
    private function getProductColumnWidths(): array
    {
        if ($this->builder->getCharsPerLine() >= 48) {
            // 80mm: 7 borders (|) + content = 48 chars
            // #(2) Name(14) Qty(3) Rate(8) Dis(7) Total(7) = 41 + 7 = 48
            return [2, 14, 3, 8, 7, 7];
        }
        // 58mm: 7 borders (|) + content = 32 chars
        // #(2) Name(8) Qty(3) Rate(5) Dis(4) Total(3) = 25 + 7 = 32
        return [2, 8, 3, 5, 4, 3];
    }

    /**
     * Get picker column widths.
     * Columns: #, Name, Location, Dept, Qty
     */
    private function getPickerColumnWidths(): array
    {
        if ($this->builder->getCharsPerLine() >= 48) {
            // 80mm: #(3) Name(18) Loc(8) Dept(12) Qty(7) = 48
            return [3, 18, 8, 12, 7];
        }
        // 58mm: #(2) Name(12) Loc(5) Dept(8) Qty(5) = 32
        return [2, 12, 5, 8, 5];
    }
}
