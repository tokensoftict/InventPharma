<?php

namespace App\View\Components;

use Illuminate\View\Component;

class createPaymentComponent extends Component
{

    public $invoice;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($invoice)
    {
        $this->invoice = $invoice;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        $data = [
            'invoice' => $this->invoice
        ];


        return view('components.create-payment-component', $data);
    }
}
