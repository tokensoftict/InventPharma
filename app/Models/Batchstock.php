<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Batchstock
 * 
 * @property int $id
 * @property int $stock_id
 * @property int|null $quantity
 * @property int|null $wholesales
 * @property int|null $bulksales
 * @property int|null $retail
 * @property int|null $quantity_user_id
 * @property int|null $bulk_user_id
 * @property int|null $wholsale_user_id
 * @property int|null $retail_user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property User|null $user
 * @property Stock $stock
 *
 * @package App\Models
 */
class Batchstock extends Model
{
	protected $table = 'batchstocks';

	protected $casts = [
		'stock_id' => 'int',
		'quantity' => 'int',
		'wholesales' => 'int',
		'bulksales' => 'int',
		'retail' => 'int',
		'quantity_user_id' => 'int',
		'bulk_user_id' => 'int',
		'wholsale_user_id' => 'int',
		'retail_user_id' => 'int',
        'retail_store_user_id' => 'int',
        'retail_store' => 'int',
	];

	protected $fillable = [
		'stock_id',
		'quantity',
		'wholesales',
		'bulksales',
		'retail',
		'quantity_user_id',
		'bulk_user_id',
		'wholsale_user_id',
		'retail_user_id',
        'retail_store_user_id',
        'retail_store'
	];

	public function wholsale_user()
	{
		return $this->belongsTo(User::class, 'wholsale_user_id');
	}

    public function quantity_user()
    {
        return $this->belongsTo(User::class, 'quantity_user_id');
    }

    public function bulk_user()
    {
        return $this->belongsTo(User::class, 'bulk_user_id');
    }


    public function retail_user()
    {
        return $this->belongsTo(User::class, 'retail_user_id');
    }

    public function retail_store_user()
    {
        return $this->belongsTo(User::class, 'retail_store_user_id');
    }

	public function stock()
	{
		return $this->belongsTo(Stock::class);
	}
}
