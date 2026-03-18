<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Services\Online\ProcessOrderService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use function Laravel\Prompts\text;

class FixInvalidOrderForCustomer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-invalid-order-for-customer';

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


        $startDay = "2026-03-17";
        $endDay = "2022-03-18";

        $in =Invoice::query()->whereBetween("invoice_date", [$startDay, $endDay])
            ->where("customer_id", 2151)->whereNotNull("onliner_order_id")
            ->where('status_id', status('Draft'));

        $this->info("found total invoice ". $in->count());

        $in->chunk(100, function ($invoices) {
                foreach ($invoices as $invoice) {
                    $this->info("processing invoice #" . $invoice->id);
                   DB::transaction(function () use ($invoice) {
                       $order = Http::get("https://pa.psgdc.store/?order_id=$invoice->onliner_order_id")->json();
                       $invoice->invoice_date = (new Carbon($order['order_date']))->format('Y-m-d');
                       if($order->customer_type == "App\\Models\\SupermarketUser") {
                           $invoice->status_id = status('Complete');
                       } else {
                           $invoice->status_id = status('Dispatched');
                       }
                       $invoice->save();

                       ProcessOrderService::sendBackOrderDispatchedMessage($order['id'], $order['local_order_id'], $invoice->carton_no);
                   });
                }
            });
    }
}
