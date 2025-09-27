<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use App\Traits\ModelFilterTraits;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class CustomerLedger
 *
 * @property int $id
 * @property int|null $payment_id
 * @property int|null $invoice_id
 * @property int|null $customer_id
 * @property float $amount
 * @property float $total
 * @property Carbon $transaction_date
 * @property int|null $user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Customer|null $customer
 * @property Invoice|null $invoice
 * @property Payment|null $payment
 * @property User|null $user
 *
 * @package App\Models
 */
class CustomerLedger extends Model
{

    use ModelFilterTraits;

	protected $table = 'customer_ledgers';

	protected $casts = [
		'payment_id' => 'int',
		'invoice_id' => 'int',
		'customer_id' => 'int',
		'amount' => 'float',
		'total' => 'float',
		'transaction_date' => 'datetime',
		'user_id' => 'int'
	];

	protected $fillable = [
		'payment_id',
		'invoice_id',
		'customer_id',
		'amount',
		'total',
		'transaction_date',
		'user_id'
	];

	public function customer()
	{
		return $this->belongsTo(Customer::class);
	}

	public function invoice()
	{
		return $this->belongsTo(Invoice::class);
	}

	public function payment()
	{
		return $this->belongsTo(Payment::class);
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
