<?php

namespace App\View\Components;

use App\Models\Payment;
use Illuminate\View\Component;

class ShowPaymentComponent extends Component
{

    Public Payment $payment;
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        $data = [
            'payment' => $this->payment
        ];
        return view('components.show-payment-component', $data);
    }
}
