<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use App\Traits\ModelFilterTraits;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Invoice
 *
 * @property int $id
 * @property string $invoice_number
 * @property int|null $customer_id
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
class Invoice extends Model
{
    use ModelFilterTraits;

	protected $table = 'invoices';


	protected $casts = [
		'customer_id' => 'int',
		'payment_id' => 'int',
		'discount_amount' => 'float',
		'discount_value' => 'float',
		'status_id' => 'int',
		'sub_total' => 'float',
		'total_amount_paid' => 'float',
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
        'retail_printed' => 'bool'
	];

	protected $fillable = [
		'invoice_number',
		'customer_id',
		'payment_id',
		'department',
		'in_department',
		'discount_amount',
		'discount_type',
		'discount_value',
		'status_id',
		'sub_total',
		'total_amount_paid',
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
        'scan_time'
	];

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

    public function waitingCustomer()
    {
        return $this->hasOne(WaitingCustomer::class, );
    }
}
