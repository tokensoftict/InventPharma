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
 * @property int $member_group_id
 * @property int $retail_member_group_id
 * @property float $loyalty_points
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
 * @property MemberGroup|null $member_group
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
		'deposit_balance' => 'float',
        'loyalty_points' => 'float',
        'retail_loyalty_points' => 'float',
        'member_group_id' => 'int',
        'retail_member_group_id' => 'int',
        'is_manual_member_group' => 'bool',
        'is_manual_retail_member_group' => 'bool',
        'customer_type' => 'string',
	];

	protected $fillable = [
		'firstname',
		'lastname',
		'email',
		'status',
		'address',
		'phone_number',
		'retail_customer',
        'customer_type',
		'city_id',
		'credit_balance',
		'deposit_balance',
        'loyalty_points',
        'retail_loyalty_points',
        'member_group_id',
        'retail_member_group_id',
        'is_manual_member_group',
        'is_manual_retail_member_group'
	];


    protected $appends = ['fullname', 'department_type', 'type_label'];

    public function getFullnameAttribute()
    {
        return $this->firstname." ".$this->lastname;
    }

    public function getDepartmentTypeAttribute(): string
    {
        if (!empty($this->customer_type) && in_array($this->customer_type, ['both', 'retail', 'wholesale'])) {
            return $this->customer_type;
        }

        // Check if query already computed spent in both
        if (isset($this->attributes['retail_spent']) && isset($this->attributes['wholesale_spent'])) {
            if ((float)$this->attributes['retail_spent'] > 0 && (float)$this->attributes['wholesale_spent'] > 0) {
                return 'both';
            }
            if ((float)$this->attributes['retail_spent'] > 0) {
                return 'retail';
            }
            if ((float)$this->attributes['wholesale_spent'] > 0) {
                return 'wholesale';
            }
        }

        // Check customer invoices in_department if loaded:
        // 'bulksales' & 'wholesales' => wholesale, 'retail' => retail
        if ($this->relationLoaded('invoices') && $this->invoices->isNotEmpty()) {
            $hasWholesale = $this->invoices->contains(fn($inv) => in_array($inv->in_department, ['bulksales', 'wholesales']));
            $hasRetail = $this->invoices->contains(fn($inv) => $inv->in_department === 'retail');
            if ($hasWholesale && $hasRetail) {
                return 'both';
            }
            if ($hasRetail) {
                return 'retail';
            }
            if ($hasWholesale) {
                return 'wholesale';
            }
        }

        // If customer has both wholesale and retail member groups assigned
        if (!empty($this->member_group_id) && !empty($this->retail_member_group_id)) {
            return 'both';
        }

        return $this->retail_customer ? 'retail' : 'wholesale';
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->department_type) {
            'both' => 'Wholesales & Retail',
            'retail' => 'Retail',
            default => 'Wholesales',
        };
    }

    public function getTypeBadgeAttribute(): string
    {
        return match ($this->department_type) {
            'both' => '<span class="badge text-white px-2 py-1" style="background-color: #6f42c1 !important; font-size: 11px;"><i class="bx bx-git-merge me-1"></i> Both (Wholesale &amp; Retail)</span>',
            'retail' => '<span class="badge bg-success-subtle text-success border border-success px-2 py-1" style="font-size: 11px;"><i class="bx bx-store me-1"></i> Retail</span>',
            default => '<span class="badge bg-primary-subtle text-primary border border-primary px-2 py-1" style="font-size: 11px;"><i class="bx bx-buildings me-1"></i> Wholesales</span>',
        };
    }

    public function getIsOwingAttribute(): bool
    {
        return (float) $this->credit_balance < 0;
    }

    public function getOwingAmountAttribute(): float
    {
        return $this->is_owing ? abs((float) $this->credit_balance) : 0.0;
    }

    public function getOwingBadgeAttribute(): string
    {
        if ($this->is_owing) {
            return '<span class="badge bg-danger-subtle text-danger border border-danger px-2 py-1 fw-bold" style="font-size: 11px;"><i class="bx bx-error-circle me-1"></i> Owing ' . money($this->owing_amount) . '</span>';
        }
        if ((float) $this->credit_balance > 0) {
            return '<span class="badge bg-success-subtle text-success border border-success px-2 py-1" style="font-size: 11px;"><i class="bx bx-wallet me-1"></i> Deposit ' . money($this->credit_balance) . '</span>';
        }
        return '<span class="badge bg-light text-muted border px-2 py-1" style="font-size: 11px;">₦0.00 (Cleared)</span>';
    }

    public function retail_invoices()
    {
        return $this->hasMany(Invoice::class)->where('in_department', 'retail');
    }

    public function wholesale_invoices()
    {
        return $this->hasMany(Invoice::class)->whereIn('in_department', ['bulksales', 'wholesales']);
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

    public function memberGroup()
    {
        return $this->belongsTo(MemberGroup::class, 'member_group_id');
    }

    public function retailMemberGroup()
    {
        return $this->belongsTo(MemberGroup::class, 'retail_member_group_id');
    }

    public function member_group()
    {
        return $this->belongsTo(MemberGroup::class, 'member_group_id');
    }

    public function retail_member_group()
    {
        return $this->belongsTo(MemberGroup::class, 'retail_member_group_id');
    }

    public function getBulkPushData() : array{
        return [
            'local_id'=>$this->id,
            'firstname'=> $this->firstname,
            'lastname'=>$this->lastname,
            'email'=>$this->email,
            'address'=>$this->address,
            'phone_number'=>$this->phone_number,
            'loyalty_points' => $this->loyalty_points,
            'retail_loyalty_points' => $this->retail_loyalty_points,
            'member_group_id' => $this->member_group_id,
            'retail_member_group_id' => $this->retail_member_group_id,
            'is_manual_member_group' => $this->is_manual_member_group,
            'is_manual_retail_member_group' => $this->is_manual_retail_member_group,
            'customer_type' => $this->customer_type,
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
