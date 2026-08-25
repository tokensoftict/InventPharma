<?php

namespace App\Http\Controllers\Printing;

use App\Classes\Settings;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\Printing\EscPos\EscPosReceiptGenerator;
use App\Services\Printing\ReceiptDataAssembler;

/**
 * Controller for ESC/POS thermal receipt generation.
 * Returns raw binary ESC/POS data as application/octet-stream.
 */
class EscPosPrintController extends Controller
{
    private Settings $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Generate and return ESC/POS receipt data.
     */
    public function print(Invoice $invoice)
    {
        // Same retail printing restrictions as existing PDF thermal print
        if ($invoice->in_department == 'retail' && $invoice->retail_printed) {
            if (!userCanView('invoiceandsales.rePrintInvoice')) {
                return response()->json(['error' => 'You can only print completed invoice once'], 403);
            } else {
                logActivity($invoice->id, $invoice->invoice_number, 'Retail Invoice was Re-print (ESC/POS)');
            }
        }

        if ($invoice->in_department === 'retail' && ($invoice->status_id == status('Paid') || $invoice->status_id == status('Complete')) && $invoice->retail_printed === false) {
            $invoice->retail_printed = '1';
            $invoice->update();
        }

        // Log activity (same as existing)
        logActivity($invoice->id, $invoice->invoice_number, 'Print Invoice ESC/POS Thermal Status:' . $invoice->status->name);
        logInvoicePrint(Settings::$printType['escpos'] ?? Settings::$printType['thermal'], $invoice);

        // Assemble receipt data
        $assembler = new ReceiptDataAssembler($this->settings);
        $receiptData = $assembler->assemble($invoice);

        // Generate ESC/POS binary
        $generator = new EscPosReceiptGenerator();
        $escPosBytes = $generator->generate($receiptData);

        // Return as binary stream
        return response($escPosBytes, 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="receipt-' . $invoice->invoice_number . '.bin"',
            'Content-Length' => strlen($escPosBytes),
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }
}
