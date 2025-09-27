<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use App\Enums\KafkaAction;
use App\Enums\KafkaTopics;
use App\Jobs\PushDataServer;
use App\Traits\ModelFilterTraits;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Customer
 * 
 * @property int $id
 * @property string|null $firstname
 * @property string|null $lastname
 * @property string|null $email
 * @property bool|null $status
 * @property string|null $address
 * @property string|null $phone_number
 * @property bool $retail_customer
 * @property int|null $city_id
 * @property float $credit_balance
 * @property float $deposit_balance
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Invoice $invoice
 * @property City|null $city
 * @property Collection|Creditpaymentlog[] $creditpaymentlogs
 * @property Collection|CustomerLedger[] $customer_ledgers
 * @property Collection|Invoiceitem[] $invoiceitems
 * @property Collection|Invoice[] $invoices
 * @property Collection|Paymentmethoditem[] $paymentmethoditems
 * @property Collection|Payment[] $payments
 *
 * @package App\Models
 */
class Customer extends Model
{
    use ModelFilterTraits;
	protected $table = 'customers';

	protected $casts = [
		'status' => 'bool',
		'retail_customer' => 'bool',
		'city_id' => 'int',
		'credit_balance' => 'float',
		'deposit_balance' => 'float'
	];

	protected $fillable = [
		'firstname',
		'lastname',
		'email',
		'status',
		'address',
		'phone_number',
		'retail_customer',
		'city_id',
		'credit_balance',
		'deposit_balance'
	];


    protected $appends = ['fullname'];

    public function getFullnameAttribute()
    {
        return $this->firstname." ".$this->lastname;
    }

	public function city()
	{
		return $this->belongsTo(City::class);
	}

	public function creditpaymentlogs()
	{
		return $this->hasMany(Creditpaymentlog::class);
	}


    public function creditpaymentlog()
    {
        return $this->hasOne(Creditpaymentlog::class)->ofMany(['id' => 'MAX'], function($query){
            $query->where('amount', '>', 0);
        });
    }

    public function payment()
    {
        return $this->hasOne(Payment::class)->ofMany(['id' => 'MAX'], function($query){
            $query->where('total_paid', '>', 0);
        });
    }
    public function invoice()
    {
        return $this->hasOne(Invoice::class)->latestOfMany();
    }

	public function customer_ledgers()
	{
		return $this->hasMany(CustomerLedger::class);
	}

	public function invoiceitems()
	{
		return $this->hasMany(Invoiceitem::class);
	}

	public function invoices()
	{
		return $this->hasMany(Invoice::class);
	}



	public function paymentmethoditems()
	{
		return $this->hasMany(Paymentmethoditem::class);
	}

	public function payments()
	{
		return $this->hasMany(Payment::class);
	}

    public function updateCreditBalance()
    {
        $this->credit_balance =  $this->creditpaymentlogs()->sum('amount');
        $this->update();
    }

    public function getBulkPushData() : array{
        return [
            'local_id'=>$this->id,
            'firstname'=> $this->firstname,
            'lastname'=>$this->lastname,
            'email'=>$this->email,
            'address'=>$this->address,
            'phone_number'=>$this->phone_number
        ];
    }

    public function newonlinePush()
    {
        dispatch(new PushDataServer(['KAFKA_ACTION'=> KafkaAction::CREATE_CUSTOMER, 'KAFKA_TOPICS'=>KafkaTopics::GENERAL, 'action'=>'new','table'=>'existing_customer','data'=>$this->getBulkPushData()]));
    }

    public function updateonlinePush()
    {
        dispatch(new PushDataServer(['KAFKA_ACTION'=> KafkaAction::UPDATE_CUSTOMER, 'KAFKA_TOPICS'=>KafkaTopics::GENERAL, 'action'=>'update','table'=>'existing_customer','data'=>$this->getBulkPushData()]));
    }

}
