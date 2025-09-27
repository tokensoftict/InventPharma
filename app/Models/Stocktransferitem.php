<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use App\Traits\ModelFilterTraits;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Stocktransferitem
 *
 * @property int $id
 * @property int $stocktransfer_id
 * @property int $stock_id
 * @property int $quantity
 * @property int $rem_quantity
 * @property float|null $selling_price
 * @property float|null $cost_price
 * @property int|null $stockbatch_id
 * @property int|null $user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $transfer_date
 *
 * @property Stock $stock
 * @property Stockbatch|null $stockbatch
 * @property Stocktransfer $stocktransfer
 * @property User|null $user
 *
 * @package App\Models
 */
class Stocktransferitem extends Model
{

    use ModelFilterTraits;

	protected $table = 'stocktransferitems';

	protected $casts = [
		'stocktransfer_id' => 'int',
		'stock_id' => 'int',
		'quantity' => 'int',
        'rem_quantity' => 'int',
		'selling_price' => 'float',
		'cost_price' => 'float',
		'stockbatch_id' => 'int',
		'user_id' => 'int'
	];

	protected $fillable = [
		'stocktransfer_id',
		'stock_id',
		'quantity',
		'selling_price',
		'cost_price',
		'stockbatch_id',
		'user_id',
        'rem_quantity',
        'transfer_date'
	];

    //protected $with = ['stock'];

    protected $appends = ['name','location', 'total'];

    public function getNameAttribute()
    {
        return $this->stock->name;
    }

    public function getLocationAttribute()
    {
        return $this->stock->location;
    }

    public function getTotalAttribute()
    {
        return $this->quantity * $this->selling_price;
    }

	public function stock()
	{
		return $this->belongsTo(Stock::class);
	}

	public function stockbatch()
	{
		return $this->belongsTo(Stockbatch::class);
	}

	public function stocktransfer()
	{
		return $this->belongsTo(Stocktransfer::class);
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
