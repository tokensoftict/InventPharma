<?php

namespace App\Livewire\PurchaseOrder\Supplier\Payment;

use App\Models\SupplierCreditPaymentHistory;
use Livewire\Component;
use App\Traits\LivewireAlert;

class SupplierPaymentDetailComponent extends Component
{
    use LivewireAlert;

    public SupplierCreditPaymentHistory $payment;

    public function approve()
    {
        $payment_info = $this->payment->payment_info;
        $payment_info['status'] = 'Approved';
        $this->payment->payment_info = $payment_info;
        $this->payment->save();

        $this->alert(
            "success",
            "Cheque Approval",
            [
                'position' => 'center',
                'timer' => 6000,
                'toast' => false,
                'text' => 'Cheque payment approved successfully!',
            ]
        );
        $this->payment = $this->payment->fresh();
    }

    public function decline()
    {
        $payment_info = $this->payment->payment_info;
        $payment_info['status'] = 'Declined';
        $this->payment->payment_info = $payment_info;
        $this->payment->save();

        $this->alert(
            "warning",
            "Cheque Approval",
            [
                'position' => 'center',
                'timer' => 6000,
                'toast' => false,
                'text' => 'Cheque payment declined successfully!',
            ]
        );
        $this->payment = $this->payment->fresh();
    }

    public function render()
    {
        return view('livewire.purchase-order.supplier.payment.supplier-payment-detail-component');
    }
}
