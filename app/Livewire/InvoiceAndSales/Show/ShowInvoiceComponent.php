<?php

namespace App\Livewire\InvoiceAndSales\Show;

use App\Jobs\AddLogToCustomerLedger;
use App\Models\Invoice;
use Livewire\Component;
use App\Traits\LivewireAlert;

class ShowInvoiceComponent extends Component
{
    use LivewireAlert;

    public Invoice $invoice;

    public String $discount_type = "Fixed";
    public String $discount_value;

    protected $listeners = ['InvoiceError' => 'handleInvoiceError'];

    public array $InvoiceErrors = [];

    public string $onlinewaybill = "";

    public string $onlineinvoice = "";

    public function boot()
    {

    }

    public function mount()
    {
        $this->discount_value = $this->invoice->discount_value;
        $this->discount_type = $this->invoice->discount_type;

        if($this->invoice->online_order_status == "1" && ($this->invoice->status_id === status('Complete'))){

            $order = _GET('view_order/'. $this->invoice->onliner_order_id);

            if($order){

               $this->onlinewaybill = $order['waybill'];

               $this->onlineinvoice = $order['invoiceurl'];

            }

        }

    }

    public function render()
    {
        return view('livewire.invoice-and-sales.show.show-invoice-component');
    }


    public function applyInvoiceDiscount()
    {
        $sub_total = 0;

        foreach($this->invoice->invoiceitems as $items)
        {
            $sub_total += ($items->quantity * $items->selling_price) - $items->discount_amount;
        }

        $this->invoice->status_id = status("Draft");
        $this->invoice->discount_type = $this->discount_type;
        $this->invoice->discount_value = $this->discount_value;

        if($this->discount_type == "None"){
            $this->invoice->sub_total = $sub_total;
            $this->invoice->total_profit = $sub_total -   $this->invoice->total_cost;
            $this->invoice->discount_amount = 0;
            $this->invoice->discount_value = 0;
            logActivity(  $this->invoice->id,   $this->invoice->invoice_number,'No Invoice discount was added / Invoice discount was removed : '.  $this->invoice->status->name);
        }else if($this->discount_type == "Percentage")
        {
            $sub = $this->invoice->sub_total;
            $dis_value = round(abs((($this->discount_value/100) * $sub)));
            $this->invoice->discount_amount = $dis_value;
            $this->invoice->total_profit = ($sub_total - $this->invoice->total_cost) - $dis_value;
            logActivity($this->invoice->id, $this->invoice->invoice_number,"Invoice percentage discount was applied percentage $this->discount_value % , discount value $dis_value : ".$this->invoice->status->name);
        }else{
            $this->invoice->discount_amount = $this->discount_value;
            LogActivity($this->invoice->id, $this->invoice->invoice_number,"Fixed discount was applied to invoice value : $this->discount_value ".$this->invoice->status);
        }

        $this->invoice->update();

        $this->dispatch('invoiceDiscountModal', []);
        $this->dispatch('refreshBrowser', []);

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
                'text' =>  "Invoice Discount has been applied successfully!.",
            ]
        );

    }


    public function handleInvoiceError(array $errors)
    {
        $this->dispatch('closeAddPaymentModal', []);
        $this->InvoiceErrors =$errors['errors'];

    }


    public function sendBackToDraft()
    {
        $this->invoice->status_id = status('Draft');
        $this->invoice->update();

        $this->alert(
            "success",
            "Invoice",
            [
                'position' => 'center',
                'timer' => 1500,
                'toast' => false,
                'text' =>  "Invoice Status has been updated to Draft successfully!.",
            ]
        );
    }


    public function requestForDiscount()
    {
        $this->invoice->status_id = status('Discount');
        $this->invoice->update();

        $this->alert(
            "success",
            "Invoice",
            [
                'position' => 'center',
                'timer' => 1500,
                'toast' => false,
                'text' =>  "Discount Request has been sent successfully!.",
            ]
        );
    }

}
