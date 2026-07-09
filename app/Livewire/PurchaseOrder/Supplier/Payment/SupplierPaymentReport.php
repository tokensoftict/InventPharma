<?php

namespace App\Livewire\PurchaseOrder\Supplier\Payment;

use App\Models\SupplierCreditPaymentHistory;
use Livewire\Component;
use App\Traits\LivewireAlert;
use Illuminate\Support\Carbon;

class SupplierPaymentReport extends Component
{
    use LivewireAlert;

    public $date_filter = 'Today';
    public $start_date;
    public $end_date;

    public function render()
    {
        $query = SupplierCreditPaymentHistory::query()
            ->with(['user', 'paymentmethod', 'purchase', 'supplier', 'supplier.supplierStockOpening'])
            ->where('paymentmethod_id', 8);

        switch ($this->date_filter) {
            case 'Today':
                $query->whereDate('payment_info->cheque_date', Carbon::today());
                break;
            case 'This Week':
                $query->whereBetween('payment_info->cheque_date', [Carbon::now()->startOfWeek()->format('Y-m-d'), Carbon::now()->endOfWeek()->format('Y-m-d')]);
                break;
            case 'This Month':
                $query->whereBetween('payment_info->cheque_date', [Carbon::now()->startOfMonth()->format('Y-m-d'), Carbon::now()->endOfMonth()->format('Y-m-d')]);
                break;
            case 'Custom':
                if ($this->start_date && $this->end_date) {
                    $query->whereBetween('payment_info->cheque_date', [
                        Carbon::parse($this->start_date)->format('Y-m-d'),
                        Carbon::parse($this->end_date)->format('Y-m-d')
                    ]);
                }
                break;
        }

        $payments = $query->orderBy('payment_info->cheque_date', 'ASC')->get();
        $grand_total = $payments->filter(fn($payment) => (($payment->payment_info['status'] ?? 'Pending') === 'Approved' || ($payment->payment_info['status'] ?? 'Pending') === 'Pending'))->sum('amount');

        $total_pending = $payments->filter(function ($payment) {
            $status = $payment->payment_info['status'] ?? 'Pending';
            return $status === 'Pending';
        })->sum('amount');

        $total_approved = $payments->filter(function ($payment) {
            $status = $payment->payment_info['status'] ?? 'Pending';
            return $status === 'Approved';
        })->sum('amount');

        $grouped_payments = $payments->groupBy(fn($payment) => $payment->payment_info['cheque_date'] ?? $payment->payment_date->format('Y-m-d'));

        return view('livewire.purchase-order.supplier.payment.supplier-payment-report', [
            'grouped_payments' => $grouped_payments,
            'grand_total' => $grand_total,
            'total_pending' => $total_pending,
            'total_approved' => $total_approved
        ]);
    }

    public function approve($id)
    {
        $payment = SupplierCreditPaymentHistory::findOrFail($id);
        $payment_info = $payment->payment_info;
        $payment_info['status'] = 'Approved';
        $payment_info['approved_date'] = date('Y-m-d');
        $payment->payment_info = $payment_info;
        $payment->save();

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
    }

    public function decline($id)
    {
        $payment = SupplierCreditPaymentHistory::findOrFail($id);
        $payment_info = $payment->payment_info;
        $payment_info['status'] = 'Declined';
        $payment_info['decline_date'] = date('Y-m-d');
        $payment->payment_info = $payment_info;
        $payment->save();

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
    }
}
