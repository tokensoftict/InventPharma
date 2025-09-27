<?php

namespace App\Listeners;

use App\Events\UpdateOnlineOrderStatusEvent;
use App\Models\Invoice;
use App\Services\Online\ProcessOrderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class PaymentConfirmOnlineOrder
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\UpdateOnlineOrderStatusEvent  $event
     * @return void
     */
    public function handle(UpdateOnlineOrderStatusEvent $event)
    {
        if(config('app.sync_with_online')== 0)  return;

        if($event->invoice->online_order_status == "1") {
            if($event->invoice->status_id == status('Paid')) {
                if ($event->invoice->onliner_order_id !== "") {
                    ProcessOrderService::sendBackPaymentConfirmedMessage($event->invoice->onliner_order_id);
                    $in = Invoice::find($event->invoice->id);
                    $in->online_order_debit = 0;
                    $in->update();
                }
            }
        }
    }
}
