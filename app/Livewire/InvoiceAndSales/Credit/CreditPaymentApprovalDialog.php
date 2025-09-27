<?php

namespace App\Livewire\InvoiceAndSales\Credit;

use App\Enums\InvoicePaymentApprovalTypeEnum;
use App\Enums\InvoicePaymentApprovalTypeStatusEnum;
use App\Models\Creditpaymentlog;
use App\Models\Customer;
use App\Models\InvoicePaymentApprovalStatus;
use App\Repositories\InvoicePaymentApprovalStatusRepository;
use App\Repositories\PaymentRepository;
use App\Traits\PaymentComponentTrait;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use App\Traits\LivewireAlert;

class CreditPaymentApprovalDialog extends Component
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
    private  $invoicePaymentApprovalStatus;

    private PaymentRepository $paymentRepository;

    public function booted(InvoicePaymentApprovalStatusRepository $approvalStatusRepository,  PaymentRepository $paymentRepository)
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

        $this->payment_method = "4";
    }

    public function render()
    {
        if($this->mode === "approveDecline")
        {
            $this->invoicePaymentApprovalStatus = $this->approvalStatusRepository->getPendingRequest($this->invoice->id, InvoicePaymentApprovalTypeEnum::Credit);

        }
        return view('livewire.invoice-and-sales.credit.credit-payment-approval-dialog');
    }


    public function sendBarcodeForApproval()
    {
        $this->invoice->status_id = status('Waiting-For-Credit-Approval');

        $this->invoice->update();

        InvoicePaymentApprovalStatusRepository::create(
            [
                'date' => todaysDate(),
                'amount' => $this->invoice->sub_total,
                'bank_id' => NULL,
                'invoice_id' => $this->invoice->id,
                'type' => InvoicePaymentApprovalTypeEnum::Credit,
                'approval_status' => InvoicePaymentApprovalTypeStatusEnum::Pending,
                'comment' => $this->comment
            ]
        );

        logActivity($this->invoice->id, $this->invoice->invoice_number,'request for credit payment have been approved');

        $this->alert(
            "success",
            "Credit Approval",
            [
                'position' => 'center',
                'timer' => 2000,
                'toast' => false,
                'text' =>  "Credit Request has been sent for approval successfully",
            ]
        );

        return ['status' => true, 'href' => route('invoiceandsales.view', $this->invoice->id)];
    }


    public function cancelBarcodeForApproval()
    {
        return DB::transaction(function(){
            $this->invoice->status_id = status('Draft');

            $this->invoice->update();

            logActivity($this->invoice->id, $this->invoice->invoice_number,'request for credit payment have been cancelled');

            $this->invoicePaymentApprovalStatus = $this->approvalStatusRepository->getPendingRequest($this->invoice->id, InvoicePaymentApprovalTypeEnum::Credit);

            if($this->invoicePaymentApprovalStatus){

                $this->invoicePaymentApprovalStatus->approval_status = InvoicePaymentApprovalTypeStatusEnum::Declined;

                $this->invoicePaymentApprovalStatus->update();
            }

            $this->approvalStatusRepository->cancelAllRequest($this->invoice->id);

            $this->alert(
                "success",
                "Credit Approval",
                [
                    'position' => 'center',
                    'timer' => 2000,
                    'toast' => false,
                    'text' =>  "Credit Request has been cancelled successfully",
                ]
            );

            return ['status' => true, 'href' => route('invoiceandsales.view', $this->invoice->id)];
        });
    }



    public function approveCreditPayment()
    {
        return DB::transaction(function(){

            logActivity($this->invoice->id, $this->invoice->invoice_number,'credit payment request has been approved');

            $this->invoicePaymentApprovalStatus = $this->approvalStatusRepository->getPendingRequest($this->invoice->id, InvoicePaymentApprovalTypeEnum::Credit);

            $this->generateCreditPayment([
                'amount' => $this->sub_total,
                'customer_id' => $this->invoice->customer_id,
            ]);

            if(!isset($this->invoicePaymentApprovalStatus->payment_id)) {
                $this->savePayment();
            }else{
               $this->paymentRepository->saveApproveCreditpayment([
                    'payment_id' => $this->invoicePaymentApprovalStatus->payment_id,
                    'invoice_id' => $this->invoicePaymentApprovalStatus->invoice_id,
                    'user_id' => $this->invoicePaymentApprovalStatus->user_id,
                    'amount' => $this->invoicePaymentApprovalStatus->amount,
                    'customer_id' => $this->invoice->customer_id,
                    'department' => $this->invoicePaymentApprovalStatus->payment->department,
                    'payment_date' => $this->invoicePaymentApprovalStatus->payment->payment_date,
                    'invoice_type' => $this->invoicePaymentApprovalStatus->payment->invoice_type
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
                if($pendingRequest->type == InvoicePaymentApprovalTypeEnum::Cheque){
                    $this->invoice->status_id = status('Waiting-For-Cheque-Approval');
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
                "Credit Approval",
                [
                    'position' => 'center',
                    'timer' => 2000,
                    'toast' => false,
                    'text' =>  "Credit Payment Request has been approved successfully, Invoice has been updated to Paid",
                ]
            );

            return ['status' => true, 'href' => route('invoiceandsales.view', $this->invoice->id)];
        });

    }

}
