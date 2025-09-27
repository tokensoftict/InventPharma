<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use App\Traits\ModelFilterTraits;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Paymentmethoditem
 *
 * @property int $id
 * @property int|null $user_id
 * @property int|null $customer_id
 * @property int|null $payment_id
 * @property int|null $paymentmethod_id
 * @property string $invoice_type
 * @property int $invoice_id
 * @property string|null $department
 * @property Carbon $payment_date
 * @property float $amount
 * @property string|null $payment_info
 * @property int|null $bank_account_id
 * @property float|null $amount_tendered
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property BankAccount|null $bank_account
 * @property Customer|null $customer
 * @property Payment|null $payment
 * @property Paymentmethod|null $paymentmethod
 * @property User|null $user
 *
 * @package App\Models
 */
class Paymentmethoditem extends Model
{
    use ModelFilterTraits;

	protected $table = 'paymentmethoditems';

	protected $casts = [
		'user_id' => 'int',
		'customer_id' => 'int',
		'payment_id' => 'int',
		'paymentmethod_id' => 'int',
		'invoice_id' => 'int',
		'payment_date' => 'datetime',
		'amount' => 'float',
		'bank_account_id' => 'int',
		'amount_tendered' => 'float'
	];

	protected $fillable = [
		'user_id',
		'customer_id',
		'payment_id',
		'paymentmethod_id',
		'invoice_type',
		'invoice_id',
		'department',
		'payment_date',
		'amount',
		'payment_info',
		'bank_account_id',
		'amount_tendered'
	];

	public function bank_account()
	{
		return $this->belongsTo(BankAccount::class);
	}

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

	public function user()
	{
		return $this->belongsTo(User::class);
	}

    public function invoice(){

        return $this->morphTo();
    }
}
