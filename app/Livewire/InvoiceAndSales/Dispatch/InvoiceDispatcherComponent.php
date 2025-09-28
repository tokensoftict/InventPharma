<?php

namespace App\Livewire\InvoiceAndSales\Dispatch;

use App\Models\Invoice;
use App\Models\User;
use App\Models\WaitingCustomer;
use App\Services\Online\ProcessOrderService;
use Livewire\Component;
use App\Traits\LivewireAlert;

class InvoiceDispatcherComponent extends Component
{
    use LivewireAlert;

    public Invoice $invoice;

    public $users;

    public array $data;

    public function boot()
    {

    }

    public function mount()
    {
        $invoiceDispatcher = config('app.invoice_dispatcher', 11);
        if($invoiceDispatcher === false) {
            $invoiceDispatcher = 11;
        }
        $this->users = User::where('usergroup_id',$invoiceDispatcher)->where('status',1)->get();

        $this->data['picked_by'] = "";
        $this->data['checked_by'] = "";
        $this->data['packed_by'] = "";
        $this->data['dispatched_by'] = "";
        $this->data['carton_no'] = "";

    }

    public function render()
    {
        return view('livewire.invoice-and-sales.dispatch.invoice-dispatcher-component');
    }

    public function dispatchedInvoice()
    {
        $this->validate(
            [
                'data.picked_by' => 'required',
                'data.checked_by' => 'required',
                'data.packed_by' => 'required',
                'data.dispatched_by' => 'required',
                'data.carton_no' => 'required'
            ]
        );

        $this->data['status_id'] = status('Complete');

        $this->invoice->update($this->data);

        if(isset($this->invoice->waitingCustomer->status)) {
            $this->invoice->waitingCustomer->status = WaitingCustomer::$waitingInvoiceStatus['dispatched'];
            $this->invoice->waitingCustomer->save();

            addCustomerWaitingListStatusHistory($this->invoice->waitingCustomer, 'dispatched');
        }

        $this->dispatch('refreshBrowser', ['link'=>route('invoiceandsales.view',$this->invoice->id)]);

        logActivity($this->invoice->id, $this->invoice->invoice_number,'Invoice dispatched');

        if($this->invoice->online_order_status == "1"){

            ProcessOrderService::sendBackOrderDispatchedMessage($this->invoice->onliner_order_id, $this->invoice->invoice_number, $this->invoice->carton_no);
            logActivity($this->invoice->id, $this->invoice->invoice_number,'Online invoice -- dispatched update was sent to the serve');
        }


        $this->alert(
            "success",
            "Invoice Dispatcher",
            [
                'position' => 'center',
                'timer' => 1500,
                'toast' => false,
                'text' =>  "Invoice has been dispatched successfully, Invoice is now completed!",
            ]
        );

    }

}
