<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use App\Traits\ModelFilterTraits;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class SupplierCreditPaymentHistory
 * 
 * @property int $id
 * @property int|null $user_id
 * @property int|null $supplier_id
 * @property string $type
 * @property int|null $purchase_id
 * @property int|null $paymentmethod_id
 * @property string|null $payment_info
 * @property float $amount
 * @property Carbon $payment_date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Paymentmethod|null $paymentmethod
 * @property Purchase|null $purchase
 * @property Supplier|null $supplier
 * @property User|null $user
 *
 * @package App\Models
 */
class SupplierCreditPaymentHistory extends Model
{

    use ModelFilterTraits;

	protected $table = 'supplier_credit_payment_histories';

	protected $casts = [
		'user_id' => 'int',
		'supplier_id' => 'int',
		'purchase_id' => 'int',
        'invoice_id' => 'int',
        'purchase_return_id' => 'int',
		'paymentmethod_id' => 'int',
        'reference' => 'string',
		'amount' => 'float',
        'status' => 'string',
        'reversed_at' => 'datetime',
        'reversed_by' => 'int',
        'reversal_reason' => 'string',
        'idempotency_key' => 'string',
		'payment_date' => 'datetime',
        'payment_info' => 'json'
	];

	protected $fillable = [
		'user_id',
		'supplier_id',
		'type',
		'purchase_id',
        'invoice_id',
        'purchase_return_id',
		'paymentmethod_id',
        'reference',
		'payment_info',
		'amount',
        'status',
        'reversed_at',
        'reversed_by',
        'reversal_reason',
        'idempotency_key',
        'remark',
		'payment_date'
	];

	public function paymentmethod()
	{
		return $this->belongsTo(Paymentmethod::class);
	}

	public function purchase()
	{
		return $this->belongsTo(Purchase::class);
	}

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function purchaseReturn()
    {
        return $this->belongsTo(PurchaseReturn::class, 'purchase_return_id');
    }

	public function supplier()
	{
		return $this->belongsTo(Supplier::class);
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}

    public function reversedByUser()
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function isStockForStock(): bool
    {
        return !is_null($this->invoice_id)
            || is_stock_for_stock_payment_method($this->paymentmethod_id)
            || ($this->relationLoaded('paymentmethod') && is_stock_for_stock_payment_method($this->paymentmethod));
    }

    public function isReversed(): bool
    {
        return $this->status === 'Reversed'
            || !is_null($this->reversed_at)
            || (isset($this->payment_info['status']) && $this->payment_info['status'] === 'Reversed');
    }
}
