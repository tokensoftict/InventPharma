<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class PromotionItem
 * 
 * @property int $id
 * @property int $promotion_id
 * @property int $stock_id
 * @property int|null $user_id
 * @property int $status_id
 * @property Carbon $from_date
 * @property Carbon $end_date
 * @property Carbon $created
 * @property float $whole_price
 * @property float $bulk_price
 * @property float $retail_price
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Promotion $promotion
 * @property Status $status
 * @property Stock $stock
 * @property User|null $user
 *
 * @package App\Models
 */
class PromotionItem extends Model
{
	protected $table = 'promotion_items';

	protected $casts = [
		'promotion_id' => 'int',
		'stock_id' => 'int',
		'user_id' => 'int',
		'status_id' => 'int',
		'from_date' => 'datetime',
		'end_date' => 'datetime',
		'created' => 'datetime',
		'whole_price' => 'float',
		'bulk_price' => 'float',
		'retail_price' => 'float'
	];

	protected $fillable = [
		'promotion_id',
		'stock_id',
		'user_id',
		'status_id',
		'from_date',
		'end_date',
		'created',
		'whole_price',
		'bulk_price',
		'retail_price'
	];

	public function promotion()
	{
		return $this->belongsTo(Promotion::class);
	}

	public function status()
	{
		return $this->belongsTo(Status::class);
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
