<?php

namespace App\Livewire\PurchaseOrder\Supplier\Payment;

use App\Models\SupplierCreditPaymentHistory;
use App\Repositories\PurchaseOrderRepository;
use Illuminate\Support\Arr;
use Livewire\Component;
use App\Traits\LivewireAlert;

class SupplierPaymentComponent extends Component
{
    use LivewireAlert;

    public SupplierCreditPaymentHistory $supplierCreditPaymentHistory;

    public $paymentMethods;
    public $suppliers;

    public $payment_data = [];

    public function boot()
    {

    }

    public function booted()
    {
        $this->paymentMethods = paymentmethodsOnly([1,2,3,8]);
        $this->suppliers = suppliers(true);
    }

    public function mount()
    {
        $fields = [
            'user_id' => auth()->id(),
            'supplier_id' => NULL,
            'type' => NULL,
            'purchase_id' => NULL,
            'paymentmethod_id' => NULL,
            'payment_info' => ['cheque_date' => NULL],
            'amount' => NULL,
            'remark' => NULL,
            'payment_date' => NULL,
            'cheque_date' => NULL
        ];

        if(isset($this->supplierCreditPaymentHistory->id))
        {
            $this->payment_data = Arr::only( $this->supplierCreditPaymentHistory->toArray(), array_keys($fields));
        }
        else {
            $this->payment_data = $fields;
        }
    }

    public function render()
    {
        return view('livewire.purchase-order.supplier.payment.supplier-payment-component');
    }


    public function savePayment()
    {
        $data = [
            "payment_data.payment_date"=>"bail|required",
            "payment_data.amount"=>"bail|required",
            "payment_data.supplier_id"=>"bail|required",
            "payment_data.paymentmethod_id"=>"bail|required",
        ];




        $this->validate($data);

        if(!isset($this->supplierCreditPaymentHistory->id)) {
            $message = "created";
            $this->payment_data['user_id'] = auth()->id();
            PurchaseOrderRepository::createSupplierPaymentHistory($this->payment_data);
        }else{
            $message = "updated";
            PurchaseOrderRepository::updateSupplierPaymentHistory($this->supplierCreditPaymentHistory->id, $this->payment_data);
        }

        $this->alert(
            "success",
            "Product",
            [
                'position' => 'center',
                'timer' => 12000,
                'toast' => false,
                'text' =>  "Payment has been ".$message." successfully!.",
            ]
        );


        return redirect()->route('supplier.payment.index');
    }

}
