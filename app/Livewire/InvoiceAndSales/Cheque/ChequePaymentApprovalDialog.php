<?php

namespace App\Livewire\InvoiceAndSales\Cheque;

use App\Enums\InvoicePaymentApprovalTypeEnum;
use App\Enums\InvoicePaymentApprovalTypeStatusEnum;
use App\Models\Creditpaymentlog;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoicePaymentApprovalStatus;
use App\Repositories\InvoicePaymentApprovalStatusRepository;
use App\Repositories\PaymentRepository;
use App\Traits\PaymentComponentTrait;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use App\Traits\LivewireAlert;

class ChequePaymentApprovalDialog extends Component
{
    use LivewireAlert, PaymentComponentTrait;

    public int $customer_id = 0;

    public Customer $customer;
    public $online_credit_invoice = "";

    public string $mode = 'requestApprovalDialog';

    protected $listeners = [
        'generateCreditPayment' => 'generateCreditPayment',
        'generateInvoicePayment' => 'generateInvoicePayment',
    ];
    private InvoicePaymentApprovalStatusRepository $approvalStatusRepository;

    private PaymentRepository $paymentRepository;
    private  $invoicePaymentApprovalStatus;

    public function booted(InvoicePaymentApprovalStatusRepository $approvalStatusRepository, PaymentRepository $paymentRepository)
    {
        $this->approvalStatusRepository = $approvalStatusRepository;
        $this->paymentRepository = $paymentRepository;
        $this->mountProperties();
    }

    public function mount()
    {
        if(isset($this->invoice->customer_id))
        {
            $this->sub_total = $this->invoice->sub_total - $this->invoice->discount_amount;

            $this->totalCredit = Creditpaymentlog::where('customer_id',$this->invoice->customer_id)->sum('amount');

        }
        $this->payment_method = "8";

        $this->bank = "";
        $this->cheque_date = todaysDate();
        $this->comment =  "";

    }

    public function render()
    {
        if($this->mode === "approveDecline")
        {
            $this->invoicePaymentApprovalStatus = $this->approvalStatusRepository->getPendingRequest($this->invoice->id, InvoicePaymentApprovalTypeEnum::Cheque);

            $this->cheque_date = $this->invoicePaymentApprovalStatus->date ?? "";
            $this->comment = $this->invoicePaymentApprovalStatus->comment ?? "";
            $this->bank = $this->invoicePaymentApprovalStatus->bank->id ?? "";
        }
        return view('livewire.invoice-and-sales.cheque.cheque-payment-approval-dialog');
    }

    public function sendChequeForApproval()
    {
        $this->validate(
            [
                'bank' => 'required',
                'cheque_date' => 'required',
            ]
        );

        DB::transaction(function(){

            InvoicePaymentApprovalStatusRepository::create(
                [
                    'date' => $this->cheque_date,
                    'amount' => $this->invoice->sub_total,
                    'bank_id' => $this->bank,
                    'invoice_id' => $this->invoice->id,
                    'type' => InvoicePaymentApprovalTypeEnum::Cheque,
                    'approval_status' => InvoicePaymentApprovalTypeStatusEnum::Pending,
                    'payment_id' => NULL,
                    'paymentmethoditem_id' => NULL,
                    'comment' => $this->comment
                ]
            );

            $this->invoice->status_id = status('Waiting-For-Cheque-Approval');

            $this->invoice->update();
        });

        logActivity($this->invoice->id, $this->invoice->invoice_number,'request for cheque payment have been approved');

        $this->alert(
            "success",
            "Cheque Approval",
            [
                'position' => 'center',
                'timer' => 2000,
                'toast' => false,
                'text' =>  "Credit Request has been sent for approval successfully",
            ]
        );

        return ['status' => true, 'href' => route('invoiceandsales.view', $this->invoice->id)];
    }


    public function cancelChequeForApproval()
    {
        return DB::transaction(function(){

            $this->invoice->status_id = status('Draft');

            $this->invoice->update();

            logActivity($this->invoice->id, $this->invoice->invoice_number,'request for cheque payment have been cancelled');

            $this->invoicePaymentApprovalStatus = $this->approvalStatusRepository->getPendingRequest($this->invoice->id, InvoicePaymentApprovalTypeEnum::Cheque);

            if($this->invoicePaymentApprovalStatus){

                $this->invoicePaymentApprovalStatus->approval_status = InvoicePaymentApprovalTypeStatusEnum::Declined;
                $this->invoicePaymentApprovalStatus->resolved_by_id = auth()->id();

                $this->invoicePaymentApprovalStatus->update();
            }


            $this->approvalStatusRepository->cancelAllRequest($this->invoice->id);


            $this->alert(
                "success",
                "Cheque Approval",
                [
                    'position' => 'center',
                    'timer' => 2000,
                    'toast' => false,
                    'text' =>  "Cheque Request has been cancelled successfully, Invoice has been updated to Draft",
                ]
            );

            return ['status' => true, 'href' => route('invoiceandsales.view', $this->invoice->id)];
        });
    }



    public function approvalChequePayment()
    {
        return DB::transaction(function(){

            logActivity($this->invoice->id, $this->invoice->invoice_number,'Cheque Payment request approved');

            $this->invoicePaymentApprovalStatus = $this->approvalStatusRepository->getPendingRequest($this->invoice->id, InvoicePaymentApprovalTypeEnum::Cheque);
            if(!isset($this->invoicePaymentApprovalStatus->payment_id)) {
                $this->savePayment();
            }else{
                $this->paymentRepository->saveApprovedChequePayment([
                    'payment_id' => $this->invoicePaymentApprovalStatus->payment_id,
                    'invoice_id' => $this->invoicePaymentApprovalStatus->invoice_id,
                    'user_id' => $this->invoicePaymentApprovalStatus->user_id,
                    'amount' => $this->invoicePaymentApprovalStatus->amount,
                    'customer_id' => $this->invoice->customer_id,
                    'department' => $this->invoicePaymentApprovalStatus->payment->department,
                    'payment_date' => $this->invoicePaymentApprovalStatus->payment->payment_date,
                    'invoice_type' => $this->invoicePaymentApprovalStatus->payment->invoice_type,
                    'resolved_by_id' => auth()->id()
                ]);
            }
            $this->invoice = $this->invoice->refresh(); // refresh what ever the payment component has done to the invoice

            $this->invoicePaymentApprovalStatus->approval_status = InvoicePaymentApprovalTypeStatusEnum::Approved;
            $this->invoicePaymentApprovalStatus->payment_id = $this->invoice->payment_id;

            $payment_method_id = $this->invoice->paymentmethoditems()->where('paymentmethod_id', $this->payment_method)->first();

            if($payment_method_id){
                $this->invoicePaymentApprovalStatus->paymentmethoditem_id = $payment_method_id->id;
            }

            $this->invoicePaymentApprovalStatus->update();

            $pendingRequests = $this->approvalStatusRepository->getInvoicePendingRequest($this->invoice->id);

            foreach ($pendingRequests as $pendingRequest){
                if($pendingRequest->type == InvoicePaymentApprovalTypeEnum::Credit){
                    $this->invoice->status_id = status('Waiting-For-Credit-Approval');
                    $this->invoice->update();
                }
            }

            if($pendingRequests->count() == 0)
            {
                if($this->invoicePaymentApprovalStatus->invoice->department == "retail"){
                    $this->invoicePaymentApprovalStatus->invoice->status_id = status('Complete');
                }else {
                    $this->invoicePaymentApprovalStatus->invoice->status_id = status('Paid');
                }
                $this->invoicePaymentApprovalStatus->invoice->update();

                logActivity($this->invoice->id, $this->invoice->invoice_number,'Invoice status updated to paid');
            }

            $this->alert(
                "success",
                "Cheque Approval",
                [
                    'position' => 'center',
                    'timer' => 2000,
                    'toast' => false,
                    'text' =>  "Cheque Request has been approved successfully",
                ]
            );
           
            return ['status' => true, 'href' => route('invoiceandsales.view', $this->invoice->id)];
        });

    }

}
