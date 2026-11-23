<?php

namespace App\Livewire\PurchaseOrder\Supplier\Payment;

use App\Models\Invoice;
use App\Models\Supplier;
use App\Models\SupplierCreditPaymentHistory;
use App\Repositories\PurchaseOrderRepository;
use App\Services\StockForStockPaymentService;
use App\Traits\LivewireAlert;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class SupplierPaymentComponent extends Component
{
    use LivewireAlert;

    public SupplierCreditPaymentHistory $supplierCreditPaymentHistory;

    public $paymentMethods;
    public $suppliers;
    public $banks;

    public $payment_data = [];

    public string $invoiceSearch = '';

    public bool $showConfirmation = false;

    public function boot()
    {
        //
    }

    public function booted()
    {
        $sfsId = stock_for_stock_payment_method_id();
        $this->paymentMethods = paymentmethodsOnly(array_values(array_filter([
            1, 2, 3, 8, $sfsId, 'Stock for Stock', 'STOCK FOR STOCK',
        ])));
        $this->suppliers = suppliers(true);
        $this->banks = Schema::hasTable('banks') ? banks()->filter(fn ($b) => !isset($b->status) || (int) $b->status === 1)->values() : collect();
    }

    public function mount()
    {
        $defaultPaymentInfo = [
            'cheque_date' => null,
            'date_of_issued' => null,
            'status' => null,
            'bank_name' => null,
            'account_number' => null,
            'account_name' => null,
            'cash_received_by' => null,
        ];

        $fields = [
            'user_id' => auth()->id(),
            'supplier_id' => request('supplier_id') ?: null,
            'type' => null,
            'purchase_id' => null,
            'invoice_id' => request('invoice_id') ?: null,
            'paymentmethod_id' => null,
            'reference' => null,
            'payment_info' => $defaultPaymentInfo,
            'amount' => null,
            'remark' => null,
            'payment_date' => todaysDate(),
            'cheque_date' => null,
            'idempotency_key' => (string) Str::uuid(),
        ];

        if (isset($this->supplierCreditPaymentHistory->id)) {
            $this->payment_data = array_merge(
                $fields,
                Arr::only($this->supplierCreditPaymentHistory->toArray(), array_keys($fields))
            );
            $this->payment_data['payment_info'] = array_merge(
                $defaultPaymentInfo,
                $this->payment_data['payment_info'] ?? []
            );
        } else {
            $this->payment_data = $fields;
        }
    }

    public function updatedPaymentDataSupplierId($value)
    {
        $this->payment_data['invoice_id'] = null;
        $this->showConfirmation = false;
    }

    public function updatedPaymentDataPaymentmethodId($value)
    {
        if (!$this->isStockForStockSelected()) {
            $this->payment_data['invoice_id'] = null;
        }
        if (!$this->isTransferSelected()) {
            $this->payment_data['payment_info']['bank_name'] = null;
            $this->payment_data['payment_info']['account_number'] = null;
            $this->payment_data['payment_info']['account_name'] = null;
        }
        if (!$this->isCashSelected()) {
            $this->payment_data['payment_info']['cash_received_by'] = null;
        }
        if ((string) $value !== '8') {
            $this->payment_data['payment_info']['cheque_date'] = null;
        }
        $this->showConfirmation = false;
    }

    public function updatedPaymentDataInvoiceId($value)
    {
        $this->showConfirmation = false;
        if (!empty($value)) {
            $invoice = Invoice::find($value);
            if ($invoice) {
                $maxApplicable = $invoice->calculateRemainingExchangeBalance();
                if (!empty($this->payment_data['supplier_id'])) {
                    $supplierOutstanding = app(StockForStockPaymentService::class)
                        ->getSupplierOutstandingBalance((int) $this->payment_data['supplier_id']);
                    if ($supplierOutstanding > 0) {
                        $maxApplicable = min($maxApplicable, $supplierOutstanding);
                    }
                }
                if (empty($this->payment_data['amount']) && $maxApplicable > 0) {
                    $this->payment_data['amount'] = round($maxApplicable, 2);
                }
            }
        }
    }

    public function isStockForStockSelected(): bool
    {
        return is_stock_for_stock_payment_method($this->payment_data['paymentmethod_id'] ?? null);
    }

    protected function selectedPaymentMethodName(): string
    {
        $id = $this->payment_data['paymentmethod_id'] ?? null;
        if (empty($id) || !$this->paymentMethods) {
            return '';
        }

        $method = collect($this->paymentMethods)->first(fn ($m) => (string) $m->id === (string) $id);

        return strtolower(trim((string) ($method->name ?? '')));
    }

    public function isTransferSelected(): bool
    {
        $name = $this->selectedPaymentMethodName();

        return $name !== ''
            ? str_contains($name, 'transfer')
            : (string) ($this->payment_data['paymentmethod_id'] ?? '') === '3';
    }

    public function isCashSelected(): bool
    {
        $name = $this->selectedPaymentMethodName();

        return $name !== ''
            ? $name === 'cash'
            : (string) ($this->payment_data['paymentmethod_id'] ?? '') === '1';
    }

    public function getEligibleStockExchangeInvoicesProperty()
    {
        $supplierId = !empty($this->payment_data['supplier_id']) ? (int) $this->payment_data['supplier_id'] : null;
        if (!$supplierId) {
            return collect();
        }

        return app(StockForStockPaymentService::class)
            ->getEligibleInvoices($supplierId, $this->invoiceSearch);
    }

    public function getSelectedSupplierSummaryProperty(): ?array
    {
        $supplierId = !empty($this->payment_data['supplier_id']) ? (int) $this->payment_data['supplier_id'] : null;
        if (!$supplierId) {
            return null;
        }

        $supplier = Supplier::with(['supplier_credit_payment_histories'])->find($supplierId);
        if (!$supplier) {
            return null;
        }

        $creditBalance = (float) $supplier->credit_balance;
        $outstandingOwed = $creditBalance < 0 ? round(abs($creditBalance), 2) : 0.0;
        $proposedAmount = $this->getProposedAmount();

        return [
            'supplier_id' => $supplier->id,
            'supplier_name' => $supplier->name,
            'credit_balance' => round($creditBalance, 2),
            'outstanding_balance' => $outstandingOwed,
            'proposed_amount' => $proposedAmount,
            'credit_balance_after_payment' => round($creditBalance + $proposedAmount, 2),
            'remaining_owed_after_payment' => max(0.0, round($outstandingOwed - $proposedAmount, 2)),
        ];
    }

    public function getSelectedInvoiceSummaryProperty(): ?array
    {
        $invoiceId = $this->payment_data['invoice_id'] ?? null;
        if (empty($invoiceId)) {
            return null;
        }

        $invoice = Invoice::with(['supplier', 'status'])->find($invoiceId);
        if (!$invoice) {
            return null;
        }

        $supplierId = !empty($this->payment_data['supplier_id']) ? (int) $this->payment_data['supplier_id'] : null;
        $summary = app(StockForStockPaymentService::class)->getInvoiceExchangeSummary($invoice, $supplierId);
        $proposedAmount = $this->getProposedAmount();
        $summary['proposed_amount'] = $proposedAmount;
        $summary['remaining_after_allocation'] = max(0.0, round($summary['remaining_balance'] - $proposedAmount, 2));

        return $summary;
    }

    protected function getProposedAmount(): float
    {
        $raw = isset($this->payment_data['amount'])
            ? Str::replace(',', '', (string) $this->payment_data['amount'])
            : '0';

        return is_numeric($raw) && (float) $raw > 0 ? round((float) $raw, 5) : 0.0;
    }

    public function previewConfirmation()
    {
        $this->validatePaymentInput();
        $this->showConfirmation = true;
    }

    public function cancelConfirmation()
    {
        $this->showConfirmation = false;
    }

    protected function validatePaymentInput(): void
    {
        $rules = [
            'payment_data.payment_date' => 'bail|required',
            'payment_data.amount' => 'bail|required',
            'payment_data.supplier_id' => 'bail|required',
            'payment_data.paymentmethod_id' => 'bail|required',
        ];

        if (isset($this->payment_data['paymentmethod_id']) && (string) $this->payment_data['paymentmethod_id'] === '8') {
            $rules['payment_data.payment_info.cheque_date'] = 'bail|required';
        }

        if ($this->isTransferSelected()) {
            $rules['payment_data.payment_info.bank_name'] = 'bail|required|string|max:255';
            $rules['payment_data.payment_info.account_number'] = 'bail|required|string|max:50';
            $rules['payment_data.payment_info.account_name'] = 'bail|required|string|max:255';
        }

        if ($this->isCashSelected()) {
            $rules['payment_data.payment_info.cash_received_by'] = 'bail|required|string|max:255';
        }

        if ($this->isStockForStockSelected()) {
            $rules['payment_data.invoice_id'] = 'bail|required';
        }

        $this->validate($rules, [
            'payment_data.invoice_id.required' => 'Please select an eligible Stock Exchange Invoice.',
            'payment_data.payment_info.bank_name.required' => 'Please select a Bank Name.',
            'payment_data.payment_info.account_number.required' => 'Please enter the Account Number.',
            'payment_data.payment_info.account_name.required' => 'Please enter the Account Name.',
            'payment_data.payment_info.cash_received_by.required' => 'Please enter who received the cash.',
        ]);
    }

    public function render()
    {
        return view('livewire.purchase-order.supplier.payment.supplier-payment-component', [
            'eligibleStockExchangeInvoices' => $this->eligibleStockExchangeInvoices,
            'selectedInvoiceSummary' => $this->selectedInvoiceSummary,
            'selectedSupplierSummary' => $this->selectedSupplierSummary,
            'isStockForStock' => $this->isStockForStockSelected(),
            'isTransfer' => $this->isTransferSelected(),
            'isCash' => $this->isCashSelected(),
        ]);
    }

    public function savePayment()
    {
        $this->validatePaymentInput();

        if (isset($this->payment_data['paymentmethod_id']) && (string) $this->payment_data['paymentmethod_id'] === '8') {
            if (!isset($this->supplierCreditPaymentHistory->id)) {
                $this->payment_data['payment_info']['status'] = 'Pending';
                $this->payment_data['payment_info']['going_out_date'] = (new Carbon($this->payment_data['payment_info']['cheque_date']))->addDay()->format('Y-m-d');
            }
        } else {
            $this->payment_data['payment_info']['status'] = 'Approved';
        }

        try {
            if (!isset($this->supplierCreditPaymentHistory->id)) {
                $message = 'created';
                $this->payment_data['user_id'] = auth()->id();
                PurchaseOrderRepository::createSupplierPaymentHistory($this->payment_data);
            } else {
                $message = 'updated';
                PurchaseOrderRepository::updateSupplierPaymentHistory($this->supplierCreditPaymentHistory->id, $this->payment_data);
            }
        } catch (ValidationException $e) {
            $mapped = [];
            foreach ($e->errors() as $key => $messages) {
                $mapped[$key] = $messages;
                if (!str_starts_with($key, 'payment_data.')) {
                    $mapped['payment_data.' . $key] = $messages;
                }
            }
            throw ValidationException::withMessages($mapped);
        }

        $this->showConfirmation = false;

        $this->alert(
            'success',
            'Supplier Payment',
            [
                'position' => 'center',
                'timer' => 12000,
                'toast' => false,
                'text' => 'Payment has been ' . $message . ' successfully!.',
            ]
        );

        return redirect()->route('supplier.payment.index');
    }
}
