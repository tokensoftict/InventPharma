<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Paymentmethod;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\SupplierCreditPaymentHistory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StockForStockPaymentService
{
    /**
     * Retrieve eligible stock exchange invoices for a given supplier (and optional invoice number search).
     */
    public function getEligibleInvoices(?int $supplierId = null, ?string $search = null): Collection
    {
        return Invoice::query()
            ->eligibleForStockExchange($supplierId, $search)
            ->with(['supplier', 'status'])
            ->orderBy('id', 'DESC')
            ->get()
            ->filter(fn (Invoice $invoice) => $invoice->isEligibleForStockExchange($supplierId))
            ->values();
    }

    /**
     * Retrieve a structured summary of an invoice's stock exchange values and eligibility.
     */
    public function getInvoiceExchangeSummary(int|Invoice $invoice, ?int $supplierId = null): array
    {
        $invoiceModel = $invoice instanceof Invoice
            ? $invoice
            : Invoice::with(['supplier', 'status'])->find($invoice);

        if (!$invoiceModel) {
            throw ValidationException::withMessages([
                'invoice_id' => 'The selected invoice does not exist.',
            ]);
        }

        $originalValue = $invoiceModel->calculateOriginalTotal();
        $utilisedAmount = $invoiceModel->calculateTotalExchangeUtilised();
        $remainingBalance = $invoiceModel->calculateRemainingExchangeBalance();

        return [
            'invoice_id' => $invoiceModel->id,
            'invoice_number' => (string) $invoiceModel->invoice_number,
            'supplier_id' => $invoiceModel->supplier_id,
            'supplier_name' => $invoiceModel->supplier->name ?? 'N/A',
            'status_id' => $invoiceModel->status_id,
            'status_name' => status_name($invoiceModel->status_id),
            'is_stock_exchange' => $invoiceModel->isStockExchange(),
            'original_value' => $originalValue,
            'utilised_amount' => $utilisedAmount,
            'remaining_balance' => $remainingBalance,
            'is_eligible' => $invoiceModel->isEligibleForStockExchange($supplierId),
        ];
    }

    /**
     * Calculate the outstanding balance owed to a supplier (from negative credit balance).
     */
    public function getSupplierOutstandingBalance(int|Supplier $supplier): float
    {
        $supplierModel = $supplier instanceof Supplier
            ? $supplier->fresh(['supplier_credit_payment_histories'])
            : Supplier::with(['supplier_credit_payment_histories'])->find($supplier);

        if (!$supplierModel) {
            return 0.0;
        }

        $creditBalance = (float) $supplierModel->credit_balance;

        // A negative credit_balance means the company owes the supplier that amount
        return $creditBalance < 0 ? round(abs($creditBalance), 5) : 0.0;
    }

    /**
     * Resolve or create the "Stock for Stock" payment method ID.
     */
    public function resolveStockForStockPaymentMethodId(): ?int
    {
        $id = stock_for_stock_payment_method_id();
        if ($id !== null) {
            return $id;
        }

        if (Schema::hasTable('paymentmethods')) {
            $method = Paymentmethod::create([
                'name' => 'Stock for Stock',
                'status' => 1,
            ]);
            Cache::forget('paymentmethods');
            return (int) $method->id;
        }

        return null;
    }

    /**
     * Apply a Stock for Stock supplier payment from a stock exchange invoice to offset the supplier's negative balance
     * (and optionally link to a Purchase Order if purchase_id is provided).
     *
     * @throws ValidationException
     */
    public function applyStockForStockPayment(array $data): SupplierCreditPaymentHistory
    {
        return DB::transaction(function () use ($data) {
            $purchaseId = !empty($data['purchase_id']) ? (int) $data['purchase_id'] : null;
            $invoiceId = $data['invoice_id'] ?? null;
            $rawAmount = isset($data['amount']) ? Str::replace(',', '', (string) $data['amount']) : null;
            $amount = is_numeric($rawAmount) ? round((float) $rawAmount, 5) : 0.0;
            $supplierId = !empty($data['supplier_id']) ? (int) $data['supplier_id'] : null;
            $paymentDate = !empty($data['payment_date']) ? $data['payment_date'] : todaysDate();
            $userId = $data['user_id'] ?? auth()->id();
            $idempotencyKey = !empty($data['idempotency_key']) ? trim((string) $data['idempotency_key']) : null;
            $explicitReference = !empty($data['reference']) ? trim((string) $data['reference']) : null;
            $reference = $explicitReference ?: stock_exchange_payment_reference();
            $remark = $data['remark'] ?? null;

            if (empty($invoiceId)) {
                throw ValidationException::withMessages([
                    'invoice_id' => 'A stock exchange invoice must be selected for Stock for Stock payment.',
                ]);
            }

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'The applied exchange amount must be greater than zero.',
                ]);
            }

            // Prevent duplicate payment submissions
            if ($idempotencyKey !== null && Schema::hasColumn('supplier_credit_payment_histories', 'idempotency_key')) {
                $duplicateKeyExists = SupplierCreditPaymentHistory::where('idempotency_key', $idempotencyKey)
                    ->lockForUpdate()
                    ->exists();

                if ($duplicateKeyExists) {
                    throw ValidationException::withMessages([
                        'idempotency_key' => 'Duplicate payment submission detected.',
                    ]);
                }
            }

            if ($explicitReference !== null && Schema::hasColumn('supplier_credit_payment_histories', 'reference')) {
                $duplicateRefExists = SupplierCreditPaymentHistory::where('reference', $explicitReference)
                    ->where(function ($q) {
                        $q->whereNull('status')->orWhere('status', '!=', 'Reversed');
                    })
                    ->lockForUpdate()
                    ->exists();

                if ($duplicateRefExists) {
                    throw ValidationException::withMessages([
                        'reference' => 'A payment allocation with this reference already exists.',
                    ]);
                }
            }

            // If purchase_id is optionally provided, lock and validate the Purchase Order
            $purchase = null;
            if ($purchaseId !== null) {
                $purchase = Purchase::where('id', $purchaseId)->lockForUpdate()->first();
                if (!$purchase) {
                    throw ValidationException::withMessages([
                        'purchase_id' => 'The selected Purchase Order does not exist.',
                    ]);
                }

                SupplierCreditPaymentHistory::where('purchase_id', $purchase->id)->lockForUpdate()->get();

                if (is_null($purchase->supplier_id)) {
                    throw ValidationException::withMessages([
                        'purchase_id' => 'The selected Purchase Order does not have an associated supplier.',
                    ]);
                }

                if ($supplierId !== null && (int) $purchase->supplier_id !== $supplierId) {
                    throw ValidationException::withMessages([
                        'supplier_id' => 'The Purchase Order does not belong to the selected supplier.',
                    ]);
                }

                $supplierId = (int) $purchase->supplier_id;

                $ineligiblePoStatuses = array_values(array_filter([
                    status('Cancelled'),
                    status('Deleted'),
                    status('Declined'),
                    status('Pre-Draft'),
                    status('Draft'),
                ]));

                if (!is_null($purchase->status_id) && in_array((int) $purchase->status_id, $ineligiblePoStatuses, true)) {
                    throw ValidationException::withMessages([
                        'purchase_id' => 'The selected Purchase Order is not eligible to receive payment.',
                    ]);
                }

                $poTotal = $purchase->calculateTotalAmount();
                $poPaid = $purchase->calculateTotalPaid();
                $poOutstanding = max(0.0, round($poTotal - $poPaid, 5));

                if (round($poTotal, 2) <= 0 || round($poOutstanding, 2) <= 0) {
                    throw ValidationException::withMessages([
                        'purchase_id' => 'The selected Purchase Order has no outstanding balance to settle.',
                    ]);
                }

                if (round($amount, 2) > round($poOutstanding, 2)) {
                    throw ValidationException::withMessages([
                        'amount' => 'The applied amount (' . money($amount) . ') cannot exceed the Purchase Order outstanding balance (' . money($poOutstanding) . ').',
                    ]);
                }
            }

            // Lock and validate the Stock Exchange Invoice
            $invoice = Invoice::where('id', $invoiceId)->lockForUpdate()->first();
            if (!$invoice) {
                throw ValidationException::withMessages([
                    'invoice_id' => 'The selected stock exchange invoice does not exist.',
                ]);
            }

            if (!$invoice->isStockExchange()) {
                throw ValidationException::withMessages([
                    'invoice_id' => 'Normal sales invoices cannot be used for Stock for Stock payments. Only stock exchange invoices are eligible.',
                ]);
            }

            if ($invoice->isCancelledOrReversed()) {
                throw ValidationException::withMessages([
                    'invoice_id' => 'Cancelled, deleted, or reversed invoices cannot be used for Stock for Stock payments.',
                ]);
            }

            $exchangedStatusId = status('Exchanged');
            if ($exchangedStatusId !== null && (int) $invoice->status_id === (int) $exchangedStatusId) {
                throw ValidationException::withMessages([
                    'invoice_id' => 'Fully exchanged invoices cannot be selected again.',
                ]);
            }

            if (is_null($invoice->supplier_id)) {
                throw ValidationException::withMessages([
                    'invoice_id' => 'The selected stock exchange invoice is not associated with any supplier.',
                ]);
            }

            if ($supplierId === null) {
                throw ValidationException::withMessages([
                    'supplier_id' => 'Please select a supplier for this payment.',
                ]);
            }

            if ((int) $invoice->supplier_id !== $supplierId) {
                throw ValidationException::withMessages([
                    'invoice_id' => 'Stock exchange invoices can only be used for payments belonging to the same supplier.',
                ]);
            }

            // Lock all existing supplier credit/payment history rows for this supplier
            $supplier = Supplier::where('id', $supplierId)->lockForUpdate()->first();
            if (!$supplier) {
                throw ValidationException::withMessages([
                    'supplier_id' => 'The selected supplier does not exist.',
                ]);
            }
            $supplierHistories = SupplierCreditPaymentHistory::where('supplier_id', $supplierId)->lockForUpdate()->get();

            // If no specific purchase_id was given, check supplier's negative credit balance when credit entries exist
            if ($purchase === null && $supplierHistories->where('type', 'CREDIT')->isNotEmpty()) {
                $supplierOutstanding = $this->getSupplierOutstandingBalance($supplier);
                if (round($supplierOutstanding, 2) <= 0) {
                    throw ValidationException::withMessages([
                        'amount' => 'The supplier currently has no outstanding credit balance to offset.',
                    ]);
                }

                if (round($amount, 2) > round($supplierOutstanding, 2)) {
                    throw ValidationException::withMessages([
                        'amount' => 'The applied amount (' . money($amount) . ') cannot exceed the supplier outstanding balance (' . money($supplierOutstanding) . ').',
                    ]);
                }
            }

            // Lock all existing allocation rows for this invoice
            SupplierCreditPaymentHistory::where('invoice_id', $invoice->id)->lockForUpdate()->get();

            $originalTotal = $invoice->calculateOriginalTotal();
            $previousUtilised = $invoice->calculateTotalExchangeUtilised();
            $remainingExchangeBalance = max(0.0, round($originalTotal - $previousUtilised, 5));

            if (round($remainingExchangeBalance, 2) <= 0) {
                throw ValidationException::withMessages([
                    'invoice_id' => 'The selected stock exchange invoice has no remaining exchange balance.',
                ]);
            }

            if (round($amount, 2) > round($remainingExchangeBalance, 2)) {
                throw ValidationException::withMessages([
                    'amount' => 'The applied amount (' . money($amount) . ') cannot exceed the invoice remaining exchange balance (' . money($remainingExchangeBalance) . ').',
                ]);
            }

            $paymentMethodId = !empty($data['paymentmethod_id'])
                ? (int) $data['paymentmethod_id']
                : $this->resolveStockForStockPaymentMethodId();

            $newRemainingExchange = max(0.0, round($remainingExchangeBalance - $amount, 5));

            $defaultRemark = $purchase
                ? "Stock for Stock payment from Invoice #{$invoice->invoice_number} applied to Purchase Order #{$purchase->id}"
                : "Stock for Stock payment from Invoice #{$invoice->invoice_number}";

            // Create the Supplier Payment record linked to the Stock Exchange Invoice (offsets negative supplier balance)
            $allocation = SupplierCreditPaymentHistory::create([
                'user_id' => $userId,
                'supplier_id' => $supplierId,
                'type' => 'PAYMENT',
                'purchase_id' => $purchase?->id,
                'invoice_id' => $invoice->id,
                'paymentmethod_id' => $paymentMethodId,
                'reference' => $reference,
                'amount' => $amount,
                'status' => 'Active',
                'payment_date' => $paymentDate,
                'idempotency_key' => $idempotencyKey,
                'remark' => $remark ?: $defaultRemark,
                'payment_info' => [
                    'status' => 'Approved',
                    'method' => 'Stock for Stock',
                    'invoice_id' => $invoice->id,
                    'invoice_number' => (string) $invoice->invoice_number,
                    'purchase_id' => $purchase?->id,
                    'reference' => $reference,
                    'original_invoice_value' => $originalTotal,
                    'previously_utilised' => $previousUtilised,
                    'applied_amount' => $amount,
                    'remaining_exchange_balance' => $newRemainingExchange,
                ],
            ]);

            // Update the invoice's utilised exchange value and status
            $invoice->recalculateExchangeStatus();

            logActivity(
                $invoice->id,
                $invoice->invoice_number,
                "Stock for Stock supplier payment of " . money($amount) . " recorded for Supplier #{$supplierId} (Ref: {$reference}). Remaining exchange balance: " . money($invoice->remaining_exchange_balance) . ". Status: " . status_name($invoice->status_id)
            );

            return $allocation;
        });
    }

    /**
     * Record a Supplier / Purchase Order payment using any payment method (Cash, Transfer, Cheque, or Stock for Stock).
     *
     * @throws ValidationException
     */
    public function recordPurchaseOrderPayment(array $data): SupplierCreditPaymentHistory
    {
        $paymentMethodId = $data['paymentmethod_id'] ?? null;

        if (is_stock_for_stock_payment_method($paymentMethodId) || !empty($data['invoice_id'])) {
            return $this->applyStockForStockPayment($data);
        }

        return DB::transaction(function () use ($data, $paymentMethodId) {
            $rawAmount = isset($data['amount']) ? Str::replace(',', '', (string) $data['amount']) : null;
            $amount = is_numeric($rawAmount) ? round((float) $rawAmount, 5) : 0.0;
            $purchaseId = !empty($data['purchase_id']) ? (int) $data['purchase_id'] : null;
            $supplierId = !empty($data['supplier_id']) ? (int) $data['supplier_id'] : null;
            $paymentDate = !empty($data['payment_date']) ? $data['payment_date'] : todaysDate();
            $userId = $data['user_id'] ?? auth()->id();
            $idempotencyKey = !empty($data['idempotency_key']) ? trim((string) $data['idempotency_key']) : null;
            $explicitReference = !empty($data['reference']) ? trim((string) $data['reference']) : null;
            $reference = $explicitReference ?: ('PAY-' . date('YmdHis') . '-' . strtoupper(Str::random(6)));

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'The payment amount must be greater than zero.',
                ]);
            }

            if ($idempotencyKey !== null && Schema::hasColumn('supplier_credit_payment_histories', 'idempotency_key')) {
                $duplicateKeyExists = SupplierCreditPaymentHistory::where('idempotency_key', $idempotencyKey)
                    ->lockForUpdate()
                    ->exists();

                if ($duplicateKeyExists) {
                    throw ValidationException::withMessages([
                        'idempotency_key' => 'Duplicate payment submission detected.',
                    ]);
                }
            }

            if ($purchaseId !== null) {
                $purchase = Purchase::where('id', $purchaseId)->lockForUpdate()->first();
                if (!$purchase) {
                    throw ValidationException::withMessages([
                        'purchase_id' => 'The selected Purchase Order does not exist.',
                    ]);
                }

                SupplierCreditPaymentHistory::where('purchase_id', $purchase->id)->lockForUpdate()->get();

                if ($supplierId !== null && (int) $purchase->supplier_id !== $supplierId) {
                    throw ValidationException::withMessages([
                        'supplier_id' => 'The Purchase Order does not belong to the selected supplier.',
                    ]);
                }

                $supplierId = (int) $purchase->supplier_id;

                $ineligiblePoStatuses = array_values(array_filter([
                    status('Cancelled'),
                    status('Deleted'),
                    status('Declined'),
                    status('Pre-Draft'),
                    status('Draft'),
                ]));

                if (!is_null($purchase->status_id) && in_array((int) $purchase->status_id, $ineligiblePoStatuses, true)) {
                    throw ValidationException::withMessages([
                        'purchase_id' => 'The selected Purchase Order is not eligible to receive payment.',
                    ]);
                }

                $poOutstanding = $purchase->calculateOutstandingBalance();
                if (round($poOutstanding, 2) <= 0) {
                    throw ValidationException::withMessages([
                        'purchase_id' => 'The selected Purchase Order has no outstanding balance.',
                    ]);
                }

                if (round($amount, 2) > round($poOutstanding, 2)) {
                    throw ValidationException::withMessages([
                        'amount' => 'The payment amount (' . money($amount) . ') cannot exceed the Purchase Order outstanding balance (' . money($poOutstanding) . ').',
                    ]);
                }
            }

            if ($supplierId === null) {
                throw ValidationException::withMessages([
                    'supplier_id' => 'A supplier is required to record a payment.',
                ]);
            }

            $paymentInfo = $data['payment_info'] ?? ['status' => 'Approved'];
            if (!isset($paymentInfo['status'])) {
                $paymentInfo['status'] = ((string) $paymentMethodId === '8') ? 'Pending' : 'Approved';
            }

            return SupplierCreditPaymentHistory::create([
                'user_id' => $userId,
                'supplier_id' => $supplierId,
                'type' => 'PAYMENT',
                'purchase_id' => $purchaseId,
                'invoice_id' => null,
                'paymentmethod_id' => $paymentMethodId,
                'reference' => $reference,
                'amount' => $amount,
                'status' => 'Active',
                'payment_date' => $paymentDate,
                'idempotency_key' => $idempotencyKey,
                'payment_info' => $paymentInfo,
                'remark' => $data['remark'] ?? null,
            ]);
        });
    }

    /**
     * Record multiple payment methods (e.g., Cash + Transfer + Stock for Stock) atomically.
     *
     * @param int|Purchase|null $purchase
     * @param array $paymentLines
     * @param string|null $paymentDate
     * @param int|null $userId
     * @return Collection<int, SupplierCreditPaymentHistory>
     */
    public function recordCombinedPurchaseOrderPayments(
        int|Purchase|null $purchase,
        array $paymentLines,
        ?string $paymentDate = null,
        ?int $userId = null
    ): Collection {
        return DB::transaction(function () use ($purchase, $paymentLines, $paymentDate, $userId) {
            $purchaseModel = null;
            if ($purchase !== null) {
                $purchaseModel = $purchase instanceof Purchase
                    ? Purchase::where('id', $purchase->id)->lockForUpdate()->first()
                    : Purchase::where('id', $purchase)->lockForUpdate()->first();

                if (!$purchaseModel) {
                    throw ValidationException::withMessages([
                        'purchase_id' => 'The selected Purchase Order does not exist.',
                    ]);
                }
            }

            if (empty($paymentLines)) {
                throw ValidationException::withMessages([
                    'payments' => 'At least one payment entry is required.',
                ]);
            }

            $created = collect();

            foreach ($paymentLines as $line) {
                $lineData = array_merge($line, [
                    'purchase_id' => $line['purchase_id'] ?? $purchaseModel?->id,
                    'supplier_id' => $line['supplier_id'] ?? $purchaseModel?->supplier_id,
                    'payment_date' => $line['payment_date'] ?? $paymentDate ?? todaysDate(),
                    'user_id' => $line['user_id'] ?? $userId ?? auth()->id(),
                ]);

                $created->push($this->recordPurchaseOrderPayment($lineData));
            }

            return $created;
        });
    }

    /**
     * Reverse a Stock for Stock supplier payment allocation, restoring the invoice's exchange balance
     * and the supplier's credit balance while preserving audit history and without touching inventory.
     *
     * @throws ValidationException
     */
    public function reverseAllocation(
        int|SupplierCreditPaymentHistory $allocation,
        ?string $reason = null,
        ?int $userId = null
    ): SupplierCreditPaymentHistory {
        return DB::transaction(function () use ($allocation, $reason, $userId) {
            $allocationId = $allocation instanceof SupplierCreditPaymentHistory ? $allocation->id : (int) $allocation;

            $lockedAllocation = SupplierCreditPaymentHistory::where('id', $allocationId)
                ->lockForUpdate()
                ->first();

            if (!$lockedAllocation) {
                throw ValidationException::withMessages([
                    'allocation' => 'The payment allocation record was not found.',
                ]);
            }

            if ($lockedAllocation->type !== 'PAYMENT') {
                throw ValidationException::withMessages([
                    'allocation' => 'Only payment allocation records can be reversed.',
                ]);
            }

            if ($lockedAllocation->isReversed()) {
                throw ValidationException::withMessages([
                    'allocation' => 'This payment allocation has already been reversed.',
                ]);
            }

            $invoice = null;
            if (!is_null($lockedAllocation->invoice_id)) {
                $invoice = Invoice::where('id', $lockedAllocation->invoice_id)->lockForUpdate()->first();
            }

            if (!is_null($lockedAllocation->purchase_id)) {
                Purchase::where('id', $lockedAllocation->purchase_id)->lockForUpdate()->first();
            }

            $actingUserId = $userId ?? auth()->id();
            $reversalReason = trim((string) ($reason ?? 'Reversed by user')) ?: 'Reversed by user';
            $reversedAt = now();

            $paymentInfo = is_array($lockedAllocation->payment_info) ? $lockedAllocation->payment_info : [];
            $paymentInfo['status'] = 'Reversed';
            $paymentInfo['reversed_at'] = $reversedAt->toDateTimeString();
            $paymentInfo['reversed_by'] = $actingUserId;
            $paymentInfo['reversal_reason'] = $reversalReason;

            $lockedAllocation->status = 'Reversed';
            $lockedAllocation->reversed_at = $reversedAt;
            $lockedAllocation->reversed_by = $actingUserId;
            $lockedAllocation->reversal_reason = $reversalReason;
            $lockedAllocation->payment_info = $paymentInfo;
            $lockedAllocation->save();

            if ($invoice) {
                $invoice->recalculateExchangeStatus();

                logActivity(
                    $invoice->id,
                    $invoice->invoice_number,
                    "Stock for Stock supplier payment #{$lockedAllocation->id} of " . money($lockedAllocation->amount) . " was reversed. Reason: {$reversalReason}. Restored remaining exchange balance: " . money($invoice->remaining_exchange_balance) . ". Status: " . status_name($invoice->status_id)
                );
            }

            return $lockedAllocation->fresh(['invoice', 'purchase', 'supplier', 'user', 'reversedByUser']);
        });
    }
}
