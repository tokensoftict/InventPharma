<?php

namespace App\Livewire\InvoiceAndSales\Discount;

use App\Jobs\AddLogToCustomerLedger;
use App\Models\Invoice;
use App\Models\Invoiceitem;
use Illuminate\Support\Arr;
use Livewire\Attributes\Validate;
use Livewire\Component;
use App\Traits\LivewireAlert;

class ApplyProductDiscountComponent extends Component
{
    use LivewireAlert;

    public Invoice $invoice;

    public String $_discounts;


    #[Validate([
        'discounts.*.discount_value' => 'required',
        'discounts.*.discount_type' => 'required',
    ])]

    public array $discounts;



    public function boot()
    {

    }

    public function mount()
    {

        $discounts = [];

        $user_id = auth()->id();

        foreach ($this->invoice->invoiceitems as $item)
        {
            $this->discounts[$item->id] = $item;

            $item->discount_added_by = $user_id;
            $discounts[$item->id] = Arr::only($item->toArray(), ['discount_value','quantity','discount_amount','discount_type','total_incentives','discount_added_by','id','cost_price','selling_price','total_selling_price','total_profit','profit']);
        }
        $this->_discounts = json_encode($discounts);

    }

    public function render()
    {

        return view('livewire.invoice-and-sales.discount.apply-product-discount-component');
    }


    public function applyProductDiscount()
    {
        $discounts = json_decode($this->_discounts ,true);
        $total = 0;
        foreach ($discounts as $discount)
        {
            if($discount['discount_type'] == "None")
            {
                Invoiceitem::find($discount['id'])->update([
                    'discount_type' => "None",
                    'discount_value' => 0,
                    'discount_amount' => 0,
                    'discount_added_by' => NULL,
                    'total_selling_price' => ($discount['quantity'] * $discount['selling_price']),
                    'profit' => ($discount['selling_price'] - $discount['cost_price']),
                    'total_profit' => ($discount['selling_price'] - $discount['cost_price']) * $discount['quantity']
                ]);
            }
            else {
                Invoiceitem::find($discount['id'])->update($discount);
            }
        }

        $this->invoice->update(
            [
                'sub_total' => $this->invoice->invoiceitems()->get()->sum(function($item){
                    return ($item->quantity * ($item->selling_price - $item->discount_amount));
                }),
                'status_id' => status('Draft')
            ]
        );

        $this->dispatch('refreshBrowser', ['link'=>route('invoiceandsales.view',$this->invoice->id)]);

        dispatch(new AddLogToCustomerLedger([
            'payment_id' => NULL,
            'invoice_id' =>  $this->invoice->id,
            'customer_id' =>  $this->invoice->customer_id,
            'amount' => -( $this->invoice->sub_total -  $this->invoice->discount_amount),
            'transaction_date' =>  $this->invoice->invoice_date,
            'user_id' =>  $this->invoice->last_updated_by,
        ]));

        $this->alert(
            "success",
            "Invoice",
            [
                'position' => 'center',
                'timer' => 1500,
                'toast' => false,
                'text' =>  "Discounts has been applied successfully!.",
            ]
        );

    }

}
