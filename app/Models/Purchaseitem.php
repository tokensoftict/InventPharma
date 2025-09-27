<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use App\Traits\ModelFilterTraits;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Purchaseitem
 *
 * @property int $id
 * @property int $purchase_id
 * @property int|null $stock_id
 * @property Carbon|null $expiry_date
 * @property int $qty
 * @property float|null $cost_price
 * @property int|null $user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Purchase $purchase
 * @property Stock|null $stock
 * @property User|null $user
 *
 * @package App\Models
 */
class Purchaseitem extends Model
{

    use ModelFilterTraits;

	protected $table = 'purchaseitems';

	protected $casts = [
		'purchase_id' => 'int',
		'stock_id' => 'int',
		'qty' => 'int',
		'cost_price' => 'float',
		'user_id' => 'int'
	];

	protected $fillable = [
		'purchase_id',
		'stock_id',
		'expiry_date',
		'qty',
		'cost_price',
		'user_id',
        'batch_no'
	];

    //protected $with = ['stock'];

    protected $appends = ['name' ,'total'];

    public function getNameAttribute()
    {
        return $this->stock->name;
    }

    public function getTotalAttribute()
    {
        return $this->qty * $this->cost_price;
    }

	public function purchase()
	{
		return $this->belongsTo(Purchase::class);
	}

	public function stock()
	{
		return $this->belongsTo(Stock::class);
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
