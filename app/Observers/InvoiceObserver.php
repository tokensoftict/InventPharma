<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Services\LoyaltyService;
use App\Services\MemberGroupService;

class InvoiceObserver
{

    /**
     * Handle the Invoice "updated" event.
     */
    public function updated(Invoice $invoice): void
    {
        if (empty($invoice->customer_id) || $invoice->customer_id === 1 || $invoice->isStockExchange() || !$invoice->customer) return;

        if (in_array($invoice->status_id, [status("Paid"), status("Complete"), status("Dispatched")])) {
            if($invoice->scan_user_id !== NULL) {
                $loyaltyPointService = app(LoyaltyService::class);

                $loyaltyPointService->earnPoints(
                    $invoice->customer,
                    $invoice,
                );


                // Recalculate Member Groups
                if ($invoice->customer) {
                    $memberGroupService = app(MemberGroupService::class);
                    $memberGroupService->recalculateForCustomer($invoice->customer);
                }


                // Kafka Sync
                dispatch(new \App\Jobs\PushDataServer([
                    'KAFKA_ACTION' => \App\Enums\KafkaAction::SYNC_LOCAL_ORDER,
                    'KAFKA_TOPICS' => \App\Enums\KafkaTopics::ORDERS,
                    'data' => $invoice->getSyncData()
                ]));

            }
        }


        if ($invoice->status_id == status("Deleted")) {
            $loyaltyPointService = app(LoyaltyService::class);

             $loyaltyPointService->deletePoint($invoice->customer, $invoice);
        }
    }


    public function deleted(Invoice $invoice): void
    {
        if (empty($invoice->customer_id) || $invoice->customer_id === 1 || $invoice->isStockExchange() || !$invoice->customer) return;

         $loyaltyPointService = app(LoyaltyService::class);
         $loyaltyPointService->deletePoint($invoice->customer, $invoice);
    }


}
