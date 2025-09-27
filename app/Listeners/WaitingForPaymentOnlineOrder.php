<?php

namespace App\Listeners;

use App\Events\UpdateOnlineOrderStatusEvent;
use App\Services\Online\ProcessOrderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class WaitingForPaymentOnlineOrder
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
            ProcessOrderService::sendBackWaitingForPaymentMessage($event->invoice->invoice->onliner_order_id);
            $event->invoice->online_order_debit = 1;
            $event->invoice->update();
        }
    }
}
