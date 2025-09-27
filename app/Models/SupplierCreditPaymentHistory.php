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
		'paymentmethod_id' => 'int',
		'amount' => 'float',
		'payment_date' => 'datetime',
        'payment_info' => 'json'
	];

	protected $fillable = [
		'user_id',
		'supplier_id',
		'type',
		'purchase_id',
		'paymentmethod_id',
		'payment_info',
		'amount',
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

	public function supplier()
	{
		return $this->belongsTo(Supplier::class);
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
