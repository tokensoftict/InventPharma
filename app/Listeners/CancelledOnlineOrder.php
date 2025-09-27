<?php

namespace App\Listeners;

use App\Events\UpdateOnlineOrderStatusEvent;
use App\Services\Online\ProcessOrderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CancelledOnlineOrder
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
            if($event->invoice->status_id == status('Deleted')) {
                ProcessOrderService::sendBackCancelOrderMessage($event->invoice->onliner_order_id);
            }
        }
    }
}
