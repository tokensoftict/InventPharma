<?php

namespace App\Models;

use App\Traits\ModelFilterTraits;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class InvoiceReturn extends Model
{
    use ModelFilterTraits;

    public const REASON_CASHIER_MISTAKE = 'Cashier / Sales Order made a mistake during invoicing.';
    public const REASON_CUSTOMER_RETURNED = 'Customer returned item(s) on the invoice.';
    public const REASON_OTHER = 'Other reason.';

    public const ALLOWED_REASONS = [
        self::REASON_CASHIER_MISTAKE,
        self::REASON_CUSTOMER_RETURNED,
        self::REASON_OTHER,
    ];

    public const REFUND_STATUS_REFUNDED = 'Refunded';
    public const REFUND_STATUS_NOT_REFUNDED = 'Not Refunded';

    public const METHOD_CASH = 'CASH';
    public const METHOD_TRANSFER = 'TRANSFER';

    protected $table = 'invoice_returns';

    protected $casts = [
        'invoice_id' => 'int',
        'customer_id' => 'int',
        'return_sequence' => 'int',
        'original_invoice_amount' => 'float',
        'current_invoice_amount' => 'float',
        'return_difference' => 'float',
        'is_customer_refunded' => 'bool',
        'refund_amount' => 'float',
        'paymentmethod_id' => 'int',
        'bank_account_id' => 'int',
        'processed_by' => 'int',
        'processed_at' => 'datetime',
        'returned_items_snapshot' => 'array',
    ];

    protected $fillable = [
        'invoice_id',
        'invoice_number',
        'customer_id',
        'department',
        'return_sequence',
        'original_invoice_amount',
        'current_invoice_amount',
        'return_difference',
        'return_reason',
        'other_reason_explanation',
        'is_customer_refunded',
        'refund_status',
        'refund_amount',
        'paymentmethod_id',
        'refund_payment_method',
        'bank_account_id',
        'processed_by',
        'return_date',
        'processed_at',
        'idempotency_key',
        'returned_items_snapshot',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function paymentmethod(): BelongsTo
    {
        return $this->belongsTo(Paymentmethod::class, 'paymentmethod_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Retrieve the configured Cash and Transfer Paymentmethod models from paymentmethods table.
     */
    public static function refundPaymentMethods(): Collection
    {
        if (!Schema::hasTable('paymentmethods')) {
            return collect();
        }

        return Paymentmethod::query()
            ->where('status', 1)
            ->get()
            ->filter(fn (Paymentmethod $pm) => self::isCashPaymentMethod($pm) || self::isTransferPaymentMethod($pm))
            ->values();
    }

    public static function isCashPaymentMethod(?Paymentmethod $pm): bool
    {
        if (!$pm) {
            return false;
        }

        return strtoupper(trim((string) $pm->name)) === 'CASH' || (int) $pm->id === 1;
    }

    public static function isTransferPaymentMethod(?Paymentmethod $pm): bool
    {
        if (!$pm) {
            return false;
        }

        return str_contains(strtoupper(trim((string) $pm->name)), 'TRANSFER') || (int) $pm->id === 3;
    }

    public static function resolveRefundPaymentMethod(mixed $idOrName): ?Paymentmethod
    {
        if ($idOrName === null || $idOrName === '' || !Schema::hasTable('paymentmethods')) {
            return null;
        }

        $methods = self::refundPaymentMethods();

        if (is_numeric($idOrName)) {
            return $methods->first(fn (Paymentmethod $pm) => (int) $pm->id === (int) $idOrName);
        }

        $upper = strtoupper(trim((string) $idOrName));

        return $methods->first(function (Paymentmethod $pm) use ($upper) {
            return strtoupper(trim((string) $pm->name)) === $upper
                || ($upper === 'CASH' && self::isCashPaymentMethod($pm))
                || ($upper === 'TRANSFER' && self::isTransferPaymentMethod($pm));
        });
    }

    /* ── Scopes for Daily Reconciliation Report ────────────────── */

    public function scopeRefunded(Builder $query): Builder
    {
        return $query->where('is_customer_refunded', true)
            ->where('refund_status', self::REFUND_STATUS_REFUNDED);
    }

    public function scopeCashRefunds(Builder $query): Builder
    {
        $cashIds = self::refundPaymentMethods()
            ->filter(fn (Paymentmethod $pm) => self::isCashPaymentMethod($pm))
            ->pluck('id')
            ->all();

        return $query->refunded()->where(function (Builder $q) use ($cashIds) {
            if (!empty($cashIds)) {
                $q->whereIn('paymentmethod_id', $cashIds);
            }
            $q->orWhereIn('refund_payment_method', ['CASH', 'Cash']);
        });
    }

    public function scopeTransferRefunds(Builder $query): Builder
    {
        $transferIds = self::refundPaymentMethods()
            ->filter(fn (Paymentmethod $pm) => self::isTransferPaymentMethod($pm))
            ->pluck('id')
            ->all();

        return $query->refunded()->where(function (Builder $q) use ($transferIds) {
            if (!empty($transferIds)) {
                $q->whereIn('paymentmethod_id', $transferIds);
            }
            $q->orWhereIn('refund_payment_method', ['TRANSFER', 'Transfer']);
        });
    }

    public function scopeForDateRange(Builder $query, string $fromDate, string $toDate): Builder
    {
        return $query->whereBetween('return_date', [$fromDate, $toDate]);
    }
}
