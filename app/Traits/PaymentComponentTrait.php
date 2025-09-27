<?php
namespace App\Traits;

use App\Models\Creditpaymentlog;
use App\Models\Customer;
use App\Models\Invoice;
use App\Repositories\InvoiceRepository;
use App\Repositories\PaymentRepository;
use Illuminate\Support\Facades\DB;
use App\Traits\LivewireAlert;

trait PaymentComponentTrait
{

    public Invoice  $invoice;

    public bool $btnEnabled = false;

    public $payments;

    public $banks;

    public $bankAccounts;

    public string $description;

    public $bank_account_id;

    public string $bank = ""; // this is for cheque payment

    public string $comment = ""; // this is for cheque payment
    public string $cheque_date = "" ;// this is for cheque payment



    public array $split_payments = [];

    public String $cash_tendered = "";

    public String $payment_method = "";

    public String $change;

    public bool $isInvoice = false;

    public String $sub_total;

    public String $totalSplitAmount = "0";

    public String $totalCredit;

    public String $error_deposit = "";

    public String $totalDeposit;

    private PaymentRepository $paymentRepository;

    private InvoiceRepository $invoiceRepository;

    public String $select2key = "";

    public array $data = [];

    public function boot(PaymentRepository $paymentRepository, InvoiceRepository $invoiceRepository)
    {
        $this->paymentRepository = $paymentRepository;
        $this->invoiceRepository = $invoiceRepository;
    }

    private function validateSplitPayment() : bool
    {

        return $this->paymentRepository->validateSplitPayment($this);

    }

    private function updateDisplay() : void
    {
        $this->paymentRepository->updateDisplay($this);
    }

    public function savePayment()
    {
        DB::transaction(function(){
            $this->paymentRepository->savePayment($this);
            $filterChequeAndCredit = 0;
            if($this->payment_method == "6"){
                $filterChequeAndCredit = collect($this->split_payments)->filter(function($item, $index){
                    if(($index == "8" || $index == "4") && $item['amount'] > 0){
                        return $item;
                    }
                })->count();
            }
            if($this->invoice->in_department == "retail" && $filterChequeAndCredit === 0){
                $this->invoice->status_id = status('Complete');
                $this->invoice->update();
                return redirect()->route(type().'view', $this->invoice->id);
            }
        });

        return redirect()->route(type().'view', $this->invoice->id);
    }

    public function saveCreditPayment() : void
    {
        DB::transaction(function(){
            $this->paymentRepository->saveCreditPayment($this);
        });
    }

    public function saveDepositPayment() : void
    {
        DB::transaction(function(){
            $this->paymentRepository->saveDepositPayment($this);
        });
    }

    private function getPaymentInfo() : array
    {
        return  $this->paymentRepository->getPaymentinfo($this);
    }

    private function mountProperties() : void
    {
        $this->payments = paymentMethods(true);
        $this->bankAccounts = bank_accounts(true);
        $this->select2key  =mt_rand();
        $this->change = money(0);
        $this->totalSplitAmount = money(0);

        $this->banks = banks();


        if(isset($this->invoice->id)) {

            $this->totalCredit = $this->invoice->customer->credit_balance;

            $this->totalDeposit = $this->invoice->customer->deposit_balance;
        }

        foreach ($this->payments as $payment)
        {
            if(!isset(  $this->split_payments[$payment->id])) {
                if($payment->id == 8){
                    $this->split_payments[$payment->id]['amount'] = 0;
                    $this->split_payments[$payment->id]['bank'] = "";
                    $this->split_payments[$payment->id]['cheque_date'] = "";
                }else {
                    $this->split_payments[$payment->id]['amount'] = 0;
                    $this->split_payments[$payment->id]['bank_account_id'] = "";
                }
            }

        }


    }


    public function generateInvoicePayment(Invoice $invoice)
    {
        $this->invoice = $invoice;

        $this->customer = $this->invoice->customer;

        $this->totalCredit =  $this->customer->credit_balance;

        $this->totalDeposit = $this->customer->deposit_balance;
    }

    public function generateCreditPayment(array $data)
    {
        $this->sub_total = $data['amount'];
        $this->customer_id = $data['customer_id'];
        $this->customer = Customer::find($data['customer_id']);
        $this->amount = $data['amount'];
    }

    public function generateDepositPayment(array $data)
    {
        $this->sub_total = $data['amount'];
        $this->customer_id = $data['customer_id'];
        $this->customer = Customer::find($data['customer_id']);
        $this->amount = $data['amount'];
    }

}
