<?php

namespace App\Livewire\PaymentManager;

use App\Models\Creditpaymentlog;
use App\Models\Customer;
use App\Models\Invoice;
use App\Traits\LivewireAlert;
use App\Traits\PaymentComponentTrait;
use Livewire\Component;

class AddcreditComponent extends Component
{
    use PaymentComponentTrait, LivewireAlert;

    public $amount = 0;

    public $online_credit_invoice = "";

    public int $customer_id = 0;

    public Customer $customer;

    public $pendingOnlineInvoices;

    protected $listeners = [
        'generateCreditPayment' => 'generateCreditPayment',
        'generateInvoicePayment' => 'generateInvoicePayment',
    ];


    public function mount()
    {
        $this->pendingOnlineInvoices = Invoice::where('customer_id', $this->customer->id)->where('online_order_debit', 1)->get();
    }


    public function booted()
    {
        $this->mountProperties();

        $this->payments = $this->payments->filter(function($payment){
            return ($payment->id !== 4 && $payment->id !== 5);
        });

        $this->sub_total = $this->amount;

        $this->customer_id = $this->customer->id;

        $this->totalCredit = Creditpaymentlog::where('customer_id',$this->customer_id)->sum('amount');
    }

    public function render()
    {
        $this->updateDisplay($this);

        return view('livewire.payment-manager.addcredit-component');
    }
}
