<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use App\Observers\InvoiceObserver;
use App\Traits\ModelFilterTraits;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Invoice
 *
 * @property int $id
 * @property string $invoice_number
 * @property int|null $customer_id
 * @property int|null $prescriber_id
 * @property int|null $payment_id
 * @property string|null $department
 * @property string|null $in_department
 * @property float|null $discount_amount
 * @property string|null $discount_type
 * @property float $discount_value
 * @property int $status_id
 * @property float $sub_total
 * @property float $total_amount_paid
 * @property float $total_profit
 * @property float $total_cost
 * @property float $vat
 * @property float $vat_amount
 * @property int|null $created_by
 * @property int|null $scan_user_id
 * @property int|null $last_updated_by
 * @property int|null $voided_by
 * @property Carbon $invoice_date
 * @property Carbon $sales_time
 * @property string|null $void_reason
 * @property Carbon|null $date_voided
 * @property Carbon|null $scan_date
 * @property Carbon|null $scan_time
 * @property Carbon|null $void_time
 * @property int|null $picked_by
 * @property int|null $packed_by
 * @property int|null $checked_by
 * @property int $carton_no
 * @property bool $online_order_status
 * @property bool $retail_printed
 * @property int|null $online_order_debit
 * @property int|null $onliner_order_id
 * @property int|null $before_customer_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property User|null $user
 * @property User|null $scan_by
 * @property User|null $create_by
 * @property Customer|null $customer
 * @property Status $status
 * @property Collection|CustomerLedger[] $customer_ledgers
 * @property Collection|Invoiceactivitylog[] $invoiceactivitylogs
 * @property Collection|Invoiceitembatch[] $invoiceitembatches
 * @property Collection|Invoiceitem[] $invoiceitems
 * @property Collection|invoiceprinthistories[] $invoiceprinthistories
 * @property Collection|Onlineordertotal[] $onlineordertotals
 *
 * @package App\Models
 */
#[ObservedBy([InvoiceObserver::class])]
class Invoice extends Model
{
    use ModelFilterTraits;

	protected $table = 'invoices';


	protected $casts = [
		'customer_id' => 'int',
        'is_stock_exchange' => 'bool',
        'invoice_type' => 'string',
        'supplier_id' => 'int',
        'prescriber_id' => 'int',
		'payment_id' => 'int',
		'discount_amount' => 'float',
		'discount_value' => 'float',
		'status_id' => 'int',
		'sub_total' => 'float',
		'total_amount_paid' => 'float',
        'exchange_amount_utilised' => 'float',
		'total_profit' => 'float',
		'total_cost' => 'float',
		'vat' => 'float',
		'vat_amount' => 'float',
		'created_by' => 'int',
		'last_updated_by' => 'int',
		'voided_by' => 'int',
		'invoice_date' => 'datetime',
        'scan_date' => 'datetime',
		'sales_time' => 'datetime',
        'scan_time' => 'datetime',
		'date_voided' => 'datetime',
		'void_time' => 'datetime',
		'picked_by' => 'int',
		'packed_by' => 'int',
		'checked_by' => 'int',
		'carton_no' => 'int',
        'scan_user_id' =>'int',
		'online_order_status' => 'bool',
		'online_order_debit' => 'int',
		'onliner_order_id' => 'int',
		'before_customer_id' => 'int',
        'retail_printed' => 'bool',
        'membership_discount' => 'float',
        'membership_discount_value' => 'float',
	];

	protected $fillable = [
		'invoice_number',
		'customer_id',
        'is_stock_exchange',
        'invoice_type',
        'supplier_id',
		'payment_id',
		'department',
		'in_department',
		'discount_amount',
		'discount_type',
		'discount_value',
		'status_id',
		'sub_total',
		'total_amount_paid',
        'exchange_amount_utilised',
		'total_profit',
		'total_cost',
		'vat',
		'vat_amount',
		'created_by',
		'last_updated_by',
		'voided_by',
		'invoice_date',
		'sales_time',
		'void_reason',
		'date_voided',
		'void_time',
		'picked_by',
		'packed_by',
		'checked_by',
		'carton_no',
		'online_order_status',
		'online_order_debit',
		'onliner_order_id',
		'before_customer_id',
        'retail_printed',
        'scan_user_id',
        'scan_date',
        'scan_time',
        'prescriber_id',
        'prescriber_payment_status',
        'membership_discount',
        'membership_discount_value'
	];

    protected static function booted(): void
    {
        static::saving(function (Invoice $invoice) {
            if ($invoice->is_stock_exchange || $invoice->invoice_type === 'stock_exchange') {
                $invoice->is_stock_exchange = true;
                $invoice->invoice_type = 'stock_exchange';
            } else {
                $invoice->is_stock_exchange = false;
                $invoice->invoice_type = $invoice->invoice_type ?: 'normal';
            }
        });

        static::deleting(function (Invoice $invoice) {
            if ($invoice->hasActiveExchangeAllocations()) {
                throw new \DomainException('Cannot delete an invoice with active stock exchange allocations. Reverse the allocations first.');
            }
        });
    }

    public function payment()
    {
        return $this->morphOne(Payment::class,'invoice');
    }

    public function creditlogs()
    {
        return $this->morphOne(Creditpaymentlog::class, 'invoicelog');
    }

    public function paymentmethoditems()
    {
        return $this->morphMany(Paymentmethoditem::class,'invoice');
    }

    public function create_by() : BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function last_updated() : BelongsTo
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }

    public function picked() : BelongsTo
    {
        return $this->belongsTo(User::class, 'picked_by');
    }

    public function checked() : BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    public function packed() : BelongsTo
    {
        return $this->belongsTo(User::class, 'packed_by');
    }


    public function scan_by() : BelongsTo
    {
        return $this->belongsTo(User::class, 'scan_user_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

	public function customer()
	{
		return $this->belongsTo(Customer::class);
	}

    public function prescriber()
	{
		return $this->belongsTo(Prescriber::class);
	}

	public function status()
	{
		return $this->belongsTo(Status::class);
	}

	public function customer_ledgers()
	{
		return $this->hasMany(CustomerLedger::class);
	}

	public function invoiceactivitylogs()
	{
		return $this->hasMany(Invoiceactivitylog::class);
	}

	public function invoiceitembatches()
	{
		return $this->hasMany(Invoiceitembatch::class);
	}

	public function invoiceitems()
	{
		return $this->hasMany(Invoiceitem::class);
	}

	public function onlineordertotals()
	{
		return $this->hasMany(Onlineordertotal::class);
	}

    public function invoiceprinthistories()
    {
        return $this->hasMany(Invoiceprinthistory::class);
    }

    public function opayPaymentLogs()
    {
        return $this->hasMany(OpayPaymentLog::class);
    }

    public function waitingCustomer()
    {
        return $this->hasOne(WaitingCustomer::class, );
    }

    public function supplier() : BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function stockExchangeAllocations()
    {
        return $this->hasMany(SupplierCreditPaymentHistory::class, 'invoice_id')
            ->where('type', 'PAYMENT');
    }

    public function activeStockExchangeAllocations()
    {
        return $this->hasMany(SupplierCreditPaymentHistory::class, 'invoice_id')
            ->where('type', 'PAYMENT')
            ->where(function ($query) {
                $query->whereNull('status')->orWhere('status', '!=', 'Reversed');
            })
            ->whereNull('reversed_at');
    }

    public function isStockExchange(): bool
    {
        return (bool) $this->is_stock_exchange || $this->invoice_type === 'stock_exchange';
    }

    public function isCancelledOrReversed(): bool
    {
        $invalidStatusIds = array_values(array_filter([
            status('Cancelled'),
            status('Deleted'),
            status('Declined'),
        ]));

        if (!empty($invalidStatusIds) && in_array((int) $this->status_id, $invalidStatusIds, true)) {
            return true;
        }

        if (!is_null($this->voided_by) || !is_null($this->date_voided)) {
            return true;
        }

        return false;
    }

    public function calculateOriginalTotal(): float
    {
        $subTotal = (float) ($this->sub_total ?? 0);
        $discount = (float) ($this->discount_amount ?? 0);
        $membershipDiscount = (float) ($this->membership_discount ?? 0);

        return max(0.0, round($subTotal - ($discount + $membershipDiscount), 5));
    }

    public function getOriginalInvoiceTotalAttribute(): float
    {
        return $this->calculateOriginalTotal();
    }

    public function calculateTotalExchangeUtilised(): float
    {
        if (!$this->exists) {
            return round((float) ($this->exchange_amount_utilised ?? 0), 5);
        }

        if (\Illuminate\Support\Facades\Schema::hasTable('supplier_credit_payment_histories')
            && \Illuminate\Support\Facades\Schema::hasColumn('supplier_credit_payment_histories', 'invoice_id')) {
            $allocations = $this->stockExchangeAllocations()->get();

            $activeTotal = $allocations->filter(function ($item) {
                if ($item->status === 'Reversed' || !is_null($item->reversed_at)) {
                    return false;
                }
                if (isset($item->payment_info['status']) && in_array($item->payment_info['status'], ['Reversed', 'Declined'], true)) {
                    return false;
                }
                return true;
            })->sum('amount');

            return max(0.0, round((float) $activeTotal, 5));
        }

        return max(0.0, round((float) ($this->exchange_amount_utilised ?? 0), 5));
    }

    public function getTotalExchangeUtilisedAttribute(): float
    {
        return $this->calculateTotalExchangeUtilised();
    }

    public function calculateRemainingExchangeBalance(): float
    {
        return max(0.0, round($this->calculateOriginalTotal() - $this->calculateTotalExchangeUtilised(), 5));
    }

    public function getRemainingExchangeBalanceAttribute(): float
    {
        return $this->calculateRemainingExchangeBalance();
    }

    public function hasActiveExchangeAllocations(): bool
    {
        return round($this->calculateTotalExchangeUtilised(), 2) > 0;
    }

    public function isEligibleForStockExchange(?int $supplierId = null): bool
    {
        if (!$this->isStockExchange()) {
            return false;
        }

        if ($this->isCancelledOrReversed()) {
            return false;
        }

        $exchangedStatusId = status('Exchanged');
        if ($exchangedStatusId !== null && (int) $this->status_id === (int) $exchangedStatusId) {
            return false;
        }

        if (is_null($this->supplier_id)) {
            return false;
        }

        if ($supplierId !== null && (int) $this->supplier_id !== (int) $supplierId) {
            return false;
        }

        if (round($this->calculateRemainingExchangeBalance(), 2) <= 0) {
            return false;
        }

        return true;
    }

    public function recalculateExchangeStatus(): self
    {
        if (!$this->isStockExchange()) {
            return $this;
        }

        $originalTotal = $this->calculateOriginalTotal();
        $utilised = $this->calculateTotalExchangeUtilised();
        $remaining = max(0.0, round($originalTotal - $utilised, 5));

        $this->exchange_amount_utilised = $utilised;
        $this->total_amount_paid = $utilised;

        if (round($utilised, 2) <= 0) {
            $unpaidStatusId = status('Unpaid') ?? status('Draft');
            if ($unpaidStatusId !== null) {
                $this->status_id = $unpaidStatusId;
            }
        } elseif (round($remaining, 2) > 0) {
            $partiallyExchangedStatusId = status('Partially Exchanged');
            if ($partiallyExchangedStatusId !== null) {
                $this->status_id = $partiallyExchangedStatusId;
            }
        } else {
            $exchangedStatusId = status('Exchanged');
            if ($exchangedStatusId !== null) {
                $this->status_id = $exchangedStatusId;
            }
        }

        $this->save();

        return $this;
    }

    public function scopeEligibleForStockExchange($query, ?int $supplierId = null, ?string $search = null)
    {
        $invalidStatusIds = array_values(array_filter([
            status('Cancelled'),
            status('Deleted'),
            status('Declined'),
            status('Exchanged'),
        ]));

        $query->where(function ($q) {
            $q->where('is_stock_exchange', true)
              ->orWhere('invoice_type', 'stock_exchange');
        })
        ->whereNotNull('supplier_id')
        ->whereNull('voided_by')
        ->whereNull('date_voided');

        if (!empty($invalidStatusIds)) {
            $query->whereNotIn('status_id', $invalidStatusIds);
        }

        if ($supplierId !== null) {
            $query->where('supplier_id', $supplierId);
        }

        if ($search !== null && trim($search) !== '') {
            $term = trim($search);
            $query->where('invoice_number', 'like', '%' . $term . '%');
        }

        return $query;
    }

    public function getSyncData(): array
    {
        $products = [];
        foreach ($this->invoiceitems as $item) {
            $products[] = [
                'local_id' => $item->stock_id,
                'name' => $item->name,
                'model' => $item->stock->model ?? '',
                'quantity' => $item->quantity,
                'price' => $item->selling_price,
                'total' => $item->selling_price * $item->quantity,
                'tax' => 0,
                'reward' => 0,
            ];
        }

        $totals = [
            ['name' => 'Sub-Total', 'value' => $this->sub_total, 'order_total_id' => 1],
            ['name' => 'Total', 'value' => $this->total_amount_paid, 'order_total_id' => 2],
        ];

        return [
            'order' => [
                'id' => $this->id,
                'invoice_no' => $this->invoice_number,
                'customer_local_id' => $this->customer_id,
                'customer_phone_number' => $this->customer->phone_number ?? '',
                'department' => $this->department,
                'in_department' => $this->in_department,
                'invoice_date' => $this->invoice_date->toDateString(),
                'total' => $this->total_amount_paid,
                'created_by' => is_null($this->onliner_order_id) ? $this->created_by : $this->payment->user_id, // use payment use for online invoice
                'payment_methods' => $this->paymentmethoditems->map(function($item) {
                    return $item->paymentmethod->name ?? 'Unknown';
                })->unique()->implode(', '),
                'comment' => '',
            ],
            'products' => $products,
            'totals' => $totals,
        ];
    }
}
