<?php

namespace App\View\Components;

use App\Models\Invoice;
use Illuminate\View\Component;

class DispatchInvoiceComponent extends Component
{

    public Invoice $invoice;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(Invoice $invoice)
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
        return view('components.dispatch-invoice-component', $data);
    }
}
