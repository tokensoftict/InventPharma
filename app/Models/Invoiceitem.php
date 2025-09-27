<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use App\Traits\ModelFilterTraits;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Invoiceitem
 *
 * @property int $id
 * @property int $invoice_id
 * @property int|null $stock_id
 * @property int $quantity
 * @property int|null $customer_id
 * @property int $added_by
 * @property int|null $discount_added_by
 * @property float|null $cost_price
 * @property float|null $selling_price
 * @property string|null $department
 * @property float|null $profit
 * @property string|null $discount_type
 * @property float $discount_value
 * @property float|null $discount_amount
 * @property int|null $before_customer_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property User|null $user
 * @property Customer|null $customer
 * @property Invoice $invoice
 * @property Stock|null $stock
 * @property Collection|Invoiceitembatch[] $invoiceitembatches
 *
 * @package App\Models
 */
class Invoiceitem extends Model
{

    use ModelFilterTraits;

	protected $table = 'invoiceitems';

	protected $casts = [
		'invoice_id' => 'int',
		'stock_id' => 'int',
		'quantity' => 'int',
		'customer_id' => 'int',
		'added_by' => 'int',
		'discount_added_by' => 'int',
		'cost_price' => 'float',
		'selling_price' => 'float',
		'profit' => 'float',
		'discount_value' => 'float',
		'discount_amount' => 'float',
		'before_customer_id' => 'int'
	];

	protected $fillable = [
		'invoice_id',
		'stock_id',
		'quantity',
		'customer_id',
		'added_by',
		'discount_added_by',
		'cost_price',
		'selling_price',
		'department',
		'profit',
		'discount_type',
		'discount_value',
		'discount_amount',
		'before_customer_id'
	];

    protected $with = ['stock'];

    protected $appends = ['box','name','av_qty', 'carton'];

    public function getBoxAttribute()
    {
        return $this->stock->box ?? 0;
    }

    public function getNameAttribute()
    {
        return $this->stock->name ?? "";
    }

    public function getAvQtyAttribute()
    {
        if(!$this->quantity) return 0;
        return $this->stock->{$this->department} + $this->quantity;
    }

    public function getCartonAttribute()
    {
        return $this->stock->carton ?? 0;
    }

    public function customer()
	{
		return $this->belongsTo(Customer::class);
	}

	public function invoice()
	{
		return $this->belongsTo(Invoice::class);
	}

	public function stock()
	{
		return $this->belongsTo(Stock::class);
	}

	public function invoiceitembatches()
	{
		return $this->hasMany(Invoiceitembatch::class);
	}

    public function user()
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
