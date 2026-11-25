<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use App\Traits\ModelFilterTraits;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Creditpaymentlog
 * 
 * @property int $id
 * @property string $credit_number
 * @property int|null $payment_id
 * @property int|null $user_id
 * @property int|null $paymentmethod_id
 * @property int|null $customer_id
 * @property int|null $paymentmethoditem_id
 * @property string|null $invoicelog_type
 * @property int|null $invoicelog_id
 * @property float $amount
 * @property Carbon $payment_date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Customer|null $customer
 * @property Payment|null $payment
 * @property Paymentmethod|null $paymentmethod
 * @property Paymentmethoditem|null $paymentmethoditem
 * @property User|null $user
 *
 * @package App\Models
 */
class Creditpaymentlog extends Model
{

    use ModelFilterTraits;

	protected $table = 'creditpaymentlogs';

	protected $casts = [
		'payment_id' => 'int',
		'user_id' => 'int',
		'paymentmethod_id' => 'int',
		'customer_id' => 'int',
		'paymentmethoditem_id' => 'int',
		'invoicelog_id' => 'int',
		'amount' => 'float',
		'payment_date' => 'datetime'
	];

	protected $fillable = [
		'credit_number',
		'payment_id',
		'user_id',
		'paymentmethod_id',
		'customer_id',
		'paymentmethoditem_id',
		'invoicelog_type',
		'invoicelog_id',
		'amount',
		'payment_date',
		'description',
	];

	public function customer()
	{
		return $this->belongsTo(Customer::class);
	}

	public function payment()
	{
		return $this->belongsTo(Payment::class);
	}

	public function paymentmethod()
	{
		return $this->belongsTo(Paymentmethod::class);
	}

	public function paymentmethoditem()
	{
		return $this->belongsTo(Paymentmethoditem::class);
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}

    public function invoicelog(){
        return $this->morphTo();
    }

    public function opayPaymentLogs()
    {
        return $this->hasMany(OpayPaymentLog::class);
    }

    public function isCreditRecord(): bool
    {
        return (float) $this->amount < 0;
    }

    public function isPaymentRecord(): bool
    {
        return (float) $this->amount > 0;
    }

    public function resolveInvoice(): ?Invoice
    {
        if ($this->invoicelog instanceof Invoice) {
            return $this->invoicelog;
        }

        if ($this->invoicelog_type === Invoice::class && !empty($this->invoicelog_id)) {
            return Invoice::find($this->invoicelog_id);
        }

        if ($this->payment && !empty($this->payment->invoice_id)) {
            $invType = $this->payment->invoice_type;
            if (empty($invType) || $invType === Invoice::class) {
                return $this->payment->invoice instanceof Invoice
                    ? $this->payment->invoice
                    : Invoice::find($this->payment->invoice_id);
            }
        }

        return null;
    }

    public function invoiceNumberLabel(): string
    {
        if (!$this->isCreditRecord()) {
            return '';
        }

        return trim((string) ($this->resolveInvoice()?->invoice_number ?? ''));
    }

    public function modeOfPaymentLabel(): string
    {
        if (!$this->isPaymentRecord()) {
            return '';
        }

        $methodName = trim((string) ($this->paymentmethod?->name ?? ''));

        if ((int) $this->paymentmethod_id === 6 || $methodName === '' || strtoupper($methodName) === 'SPLIT') {
            $splitNames = $this->payment?->paymentmethoditems
                ?->map(fn (Paymentmethoditem $item) => trim((string) ($item->paymentmethod?->name ?? '')))
                ->filter()
                ->unique()
                ->values()
                ->all() ?? [];

            if (!empty($splitNames)) {
                return count($splitNames) > 1
                    ? 'SPLIT (' . implode(', ', $splitNames) . ')'
                    : $splitNames[0];
            }
        }

        return $methodName;
    }

    public function descriptionLabel(): string
    {
        return trim((string) ($this->description ?? ''));
    }

    public function returnOrRefundLabel(): string
    {
        if (!$this->isCreditRecord() || !\Illuminate\Support\Facades\Schema::hasTable('invoice_returns')) {
            return '';
        }

        $invoice = $this->resolveInvoice();
        if (!$invoice) {
            return '';
        }

        $returns = $invoice->relationLoaded('invoiceReturns')
            ? $invoice->invoiceReturns->sortBy('id')->values()
            : $invoice->invoiceReturns()->with('paymentmethod')->reorder('id', 'asc')->get();

        if ($returns->isEmpty()) {
            return '';
        }

        $parts = [];
        foreach ($returns as $ret) {
            $displayAmount = $ret->is_customer_refunded
                ? (float) $ret->refund_amount
                : (float) $ret->return_difference;

            if ($displayAmount <= 0) {
                continue;
            }

            if ($ret->is_customer_refunded) {
                $methodName = trim((string) ($ret->paymentmethod?->name ?? $ret->refund_payment_method));
                $parts[] = number_format($displayAmount, 2) . ($methodName !== '' ? ' (' . $methodName . ')' : '');
            } else {
                $parts[] = number_format($displayAmount, 2);
            }
        }

        return implode(', ', $parts);
    }
}
