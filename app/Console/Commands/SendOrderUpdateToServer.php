<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Services\Online\ProcessOrderService;
use Illuminate\Console\Command;

class SendOrderUpdateToServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-order-update-to-server';

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
        $startDay = "2026-05-23";
        $endDay = "2026-05-29";

        $in =Invoice::query()->whereBetween("invoice_date", [$startDay, $endDay])
            ->where('created_by', 1)
            ->where('in_department', 'bulksales')
            ->whereNotNull("onliner_order_id");

        $this->info("found total invoice ". $in->count());

        $in->chunk(100, function ($invoices) {
            foreach ($invoices as $invoice) {
                $this->info("processing invoice #" . $invoice->id);
                if($invoice->status_id == status('Draft')){
                    ProcessOrderService::sendBackSuccessMessage($invoice->onliner_order_id, NULL);
                    $this->info("processing Draft invoice #" . $invoice->id);
                } else if($invoice->status_id == status('Deleted')){
                    $this->info("processing Canceled invoice #" . $invoice->id);
                    ProcessOrderService::sendBackCancelOrderMessage($invoice->onliner_order_id, NULL);
                } else if($invoice->status_id == status('Packed-Waiting-For-Payment')){
                    $this->info("processing Waiting-For-Payment invoice #" . $invoice->id);
                    ProcessOrderService::sendBackWaitingForPaymentMessage($invoice->onliner_order_id, NULL);
                } else if($invoice->status_id == status('Dispatched')){
                    $this->info("processing Dispatched invoice #" . $invoice->id);
                    ProcessOrderService::sendBackOrderDispatchedMessage($invoice->onliner_order_id, NULL, $invoice->carton_no);
                }else if($invoice->status_id == status('Complete')){
                    $this->info("processing Dispatched invoice #" . $invoice->id);
                    ProcessOrderService::sendBackOrderDispatchedMessage($invoice->onliner_order_id, NULL, $invoice->carton_no);
                }
                else {
                    $this->info("current status invoice id " . $invoice->status->name);
                }
            }
        });
    }
}
