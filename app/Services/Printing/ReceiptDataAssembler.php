<?php

namespace App\Services\Printing;

use App\Classes\Settings;
use App\Models\Invoice;

/**
 * Extracts all receipt data from an Invoice and Store settings into a ReceiptData object.
 * This mirrors exactly what pos.blade.php currently renders — no independent calculations.
 */
class ReceiptDataAssembler
{
    private Settings $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Assemble receipt data from an invoice.
     */
    public function assemble(Invoice $invoice): ReceiptData
    {
        $store = $this->settings->store();

        $data = ReceiptData::fromArray([
            'company' => $this->buildCompany($store),
            'transaction' => $this->buildTransaction($invoice),
            'customer' => $this->buildCustomer($invoice),
            'items' => $this->buildItems($invoice),
            'totals' => $this->buildTotals($invoice),
            'onlineOrderTotals' => $this->buildOnlineOrderTotals($invoice),
            'barcode' => $this->buildBarcode($invoice),
            'meta' => $this->buildMeta(),
            'picker' => $this->buildPicker($invoice, $store),
            'onlinePicker' => $this->buildOnlinePicker($invoice, $store),
        ]);

        return $data;
    }

    private function buildCompany($store): array
    {
        $logoPath = null;
        if (!empty($store->logo) && $store->logo !== '1659902910.png') {
            $logoPath = public_path('logo/' . $store->logo);
        }

        return [
            'name' => $store->name ?? '',
            'first_address' => $store->first_address ?? '',
            'second_address' => $store->second_address ?? '',
            'contact_number' => $store->contact_number ?? '',
            'rc_number' => $store->rc_number ?? '',
            'footer_notes' => $store->footer_notes ?? '',
            'logo_path' => $logoPath,
            'has_logo' => $logoPath !== null && file_exists($logoPath),
        ];
    }

    private function buildTransaction(Invoice $invoice): array
    {
        return [
            'invoice_number' => $invoice->invoice_number,
            'invoice_date' => convert_date($invoice->invoice_date),
            'sales_time' => twelveHourClock($invoice->sales_time),
            'status' => $invoice->status->name ?? '',
            'status_id' => $invoice->status_id,
            'sales_rep' => $invoice->last_updated->name ?? '',
            'in_department' => $invoice->in_department,
            'department' => $invoice->department,
            'department_label' => Settings::$department[$invoice->in_department] ?? '',
            'pickup_department_label' => Settings::$department[$invoice->department] ?? '',
            'is_retail' => $invoice->in_department === 'retail',
            'is_online_order' => $invoice->online_order_status == '1',
            'invoice_id' => $invoice->id,
        ];
    }

    private function buildCustomer(Invoice $invoice): array
    {
        return [
            'firstname' => $invoice->customer->firstname ?? '',
            'lastname' => $invoice->customer->lastname ?? '',
            'fullname' => trim(($invoice->customer->firstname ?? '') . ' ' . ($invoice->customer->lastname ?? '')),
        ];
    }

    private function buildItems(Invoice $invoice): array
    {
        $items = [];
        $iteration = 0;

        foreach ($invoice->invoiceitems as $item) {
            $iteration++;
            $options = [];

            if (!empty($item->selectedOptions) && count($item->selectedOptions) > 0) {
                foreach ($item->selectedOptions as $option) {
                    $options[] = [
                        'name' => $option['option_name'] ?? $option['name'] ?? '',
                        'value' => $option['selectedValue'] ?? $option['value'] ?? '',
                        'amount' => $option['amount'] ?? 0,
                        'sign' => $option['sign'] ?? '+',
                    ];
                }
            }

            $items[] = [
                'number' => $iteration,
                'name' => $item->stock->name ?? '',
                'options' => $options,
                'quantity' => $item->quantity,
                'selling_price' => $item->selling_price,
                'discount_amount' => $item->discount_amount,
                'discounted_price' => $item->selling_price - $item->discount_amount,
                'line_total' => $item->quantity * ($item->selling_price - $item->discount_amount),
                'stock_id' => $item->stock_id,
                'location' => $item->stock->location ?? '',
                'department' => $item->department ?? '',
                'department_label' => Settings::$department[$item->department] ?? '',
            ];
        }

        return $items;
    }

    private function buildTotals(Invoice $invoice): array
    {
        return [
            'sub_total' => $invoice->sub_total,
            'discount_amount' => $invoice->discount_amount,
            'membership_discount' => $invoice->membership_discount,
            'has_membership_discount' => ($invoice->membership_discount ?? 0) > 0,
            'total' => $invoice->sub_total - ($invoice->discount_amount + ($invoice->membership_discount ?? 0)),
            'vat' => $invoice->vat ?? 0,
            'vat_amount' => $invoice->vat_amount ?? 0,
        ];
    }

    private function buildOnlineOrderTotals(Invoice $invoice): ?array
    {
        if (!$invoice->onlineordertotals()->exists()) {
            return null;
        }

        $totals = [];
        foreach ($invoice->onlineordertotals as $orderTotal) {
            $totals[] = [
                'name' => str_replace('Subtotal', 'Sub total', $orderTotal['name']),
                'value' => $orderTotal->value,
            ];
        }

        return $totals;
    }

    private function buildBarcode(Invoice $invoice): array
    {
        return [
            'type' => 'C39',
            'value' => (string) $invoice->id,
            'invoice_number_value' => (string) $invoice->invoice_number,
        ];
    }

    private function buildMeta(): array
    {
        return [
            'generated_at' => date('Y-m-d H:i A'),
            'developer_credit' => 'Develop By Tokensoft ICT - 08130610626',
        ];
    }

    /**
     * Build picker page data for non-retail, non-online orders.
     */
    private function buildPicker(Invoice $invoice, $store): ?array
    {
        // Only non-retail, non-complete invoices get picker pages
        if ($invoice->in_department === 'retail') {
            return null;
        }
        if ($invoice->status_id == status('Complete')) {
            return null;
        }
        if ($invoice->online_order_status == '1') {
            return null; // Online orders use onlinePicker
        }

        $pickerItems = [];
        $iteration = 0;
        foreach ($invoice->invoiceitems as $item) {
            $iteration++;
            $options = [];
            if (!empty($item->selectedOptions) && count($item->selectedOptions) > 0) {
                foreach ($item->selectedOptions as $option) {
                    $options[] = [
                        'name' => $option['option_name'] ?? $option['name'] ?? '',
                        'value' => $option['selectedValue'] ?? $option['value'] ?? '',
                        'amount' => $option['amount'] ?? 0,
                        'sign' => $option['sign'] ?? '+',
                    ];
                }
            }
            $pickerItems[] = [
                'number' => $iteration,
                'name' => $item->stock->name ?? '',
                'options' => $options,
                'location' => $item->stock->location ?? '',
                'department_label' => Settings::$department[$item->department] ?? '',
                'quantity' => $item->quantity,
            ];
        }

        return [
            'items' => $pickerItems,
            'barcode_value' => (string) $invoice->invoice_number,
        ];
    }

    /**
     * Build online picker pages for online orders (per department).
     */
    private function buildOnlinePicker(Invoice $invoice, $store): ?array
    {
        if ($invoice->in_department === 'retail') {
            return null;
        }
        if ($invoice->status_id == status('Complete')) {
            return null;
        }
        if ($invoice->online_order_status != '1') {
            return null;
        }

        $departments = ['bulksales', 'quantity', 'wholesales'];
        $pages = [];

        foreach ($departments as $dept) {
            $batchItems = $invoice->invoiceitembatches()
                ->select('stock_id', \DB::raw('SUM(quantity) as total_qty'))
                ->where('department', $dept)
                ->groupBy('stock_id')
                ->get();

            if ($batchItems->count() === 0) {
                continue;
            }

            $pickerItems = [];
            $iteration = 0;
            foreach ($batchItems as $item) {
                $iteration++;
                $invoiceItem = $invoice->invoiceitems()->where('stock_id', $item->stock_id)->first();
                $options = [];
                if ($invoiceItem && !empty($invoiceItem->selectedOptions)) {
                    foreach ($invoiceItem->selectedOptions as $option) {
                        $options[] = [
                            'name' => $option['option_name'] ?? $option['name'] ?? '',
                            'value' => $option['selectedValue'] ?? $option['value'] ?? '',
                            'amount' => $option['amount'] ?? 0,
                            'sign' => $option['sign'] ?? '+',
                        ];
                    }
                }

                $pickerItems[] = [
                    'number' => $iteration,
                    'name' => $item->stock->name ?? '',
                    'options' => $options,
                    'location' => $item->stock->location ?? '',
                    'department_label' => Settings::$department[$dept] ?? '',
                    'quantity' => $item->total_qty,
                ];
            }

            $pages[] = [
                'department' => $dept,
                'department_label' => Settings::$department[$dept] ?? '',
                'items' => $pickerItems,
            ];
        }

        return empty($pages) ? null : [
            'pages' => $pages,
            'barcode_value' => (string) $invoice->invoice_number,
        ];
    }
}
