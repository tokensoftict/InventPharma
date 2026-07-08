<?php

namespace App\Livewire\PurchaseOrder\Supplier\Payment;

use App\Models\SupplierCreditPaymentHistory;
use App\Repositories\PurchaseOrderRepository;
use Illuminate\Support\Arr;
use Livewire\Component;
use App\Traits\LivewireAlert;
use Illuminate\Support\Carbon;

class SupplierPaymentComponent extends Component
{
    use LivewireAlert;

    public SupplierCreditPaymentHistory $supplierCreditPaymentHistory;

    public $paymentMethods;
    public $suppliers;

    public $payment_data = [];

    // Report Properties
    public $is_report = false;
    public $date_filter = 'Today';
    public $start_date;
    public $end_date;

    public function boot()
    {

    }

    public function booted()
    {
        if (!$this->is_report) {
            $this->paymentMethods = paymentmethodsOnly([1, 2, 3, 8]);
            $this->suppliers = suppliers(true);
        }
    }

    public function mount()
    {
        if (!$this->is_report) {
            $fields = [
                'user_id' => auth()->id(),
                'supplier_id' => NULL,
                'type' => NULL,
                'purchase_id' => NULL,
                'paymentmethod_id' => NULL,
                'payment_info' => ['cheque_date' => NULL, 'date_of_issued' => NULL, 'status' => NULL],
                'amount' => NULL,
                'remark' => NULL,
                'payment_date' => NULL,
                'cheque_date' => NULL
            ];

            if (isset($this->supplierCreditPaymentHistory->id)) {
                $this->payment_data = Arr::only($this->supplierCreditPaymentHistory->toArray(), array_keys($fields));
                $this->payment_data['payment_info'] = array_merge(
                    ['cheque_date' => NULL, 'date_of_issued' => NULL, 'status' => NULL],
                    $this->payment_data['payment_info'] ?? []
                );
            } else {
                $this->payment_data = $fields;
            }
        }
    }

    public function render()
    {
        if ($this->is_report) {
            $query = SupplierCreditPaymentHistory::query()
                ->with(['user', 'paymentmethod', 'purchase', 'supplier'])
                ->where('paymentmethod_id', 8);

            switch ($this->date_filter) {
                case 'Today':
                    $query->whereDate('cheque_date', Carbon::today());
                    break;
                case 'This Week':
                    $query->whereBetween('cheque_date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                    break;
                case 'This Month':
                    $query->whereBetween('cheque_date', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
                    break;
                case 'Custom':
                    if ($this->start_date && $this->end_date) {
                        $query->whereBetween('cheque_date', [
                            Carbon::parse($this->start_date)->startOfDay(),
                            Carbon::parse($this->end_date)->endOfDay()
                        ]);
                    }
                    break;
            }

            $payments = $query->orderBy('cheque_date', 'ASC')->get();
            $grand_total = $payments->sum('amount');
            
            $total_pending = $payments->filter(function($payment) {
                $status = $payment->payment_info['status'] ?? 'Pending';
                return $status === 'Pending';
            })->sum('amount');

            $total_approved = $payments->filter(function($payment) {
                $status = $payment->payment_info['status'] ?? 'Pending';
                return $status === 'Approved';
            })->sum('amount');

            $grouped_payments = $payments->groupBy(fn($payment) => $payment->payment_date->format('Y-m-d'));

            return view('livewire.purchase-order.supplier.payment.supplier-payment-report', [
                'grouped_payments' => $grouped_payments,
                'grand_total' => $grand_total,
                'total_pending' => $total_pending,
                'total_approved' => $total_approved
            ]);
        }

        return view('livewire.purchase-order.supplier.payment.supplier-payment-component');
    }

    public function savePayment()
    {
        $data = [
            "payment_data.payment_date" => "bail|required",
            "payment_data.amount" => "bail|required",
            "payment_data.supplier_id" => "bail|required",
            "payment_data.paymentmethod_id" => "bail|required",
        ];

        if (isset($this->payment_data['paymentmethod_id']) && $this->payment_data['paymentmethod_id'] == "8") {
            $data["payment_data.payment_info.cheque_date"] = "bail|required";
            $data["payment_data.payment_info.date_of_issued"] = "bail|required";
        }

        $this->validate($data);

        if (isset($this->payment_data['paymentmethod_id']) && $this->payment_data['paymentmethod_id'] == "8") {
            if (!isset($this->supplierCreditPaymentHistory->id)) {
                $this->payment_data['payment_info']['status'] = 'Pending';
            }
        } else {
            $this->payment_data['payment_info']['status'] = 'Approved';
        }

        if (!isset($this->supplierCreditPaymentHistory->id)) {
            $message = "created";
            $this->payment_data['user_id'] = auth()->id();
            PurchaseOrderRepository::createSupplierPaymentHistory($this->payment_data);
        } else {
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
                'text' => "Payment has been " . $message . " successfully!.",
            ]
        );

        return redirect()->route('supplier.payment.index');
    }

    public function approve($id)
    {
        $payment = SupplierCreditPaymentHistory::findOrFail($id);
        $payment_info = $payment->payment_info;
        $payment_info['status'] = 'Approved';
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
