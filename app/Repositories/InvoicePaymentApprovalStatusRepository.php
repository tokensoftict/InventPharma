<?php

namespace App\Repositories;


use App\Enums\InvoicePaymentApprovalTypeStatusEnum;
use App\Models\Invoice;
use App\Models\InvoicePaymentApprovalStatus;
use Illuminate\Support\Facades\DB;

class InvoicePaymentApprovalStatusRepository
{
    public InvoicePaymentApprovalStatus $invoicePaymentApprovalStatus;
    public function __construct(InvoicePaymentApprovalStatus $invoicePaymentApprovalStatus)
    {
        $this->invoicePaymentApprovalStatus = $invoicePaymentApprovalStatus;
    }

    public static function create($data)
    {
        $data['date_added'] = todaysDate();
        $data['user_id'] = auth()->id();
        InvoicePaymentApprovalStatus::create(
          $data
        );
    }


    public function getPendingRequest($invoice_id, $type) : NULL | InvoicePaymentApprovalStatus
    {
       return  $this->invoicePaymentApprovalStatus::where('invoice_id', $invoice_id)
           ->where('approval_status', InvoicePaymentApprovalTypeStatusEnum::Pending)
           ->where('type', $type)
           ->first();
    }

    public function getInvoicePendingRequest($invoice_id)
    {
        return $this->invoicePaymentApprovalStatus::where('invoice_id', $invoice_id)
            ->where('approval_status', InvoicePaymentApprovalTypeStatusEnum::Pending)
            ->get();
    }


    public function cancelAllRequest($invoice_id)
    {
        DB::transaction(function() use ($invoice_id){
            $invoice = Invoice::find($invoice_id);
            $invoice->payment_id = NULL;
            $invoice->update();
            $payments = $this->getInvoicePendingRequest($invoice_id);
            foreach ($payments as $payment){
                if($payment->payment) {
                    $payment->payment->delete();
                }
            }
            $this->invoicePaymentApprovalStatus::where('invoice_id', $invoice_id)->update([
                'payment_id' => NULL,
                'approval_status' => InvoicePaymentApprovalTypeStatusEnum::Declined,
                'paymentmethoditem_id' => NULL
            ]);
        });
    }

}
