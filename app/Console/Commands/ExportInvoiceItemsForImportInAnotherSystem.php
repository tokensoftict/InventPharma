<?php

namespace App\Console\Commands;

use App\Exports\Stockexport;
use App\Models\Invoice;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class ExportInvoiceItemsForImportInAnotherSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:export-invoice-items-for-import-in-another-system';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $invoiceNumber = [5972314255,447409294,6560729798,3425754955];
        $data = [];
        $invoices = Invoice::query()->with(['invoiceitems', 'invoiceitems.stock'])->whereIn('invoice_number', $invoiceNumber)->get();
        foreach ($invoices as $invoice) {
            foreach ($invoice->invoiceitems as $item) {
                $data[] = [
                    "ID" => "",
                    'name' => $item->stock->name,
                    'Category' => "",
                    'Manufacturer' => "",
                    'Classification' => "",
                    'Major Classification' => "",
                    'Group' => "",
                    'Retail Price' => $item->stock->retail_price,
                    'Whole Sales Price' => $item->stock->whole_price,
                    'Bulk Sales Price' => $item->stock->bulk_price,
                    'Status' => "1",
                    'Quantity' => $item->quantity,
                    'Last purchase Date' => "",
                    'Box'=> $item->stock->box
                ];
            }
        }

        Excel::store(new Stockexport($data), 'customer_invoice_list' . '.xlsx');
    }
}
