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

    public function isCreditBalanceRefundRecord(): bool
    {
        return $this->invoicelog_type === InvoiceReturn::class && (float) $this->amount > 0;
    }

    public function resolveInvoice(): ?Invoice
    {
        if ($this->invoicelog instanceof Invoice) {
            return $this->invoicelog;
        }

        if ($this->invoicelog instanceof InvoiceReturn) {
            return $this->invoicelog->relationLoaded('invoice')
                ? $this->invoicelog->invoice
                : Invoice::find($this->invoicelog->invoice_id);
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

    /**
     * Resolve all InvoiceReturn records for the linked invoice on this credit log row.
     *
     * @return \Illuminate\Support\Collection<int, InvoiceReturn>
     */
    public function invoiceReturns(): \Illuminate\Support\Collection
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('invoice_returns')) {
            return collect();
        }

        if ($this->isCreditBalanceRefundRecord() && $this->invoicelog instanceof InvoiceReturn) {
            return collect([$this->invoicelog]);
        }

        $invoice = $this->resolveInvoice();
        if (!$invoice) {
            return collect();
        }

        return $invoice->relationLoaded('invoiceReturns')
            ? $invoice->invoiceReturns->sortBy('id')->values()
            : $invoice->invoiceReturns()->with('paymentmethod')->reorder('id', 'asc')->get();
    }

    /**
     * Determine whether the invoice linked to this credit record was returned.
     */
    public function isReturnedInvoice(): bool
    {
        if (!$this->isCreditRecord()) {
            return false;
        }

        if ($this->invoiceReturns()->isNotEmpty()) {
            return true;
        }

        $invoice = $this->resolveInvoice();

        return $invoice !== null && (!is_null($invoice->date_voided) || (int) $invoice->void_reason > 0);
    }

    public function invoiceNumberLabel(): string
    {
        if ($this->isCreditBalanceRefundRecord()) {
            if ($this->invoicelog instanceof InvoiceReturn && !empty($this->invoicelog->invoice_number)) {
                return trim((string) $this->invoicelog->invoice_number);
            }

            return trim((string) ($this->resolveInvoice()?->invoice_number ?? ''));
        }

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

        if ($this->isCreditBalanceRefundRecord() || (int) $this->paymentmethod_id === 4) {
            return InvoiceReturn::METHOD_CREDIT;
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
        $rawDescription = trim((string) ($this->description ?? ''));
        if ($rawDescription !== '') {
            return $rawDescription;
        }

        if ($this->isReturnedInvoice()) {
            return $this->returnedInvoiceSummaryText();
        }

        return '';
    }

    /**
     * Build a concise human-readable summary of the return(s) for a returned credit invoice row.
     */
    public function returnedInvoiceSummaryText(): string
    {
        if (!$this->isReturnedInvoice()) {
            return '';
        }

        $returns = $this->invoiceReturns();
        $invoice = $this->resolveInvoice();

        if ($returns->isEmpty()) {
            $voidDate = $invoice && $invoice->date_voided ? eng_str_date($invoice->date_voided) : '';
            return 'Invoice Returned' . ($voidDate !== '' ? ' on ' . $voidDate : '');
        }

        $firstReturn = $returns->first();
        $lastReturn = $returns->last();

        $originalAmount = (float) $firstReturn->original_invoice_amount;
        $currentAmount = (float) $lastReturn->current_invoice_amount;
        $totalReturned = (float) $returns->sum('return_difference');

        $reasons = $returns->map(function (InvoiceReturn $ret) {
            if ($ret->return_reason === InvoiceReturn::REASON_OTHER && !empty($ret->other_reason_explanation)) {
                return trim((string) $ret->other_reason_explanation);
            }
            return trim((string) $ret->return_reason);
        })->filter()->unique()->values()->all();

        $parts = [
            'Invoice Returned',
            'Original: ' . number_format($originalAmount, 2),
            'After Return: ' . number_format($currentAmount, 2),
            'Returned: ' . number_format($totalReturned, 2),
        ];

        if (!empty($reasons)) {
            $parts[] = 'Reason: ' . implode('; ', $reasons);
        }

        if (!empty($lastReturn->return_date)) {
            $parts[] = 'Return Date: ' . eng_str_date($lastReturn->return_date);
        }

        return implode(' | ', $parts);
    }

    public function returnOrRefundLabel(): string
    {
        if ($this->isCreditBalanceRefundRecord()) {
            return number_format((float) $this->amount, 2) . ' (' . InvoiceReturn::METHOD_CREDIT . ')';
        }

        if (!$this->isCreditRecord() || !\Illuminate\Support\Facades\Schema::hasTable('invoice_returns')) {
            return '';
        }

        $returns = $this->invoiceReturns();

        if ($returns->isEmpty()) {
            return $this->isReturnedInvoice() ? 'Returned' : '';
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
                $rawMethodName = trim((string) ($ret->paymentmethod?->name ?? $ret->refund_payment_method));
                $methodName = ((int) $ret->paymentmethod_id === 4 || in_array(strtoupper($rawMethodName), ['CREDIT', 'CREDIT BALANCE'], true))
                    ? InvoiceReturn::METHOD_CREDIT
                    : $rawMethodName;
                $parts[] = number_format($displayAmount, 2) . ($methodName !== '' ? ' (' . $methodName . ')' : '');
            } else {
                $parts[] = number_format($displayAmount, 2);
            }
        }

        return implode(', ', $parts);
    }
}
