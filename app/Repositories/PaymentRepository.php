<?php

namespace App\Repositories;

use App\Enums\InvoicePaymentApprovalTypeEnum;
use App\Enums\InvoicePaymentApprovalTypeStatusEnum;
use App\Livewire\InvoiceAndSales\Cheque\ChequePaymentApprovalDialog;
use App\Livewire\InvoiceAndSales\Credit\CreditPaymentApprovalDialog;
use App\Jobs\AddLogToCustomerLedger;
use App\Models\Creditpaymentlog;
use App\Models\Customerdeposit;
use App\Models\CustomerLedger;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Paymentmethoditem;
use App\Services\Online\ProcessOrderService;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class PaymentRepository
{

    public InvoiceRepository $invoiceRepository;

    public function __construct(InvoiceRepository $invoiceRepository)
    {
        $this->invoiceRepository = $invoiceRepository;
    }


    public function addPayment(array $data, $obj) : Payment
    {
        //delete existing payment for the invoice

        /*  checking for existing payment  */

        if($data['invoice_type']  == Invoice::class) {
            DB::transaction(function () use ($data) {
                $payments = Payment::where('invoice_number', $data['invoice_number'])->get();

                foreach ($payments as $payment) {
                    $payment->paymentmethoditems()->delete();
                    CustomerLedger::where('payment_id', $payment->id)->delete();
                    $payment->delete();
                }
            });
        }


        /*  ending of deleting and checking for existing payment  */

        $methods = $data['methods'];

        Arr::forget($data, ['methods']);

        $data = array_merge($data, [
            'user_id' => auth()->id(),
            'payment_time' => Carbon::now()->toDateTimeLocalString(),
            'payment_date' => !isset($data['payment_date']) ? dailyDate() : $data['payment_date'],
        ]);

        $paymentItems = $this->parsePaymentMethods($methods, $data, $obj);

        $data = array_merge($data , ['total_paid' => $paymentItems['total']]);

        $payment = Payment::create($data);

        //request for approval if there is any

        $data['payment_id'] = $payment->id;

        $invoicePaymentStatus = $this->savePaymentInvoiceStatus($methods, $data, $obj);

        unset($data['payment_id']);

        $payment->paymentmethoditems()->saveMany($paymentItems['items']);

        if(count( $paymentItems['credit']) > 0)
        {
            $credit = $payment->paymentmethoditems()->where('paymentmethod_id', 4)->first();

            $paymentItems['credit']['paymentmethoditem_id'] = $credit->id;

            $paymentItems['credit']['payment_id'] = $payment->id;

            $this->addCreditPayment($paymentItems['credit']);
        }

        if(count($paymentItems['deposit']) > 0)
        {
            $deposit = $payment->paymentmethoditems()->where('paymentmethod_id', 5)->first();

            $paymentItems['deposit']['paymentmethoditem_id'] =  $deposit->id;

            $paymentItems['deposit']['payment_id'] = $payment->id;

            $this->addDepositPayment( $paymentItems['deposit']);
        }

        if($payment->invoice_type == Invoice::class){

            $payment->invoice()->update([
                'payment_id' => $payment->id,
                'status_id' =>  (auth()->user()->department_id === 4 ? status('Complete') : status('Paid')),
                'total_amount_paid' =>$paymentItems['total']
            ]);

            if(count($invoicePaymentStatus) > 0){
                foreach ($invoicePaymentStatus as $status){
                    $payment->invoice()->update([
                        'status_id' =>  $status,
                    ]);
                }
                logActivity($payment->invoice->id, $payment->invoice->invoice_number, "Some payment were added to the invoice, others are sent for approval ".$payment->invoice->status->name);
            }else{
                logActivity($payment->invoice->id, $payment->invoice->invoice_number, "Payment(s) was added to invoice Status : ".$payment->invoice->status->name);
            }
        }

        $totalPaid = $payment->paymentmethoditems()->whereIn('paymentmethod_id', [1,2,3])->sum('amount');

        if($totalPaid > 0 && $payment->customer_id !== 1) { // customer ledger for walking customer
            dispatch(new AddLogToCustomerLedger([
                'payment_id' => $payment->id,
                'invoice_id' => NULL,
                'customer_id' => $payment->customer_id,
                'amount' => $totalPaid,
                'transaction_date' => $payment->payment_date,
                'user_id' => auth()->id(),
            ]));
        }

        return $payment;

    }


    public function savePaymentInvoiceStatus(array $methods, array $data, $obj){
        $invoicePaymentStatus = [];
        if(get_class($obj) !== ChequePaymentApprovalDialog::class && get_class($obj) !== CreditPaymentApprovalDialog::class) {
            foreach ($methods as $method) {
                if ($method['paymentmethod_id'] == "4") {
                    InvoicePaymentApprovalStatusRepository::create([
                        'type' => InvoicePaymentApprovalTypeEnum::Credit,
                        'date' => todaysDate(),
                        'amount' => $method['amount'],
                        'payment_id' => $data['payment_id'],
                        'approval_status' => InvoicePaymentApprovalTypeStatusEnum::Pending,
                        'invoice_id' => $data['invoice_id'],
                    ]);

                    $invoicePaymentStatus[] = status('Waiting-For-Credit-Approval');
                }

                if ($method['paymentmethod_id'] == "8") {
                    InvoicePaymentApprovalStatusRepository::create([
                        'type' => InvoicePaymentApprovalTypeEnum::Cheque,
                        'date' => $obj->cheque_date,
                        'amount' => $method['amount'],
                        'payment_id' => $data['payment_id'],
                        'approval_status' => InvoicePaymentApprovalTypeStatusEnum::Pending,
                        'invoice_id' => $data['invoice_id'],
                        'bank_id' => $obj->bank,
                    ]);

                    $invoicePaymentStatus[] = status('Waiting-For-Cheque-Approval');
                }
            }
        }
        return $invoicePaymentStatus;
    }

    public function saveApproveCreditpayment($data)
    {
        $payment_method_item = Paymentmethoditem::create([
            'payment_id' => $data['payment_id'],
            'invoice_id' => $data['invoice_id'],
            'user_id' => $data['user_id'],
            'amount' => $data['amount'],
            'paymentmethod_id' => 4,
            'customer_id' => $data['customer_id'],
            'department' => $data['department'],
            'payment_date' => $data['payment_date'],
            'invoice_type' => $data['invoice_type'],
        ]);

        $credit = [
            'credit_number' => creditPaymentReference(),
            'user_id' =>$data['user_id'],
            'paymentmethod_id' => 4,
            'customer_id' => $data['customer_id'],
            'paymentmethoditem_id' => $payment_method_item->id,
            'payment_id' => $data['payment_id'],
            'invoicelog_type' => $data['invoice_type'],
            'invoicelog_id' => $data['invoice_id'],
            'amount' => -($data['amount']),
            'payment_date' => $data['payment_date']
        ];

        $this->addCreditPayment($credit);

        return $payment_method_item;
    }

    public function saveApprovedChequePayment($data)
    {
        $payment_method_item = Paymentmethoditem::create([
            'payment_id' => $data['payment_id'],
            'invoice_id' => $data['invoice_id'],
            'user_id' => $data['user_id'],
            'amount' => $data['amount'],
            'paymentmethod_id' => 8,
            'customer_id' => $data['customer_id'],
            'department' => $data['department'],
            'payment_date' => $data['payment_date'],
            'invoice_type' => $data['invoice_type'],
        ]);

        return $payment_method_item;
    }
    public function parsePaymentMethods(array $methods, array $data, &$obj) : array
    {
        $pmethods = [];

        $credit = [];

        $deposit = [];

        foreach ($methods as $method)
        {
            if(get_class($obj) !== ChequePaymentApprovalDialog::class && get_class($obj) !== CreditPaymentApprovalDialog::class) {
                if ($method['paymentmethod_id'] == "4") continue;
                if ($method['paymentmethod_id'] == "8") continue;
            }

            $pmethods[] = new Paymentmethoditem(
                array_merge($method, [
                    'user_id' => $data['user_id'],
                    'customer_id' => $data['customer_id'],
                    'invoice_type' => $data['invoice_type'],
                    'invoice_id' => $data['invoice_id'],
                    'payment_date' => $data['payment_date']
                ])
            );

            if($method['paymentmethod_id'] == "4")
            {
                $credit = [
                    'credit_number' => creditPaymentReference(),
                    'user_id' =>$data['user_id'],
                    'paymentmethod_id' => NULL,
                    'customer_id' => $data['customer_id'],
                    'paymentmethoditem_id' => NULL,
                    'payment_id' => NULL,
                    'invoicelog_type' => $data['invoice_type'],
                    'invoicelog_id' => $data['invoice_id'],
                    'amount' => -($method['amount']),
                    'payment_date' => $data['payment_date']
                ];
            }

            if($method['paymentmethod_id'] == "5")
            {
                $deposit = [
                    'deposit_number' => depositPaymentReference(),
                    'payment_id' => NULL,
                    'customer_id' => $data['customer_id'],
                    'amount' => -($method['amount']),
                    'created_by' => auth()->id(),
                    'last_updated_by' =>auth()->id(),
                    'paymentmethoditem_id' => NULL,
                    'deposit_date' => todaysDate(),
                    'deposit_time' => Carbon::now()->toDayDateTimeString(),
                    'description' => " ",
                ];
            }

        }

        return [
            'total' => collect($methods)->sum('amount'),
            'items' => $pmethods,
            'credit' => $credit,
            'deposit' => $deposit
        ];
    }



    public function addCreditPayment(array $data)
    {
        $credit = Creditpaymentlog::create($data);
        $credit->customer->updateCreditBalance();
    }

    public function addDepositPayment(array $data)
    {
        // Customerdeposit::create($data);
    }

    public function getPaymentinfo(&$component) : array
    {
        if($component->payment_method === "1")
        {
            return [
                'cash_tendered' => $component->cash_tendered,
                'change' => $component->cash_tendered - $component->sub_total
            ];
        }
        else if($component->payment_method === "2" || $component->payment_method === "3")
        {
            return [
                'bank_account_id' => $component->bank_account_id
            ];
        }else if($component->payment_method === "8"){
            return [
                'bank' => $component->bank,
                'cheque_date' => $component->cheque_date,
                'comment' => $component->comment
            ];
        }
        else {
            return [

            ];
        }
    }


    public  function validateSplitPayment(&$obj) : bool
    {

        $tt = collect($obj->split_payments)->sum(function($item){
            return (float)$item['amount'] != "" ?  (float)$item['amount'] : 0 ;
        });

        $obj->totalSplitAmount = money($tt);

        if(isset($obj->split_payments[8]) && $obj->split_payments[8]['amount'] > 0 && ($obj->invoice->customer_id == 0 || $obj->invoice->customer_id == 1 )){
            return false;
        }

        if(isset($obj->split_payments[4]) && $obj->split_payments[4]['amount'] > 0 && ($obj->invoice->customer_id == 0 || $obj->invoice->customer_id == 1 )) {
            return false;
        }

        if(isset($obj->split_payments[3])) {
            if ($obj->split_payments[2]['amount'] > 0 && $obj->split_payments[2]['bank_account_id'] == "") return false;
        }

        if(isset($obj->split_payments[3])) {
            if ($obj->split_payments[3]['amount'] > 0 && $obj->split_payments[3]['bank_account_id'] == "") return false;
        }

        if(isset($obj->split_payments[5])) {
            if ($obj->split_payments[5]['amount'] > 0 && $this->validateDepositPayment($obj, $obj->split_payments[5]['amount']) === false) return false;
        }
        if(isset($obj->split_payments[5])) {
            if ($obj->split_payments[5]['amount'] == 0) {
                $obj->error_deposit = "";
            }
        }

        if(isset($obj->split_payments[8])){
            $obj->bank = $obj->split_payments[8]['bank'];
            $obj->cheque_date = $obj->split_payments[8]['cheque_date'];
            if ($obj->split_payments[8]['amount'] > 0 && (empty($obj->split_payments[8]['bank']) || empty($obj->split_payments[8]['cheque_date']) )) return false;
        }

        if(($tt < $obj->sub_total)) return false;

        if(($tt > $obj->sub_total)) return false;


        return true;

    }


    public  function updateDisplay(&$obj) : void
    {

        $this->error_deposit = "";

        if ($obj->payment_method === "6") {

            $obj->btnEnabled =  $this->validateSplitPayment( $obj);
        }
        else if($obj->payment_method === "1") {

            if(!($obj->cash_tendered === "" || $obj->cash_tendered < $obj->sub_total))
            {
                $obj->change =   $obj->cash_tendered - $obj->sub_total;
                if($obj->change > $obj->sub_total){
                    $obj->btnEnabled = false;
                }else {
                    $obj->btnEnabled = true;
                }
            }else{
                $obj->btnEnabled = false;
            }

        }
        else if($obj->payment_method === "2" || $obj->payment_method === "3"){

            if($obj->bank_account_id != "")
            {
                $obj->btnEnabled = true;
            }
        }else if($obj->payment_method === "5"){

            if(!$this->validateDepositPayment($obj, $obj->sub_total))
            {
                $obj->btnEnabled = false;

            }else{

                $obj->btnEnabled = true;
            }
        }
        else if($obj->payment_method === "8"){

            if($obj->bank === "" || $obj->cheque_date === "") {
                $obj->btnEnabled = false;
            }else{
                $obj->btnEnabled = true;
            }

        }
        else if($obj->payment_method === "")
        {
            $obj->btnEnabled = false;
        }
        else{
            $obj->btnEnabled = true;
        }

    }



    public function bridgePayment(&$obj, &$payment_data, &$payment_data_items): void
    {
        if($obj->payment_method === "6")
        {
            $payment_data['total_paid'] = collect($obj->split_payments)->sum('amount');

            foreach ($obj->split_payments as $key=>$split_payment)
            {
                if( $split_payment['amount'] > 0) {
                    $payment_data_items[] = [
                        'amount' => $split_payment['amount'],
                        'paymentmethod_id' => $key,
                        'payment_info' => json_encode($obj->split_payments[$key]),
                    ];
                }
            }
        }
        else {
            $payment_data['total_paid'] = $obj->sub_total;

            $payment_data_items[] = [
                'amount' => $obj->sub_total,
                'paymentmethod_id' => $obj->payment_method,
                'amount_tendered' => $obj->cash_tendered ?? NULL,
                'bank_account_id' => $obj->bank_account_id ?? NULL,
                'payment_info' => json_encode($this->getPaymentInfo($obj))
            ];
        }

    /*
        if(isset( $obj->invoice) && $obj->invoice->online_order_status == "1"){

            if((int)$obj->payment_method === 4){

                _GET('processorder/' . $obj->invoice->onliner_order_id . "/6");

                $obj->invoice->online_order_debit = 1;

                $obj->invoice->update();

            }
        }
    */

        $payment_data['methods'] = $payment_data_items;
    }


    public function saveDepositPayment(&$obj) {

        $payment_data_items = [];

        $deposit = Customerdeposit::create([
            'deposit_number' => depositPaymentReference(),
            'payment_id' => NULL,
            'customer_id' => $obj->customer_id,
            'amount' =>  $obj->sub_total,
            'created_by' => auth()->id(),
            'last_updated_by' => auth()->id(),
            'paymentmethoditem_id' => NULL,
            'deposit_date' => todaysDate(),
            'deposit_time' => Carbon::now()->toDayDateTimeString(),
            'description' => $obj->description,
        ]);


        $payment_data = [
            'user_id' => auth()->id(),
            'customer_id' => $obj->customer_id,
            'subtotal' => $obj->sub_total,
            'payment_date' => dailyDate(),
            'invoice_number' => $deposit->deposit_number,
            'invoice_type' => Customerdeposit::class,
            'invoice_id' =>  $deposit->id
        ];

        $this->bridgePayment($obj, $payment_data, $payment_data_items);

        $payment =  $this->addPayment($payment_data, $obj);


        $deposit->payment_id = $payment->id;

        $deposit->update();

        $obj->alert(
            "success",
            "Deposit Payment",
            [
                'position' => 'center',
                'timer' => 1500,
                'toast' => false,
                'text' =>  "Deposit Payment has been added successfully!.",
            ]
        );

        $this->completePayment($obj,$payment->id);

    }


    public function saveCreditPayment(&$obj) : void
    {
        DB::transaction(function() use($obj){
            $payment_data_items = [];

            $credit = Creditpaymentlog::create(
                [
                    'credit_number' => creditPaymentReference(),
                    'payment_id' => NULL,
                    'user_id' =>  auth()->id(),
                    'paymentmethod_id' => $obj->payment_method,
                    'customer_id' =>  $obj->customer_id,
                    'paymentmethoditem_id' => NULL,
                    'invoicelog_type' =>NULL ,
                    'invoicelog_id' => NULL,
                    'amount' => $obj->sub_total,
                    'payment_date' => dailyDate()
                ]
            );

            $payment_data = [
                'user_id' => auth()->id(),
                'customer_id' => $obj->customer_id,
                'subtotal' => $obj->sub_total,
                'payment_date' => dailyDate(),
                'invoice_number' =>  $credit->credit_number,
                'invoice_type' => Creditpaymentlog::class,
                'invoice_id' =>  $credit->id
            ];

            $this->bridgePayment($obj, $payment_data, $payment_data_items);

            $payment =  $this->addPayment($payment_data, $obj);

            $credit->payment_id = $payment->id;

            $credit->update();

            $credit->customer->updateCreditBalance();

            if($obj->online_credit_invoice !== "")
            {
                $in = Invoice::find($obj->online_credit_invoice);
                ProcessOrderService::sendBackPaymentConfirmedMessage($in->onliner_order_id);
                $in->online_order_debit = 0;
                $in->update();
            }

            $obj->alert(
                "success",
                "Credit Payment",
                [
                    'position' => 'center',
                    'timer' => 1500,
                    'toast' => false,
                    'text' =>  "Credit Payment has been added successfully!.",
                ]
            );

            $this->completePayment($obj,$payment->id);

            $obj->dispatch('openPaymentPage', ['link'=>route('payment.show',$payment->id)]);
        });
    }


    public function completePayment(&$obj, $payment_id)
    {
        if(isset($obj->invoice) && $obj->invoice->online_order_status == "1" )
        {
            ProcessOrderService::sendBackPaymentConfirmedMessage($obj->invoice->onliner_order_id);
            $in = Invoice::find($obj->invoice->id);
            $in->online_order_debit = 0;
            $in->update();
        }

        if(session()->get('current_route') == "payment.create"){

            $obj->dispatch('openPaymentPage', ['link'=>route('payment.show',$payment_id)]);
        }else{
            $obj->dispatch('invoiceDiscountModal', []);
            $obj->dispatch('refreshBrowser', []);
        }
    }


    public function savePayment(&$obj) : void
    {

        $payment_data_items = [];

        $payment_data = [
            'invoice_number' => $obj->invoice->invoice_number,
            'invoice_type' => get_class($obj->invoice),
            'subtotal' => $obj->sub_total,
            'customer_id' => $obj->invoice->customer_id,
            'invoice_id' => $obj->invoice->id,
            'payment_date' => $obj->invoice->invoice_date
        ];

        /*
         *  This logic was added for online invoice order, the issue is that when the system process the online order today and the customer did not make
         *  payment today and makes the payment the next day the system use the invoice date has the payment date which in turns does not make accounting
         *  easy for them at the end of the day
         */
        $returnOnlineOrder = \Cache::get("ReturnedOnlineOrder");
        if(!$returnOnlineOrder) $returnOnlineOrder = [];
        if(!is_null($obj->invoice->onliner_order_id) and !in_array($obj->invoice->id , $returnOnlineOrder)){
            $payment_data['payment_date'] = dailyDate();
            //this means that the invoice is not a return invoice instead its an invoice that is been paid for for the first time
        }else {
            // this is a return invoice and its an online invoice
            $payments = Payment::where('invoice_number', $obj->invoice->invoice_number)->first();
            if(!is_null($payments)){
                $payment_data['payment_date'] = $payments->payment_date;
            }
            // we still need to maintain the date that has been recorded before
        }
        /*
         * end of online payment login
         */

        $this->bridgePayment($obj, $payment_data, $payment_data_items);

        $payment = $this->addPayment($payment_data, $obj);

        $obj->alert(
            "success",
            "Add Payment",
            [
                'position' => 'center',
                'timer' => 1500,
                'toast' => false,
                'text' =>  "Payment has been added successfully!.",
            ]
        );

        $this->completePayment($obj,$payment->id);
    }



    public function deletePayment(Payment $payment)
    {
        if($payment->invoice_type == Invoice::class) {
            logActivity($payment->invoice->id,  $payment->invoice->invoice_number, "Invoice payment was deleted");
            $payment->invoice->wipePayment();
        }else{
            $payment->invoice()->delete();
            $payment->delete();
        }

    }


    public function validateDepositPayment(&$obj, $amount) : bool
    {
        $status =  $obj->customer->deposit_balance >= $amount;
        if($status === false)
        {
            $obj->error_deposit = "Insufficient Deposit Amount, Please Add more deposit to use this method of payment";
        }
        else {
            $obj->error_deposit = "";
        }
        return $status;
    }

}
