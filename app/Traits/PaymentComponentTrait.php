<?php
namespace App\Traits;

use App\Models\Creditpaymentlog;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\OpayPaymentLog;
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

    public array $opay_log_ids = []; // IDs of OpayPaymentLog entries recorded in this session

    public string $opay_last_order_no = ""; // Most recent OPay orderNo sent to POS (used for Inquiry / Re-confirm)

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
     * Returns the amount (in Naira) expected to be collected via OPay POS:
     * - Full sub_total when payment_method === "9"
     * - split_payments[9]['amount'] when payment_method === "6" (Split Payment)
     */
    public function getOpayTargetAmount(): float
    {
        if ($this->payment_method === "6") {
            return (float) ($this->split_payments[9]['amount'] ?? 0);
        }

        return (float) ($this->sub_total ?? 0);
    }

    /**
     * Record the start of an OPay POS attempt before waiting for the terminal response,
     * so the orderNo is persisted even if a timeout or connection drop occurs.
     */
    public function opayStartAttempt(string $orderNo, int $amountKobo): void
    {
        $this->opay_last_order_no = $orderNo;

        $hasInvoice = isset($this->invoice) && isset($this->invoice->id);
        $invoiceId = $hasInvoice ? $this->invoice->id : null;
        $customerId = $hasInvoice
            ? $this->invoice->customer_id
            : ($this->customer_id ?? ($this->customer->id ?? null));

        $log = OpayPaymentLog::create([
            'invoice_id' => $invoiceId,
            'creditpaymentlog_id' => null,
            'payment_id' => null,
            'paymentmethoditem_id' => null,
            'customer_id' => $customerId,
            'user_id' => auth()->id(),
            'order_no' => $orderNo,
            'amount' => $amountKobo / 100,
            'amount_kobo' => $amountKobo,
            'status' => 'pending',
            'response_code' => '203',
            'response_message' => 'Sent to POS terminal (awaiting customer payment)',
        ]);

        if (!in_array($log->id, $this->opay_log_ids)) {
            $this->opay_log_ids[] = $log->id;
        }
    }

    /**
     * Persist an OPay POS terminal attempt/response to opay_payment_logs.
     */
    public function recordOpayLog(array $result): OpayPaymentLog
    {
        $data = $result['data'] ?? [];
        $hasInvoice = isset($this->invoice) && isset($this->invoice->id);
        $invoiceId = $hasInvoice ? $this->invoice->id : null;
        $customerId = $hasInvoice
            ? $this->invoice->customer_id
            : ($this->customer_id ?? ($this->customer->id ?? null));

        $orderNo = $data['orderNo'] ?? ($result['order_no'] ?? ($this->opay_last_order_no ?: null));
        if ($orderNo) {
            $this->opay_last_order_no = $orderNo;
        }

        $existing = $orderNo
            ? OpayPaymentLog::where('order_no', $orderNo)->latest('id')->first()
            : null;

        $amountKobo = isset($data['amount']) && is_numeric($data['amount'])
            ? (int) $data['amount']
            : ($existing ? (int) $existing->amount_kobo : (int) round($this->getOpayTargetAmount() * 100));

        $attributes = [
            'invoice_id' => $invoiceId ?? ($existing->invoice_id ?? null),
            'customer_id' => $customerId ?? ($existing->customer_id ?? null),
            'user_id' => auth()->id() ?? ($existing->user_id ?? null),
            'order_no' => $orderNo,
            'order_number' => $data['orderNumber'] ?? ($existing->order_number ?? null),
            'transaction_number' => $data['transactionNumber'] ?? ($existing->transaction_number ?? null),
            'terminal_sn' => $data['SN'] ?? ($existing->terminal_sn ?? null),
            'cashier_method' => $data['cashierMethod'] ?? ($existing->cashier_method ?? null),
            'card_type' => $data['cardType'] ?? ($existing->card_type ?? null),
            'card_number' => $data['cardNumber'] ?? ($existing->card_number ?? null),
            'amount' => $amountKobo / 100,
            'amount_kobo' => $amountKobo,
            'status' => $result['status'] ?? ($existing->status ?? null),
            'response_code' => $result['response_code'] ?? ($data['responseCode'] ?? ($existing->response_code ?? null)),
            'response_message' => $result['message'] ?? ($data['response'] ?? ($existing->response_message ?? null)),
            'raw_payload' => $result,
        ];

        if ($existing) {
            $existing->update($attributes);
            $log = $existing;
        } else {
            $log = OpayPaymentLog::create($attributes);
        }

        if (!in_array($log->id, $this->opay_log_ids)) {
            $this->opay_log_ids[] = $log->id;
        }

        return $log;
    }

    /**
     * Called from the browser when a non-approved terminal response (cancelled, failed, timeout, etc.) occurs.
     */
    public function opayLogAttempt(array $result): void
    {
        $this->recordOpayLog($result);
    }

    /**
     * Called from the browser after the OPay POS terminal (via the Print Agent)
     * reported the result of a purchase or Inquiry. Only an approved payment whose
     * amount matches the amount due unlocks "Save Payment".
     */
    public function opayApproved(array $result) : void
    {
        $this->opay_approved = false;
        $this->opay_data = [];

        $log = $this->recordOpayLog($result);

        $data = $result['data'] ?? [];
        $expectedKobo = (string) (int) round($this->getOpayTargetAmount() * 100);
        $code = (string) ($result['response_code'] ?? ($data['responseCode'] ?? ''));

        if (!in_array($code, ['202', '00'], true) || ($result['status'] ?? '') !== 'success') {
            $this->alert('error', 'OPay POS', ['position' => 'center', 'timer' => 4000, 'toast' => false, 'text' => 'Payment was not approved on the OPay terminal.']);
            return;
        }

        $reportedKobo = isset($data['amount']) && $data['amount'] !== ''
            ? (string) $data['amount']
            : (string) $log->amount_kobo;

        if ($reportedKobo !== $expectedKobo) {
            $this->alert('error', 'OPay POS', ['position' => 'center', 'timer' => 6000, 'toast' => false, 'text' => 'Amount paid on the terminal does not match the amount due.']);
            return;
        }

        $this->opay_approved = true;
        $this->opay_data = [
            'provider' => 'opay',
            'opay_log_id' => $log->id,
            'order_no' => $log->order_no,
            'order_number' => $data['orderNumber'] ?? $log->order_number,
            'transaction_number' => $data['transactionNumber'] ?? $log->transaction_number,
            'terminal_sn' => $data['SN'] ?? $log->terminal_sn,
            'cashier_method' => $data['cashierMethod'] ?? $log->cashier_method,
            'card_type' => $data['cardType'] ?? $log->card_type,
            'card_number' => $data['cardNumber'] ?? $log->card_number,
            'amount_kobo' => $reportedKobo,
            'response_code' => $code,
            'response' => $data['response'] ?? $log->response_message,
        ];

        if (isset($this->split_payments[9])) {
            $this->split_payments[9]['opay_data'] = $this->opay_data;
        }
    }

    public function opayReset() : void
    {
        $this->opay_approved = false;
        $this->opay_data = [];
        if (isset($this->split_payments[9]['opay_data'])) {
            unset($this->split_payments[9]['opay_data']);
        }
    }

    public function updatedPaymentMethod() : void
    {
        // Switching method discards any previous OPay approval.
        $this->opayReset();
    }

    public function updatedSplitPayments($value, $key) : void
    {
        if ((string) $key === '9.amount' && $this->opay_approved) {
            $newKobo = (int) round(((float) $value) * 100);
            $approvedKobo = (int) ($this->opay_data['amount_kobo'] ?? 0);
            if ($newKobo !== $approvedKobo) {
                $this->opayReset();
            }
        }
    }

    public function savePayment()
    {
        $opaySplitAmount = ($this->payment_method === "6") ? (float) ($this->split_payments[9]['amount'] ?? 0) : 0;
        if (($this->payment_method === "9" || ($this->payment_method === "6" && $opaySplitAmount > 0)) && !$this->opay_approved) {
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
        $opaySplitAmount = ($this->payment_method === "6") ? (float) ($this->split_payments[9]['amount'] ?? 0) : 0;
        if (($this->payment_method === "9" || ($this->payment_method === "6" && $opaySplitAmount > 0)) && !$this->opay_approved) {
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

            $lastLog = OpayPaymentLog::where('invoice_id', $this->invoice->id)
                ->whereNull('payment_id')
                ->whereNotNull('order_no')
                ->latest('id')
                ->first();
            if ($lastLog) {
                $this->opay_last_order_no = (string) $lastLog->order_no;
            }
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

        $lastLog = OpayPaymentLog::where('invoice_id', $this->invoice->id)
            ->whereNull('payment_id')
            ->whereNotNull('order_no')
            ->latest('id')
            ->first();
        $this->opay_last_order_no = $lastLog ? (string) $lastLog->order_no : "";
    }

    public function generateCreditPayment(array $data)
    {
        $this->sub_total = $data['amount'];
        $this->customer_id = $data['customer_id'];
        $this->customer = Customer::find($data['customer_id']);
        $this->amount = $data['amount'];

        $lastLog = OpayPaymentLog::where('customer_id', $this->customer_id)
            ->whereNull('invoice_id')
            ->whereNull('payment_id')
            ->whereNotNull('order_no')
            ->whereDate('created_at', today())
            ->latest('id')
            ->first();
        $this->opay_last_order_no = $lastLog ? (string) $lastLog->order_no : "";
    }

    public function generateDepositPayment(array $data)
    {
        $this->sub_total = $data['amount'];
        $this->customer_id = $data['customer_id'];
        $this->customer = Customer::find($data['customer_id']);
        $this->amount = $data['amount'];
    }

}
