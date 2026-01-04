<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Services\LoyaltyService;

class InvoiceObserver
{

    /**
     * Handle the Invoice "updated" event.
     */
    public function updated(Invoice $invoice): void
    {
        if(in_array($invoice->status_id, [status("Paid"),status("Complete"), status("Dispatched")])) {

            $loyaltyPointService = app(LoyaltyService::class);

            $loyaltyPointService->earnPoints(
                $invoice->customer,
                $invoice,
            );
        }


        if($invoice->status_id == status("Deleted")) {
            $loyaltyPointService = app(LoyaltyService::class);

            $loyaltyPointService->deletePoint($invoice->customer, $invoice);
        }
    }


    public function deleted(Invoice $invoice): void
    {
        $loyaltyPointService = app(LoyaltyService::class);
        $loyaltyPointService->deletePoint($invoice->customer, $invoice);
    }


}
