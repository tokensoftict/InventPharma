<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use App\Jobs\AddCustomerToWaitingList;
use App\Traits\ModelFilterTraits;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Payment
 *
 * @property int $id
 * @property int|null $user_id
 * @property int|null $customer_id
 * @property string|null $invoice_number
 * @property string $invoice_type
 * @property int $invoice_id
 * @property float $subtotal
 * @property float $total_paid
 * @property string|null $department
 * @property Carbon|null $payment_time
 * @property Carbon|null $payment_date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Customer|null $customer
 * @property User|null $user
 * @property Collection|CustomerLedger[] $customer_ledgers
 * @property Collection|Paymentmethoditem[] $paymentmethoditems
 *
 * @package App\Models
 */
class Payment extends Model
{
    use ModelFilterTraits;

	protected $table = 'payments';

	protected $casts = [
		'user_id' => 'int',
		'customer_id' => 'int',
		'invoice_id' => 'int',
		'subtotal' => 'float',
		'total_paid' => 'float',
		'payment_time' => 'datetime',
		'payment_date' => 'datetime'
	];

	protected $fillable = [
		'user_id',
		'customer_id',
		'invoice_number',
		'invoice_type',
		'invoice_id',
		'subtotal',
		'total_paid',
		'department',
		'payment_time',
		'payment_date'
	];

	public function customer()
	{
		return $this->belongsTo(Customer::class);
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}

	public function customer_ledgers()
	{
		return $this->hasMany(CustomerLedger::class);
	}

	public function paymentmethoditems()
	{
		return $this->hasMany(Paymentmethoditem::class);
	}

    public function invoice(){

        return $this->morphTo();
    }


    public function newonlinePush()
    {
        if(config('app.start_customer_queue_module')) {
            AddCustomerToWaitingList::dispatch($this->invoice_id);
        }
    }
}
