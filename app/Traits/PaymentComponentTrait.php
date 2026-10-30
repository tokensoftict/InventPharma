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

    public bool $opay_approved = false; // OPay POS payment approved by terminal

    public array $opay_data = []; // OPay POS transaction details (saved in payment_info)

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

    /**
     * Called from the browser after the OPay POS terminal (via the Print Agent)
     * reported the result of a purchase. Only an approved payment whose amount
     * matches the amount due unlocks "Save Payment".
     */
    public function opayApproved(array $result) : void
    {
        $this->opay_approved = false;
        $this->opay_data = [];

        $data = $result['data'] ?? [];
        $expectedKobo = (string) (int) round(((float) $this->sub_total) * 100);

        if (($result['response_code'] ?? '') !== '202' || ($result['status'] ?? '') !== 'success') {
            $this->alert('error', 'OPay POS', ['position' => 'center', 'timer' => 4000, 'toast' => false, 'text' => 'Payment was not approved on the OPay terminal.']);
            return;
        }

        if ((string) ($data['amount'] ?? '') !== $expectedKobo) {
            $this->alert('error', 'OPay POS', ['position' => 'center', 'timer' => 6000, 'toast' => false, 'text' => 'Amount paid on the terminal does not match the amount due.']);
            return;
        }

        $this->opay_approved = true;
        $this->opay_data = [
            'provider' => 'opay',
            'order_no' => $data['orderNo'] ?? null,
            'order_number' => $data['orderNumber'] ?? null,
            'transaction_number' => $data['transactionNumber'] ?? null,
            'terminal_sn' => $data['SN'] ?? null,
            'cashier_method' => $data['cashierMethod'] ?? null,
            'card_type' => $data['cardType'] ?? null,
            'card_number' => $data['cardNumber'] ?? null,
            'amount_kobo' => $data['amount'] ?? null,
            'response_code' => $result['response_code'] ?? null,
            'response' => $data['response'] ?? null,
        ];
    }

    public function opayReset() : void
    {
        $this->opay_approved = false;
        $this->opay_data = [];
    }

    public function updatedPaymentMethod() : void
    {
        // Switching method discards any previous OPay approval.
        $this->opayReset();
    }

    public function savePayment()
    {
        if ($this->payment_method === "9" && !$this->opay_approved) {
            $this->alert('error', 'OPay POS', ['position' => 'center', 'timer' => 4000, 'toast' => false, 'text' => 'Collect the payment on the OPay terminal first.']);
            return;
        }

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
        if ($this->payment_method === "9" && !$this->opay_approved) {
            $this->alert('error', 'OPay POS', ['position' => 'center', 'timer' => 4000, 'toast' => false, 'text' => 'Collect the payment on the OPay terminal first.']);
            return;
        }

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
