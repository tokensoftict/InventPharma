<?php

namespace App\Livewire\PaymentManager;

use App\Models\Invoice;
use App\Models\Payment;
use App\Repositories\PaymentRepository;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use App\Traits\LivewireAlert;

class ViewpaymentComponent extends Component
{
    use LivewireAlert;


    public Payment $payment;

    private PaymentRepository $paymentRepository;

    public function boot(PaymentRepository $paymentRepository)
    {
        $this->paymentRepository = $paymentRepository;
    }

    public function mount()
    {

    }

    public function render()
    {
        return view('livewire.payment-manager.viewpayment-component');
    }


    public function deletePayment()
    {

        DB::transaction(function(){
            $this->paymentRepository->deletePayment($this->payment);
        });

        $this->dispatch('invoiceDiscountModal', []);
        $this->dispatch('refreshBrowser', []);
        $this->alert(
            "success",
            "Show Payment",
            [
                'position' => 'center',
                'timer' => 1500,
                'toast' => false,
                'text' =>  "Payment has been deleted successfully!.",
            ]
        );

    }

}
