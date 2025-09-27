<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Promoitem
 * 
 * @property int $id
 * @property int $promo_id
 * @property int $stock_id
 * @property int|null $user_id
 * @property string $status
 * @property Carbon $from_date
 * @property Carbon $end_date
 * @property Carbon $created
 * @property float $whole_price
 * @property float $bulk_price
 * @property float $retail_price
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Promo $promo
 * @property Stock $stock
 *
 * @package App\Models
 */
class Promoitem extends Model
{
	protected $table = 'promoitems';

	protected $casts = [
		'promo_id' => 'int',
		'stock_id' => 'int',
		'user_id' => 'int',
		'from_date' => 'datetime',
		'end_date' => 'datetime',
		'created' => 'datetime',
		'whole_price' => 'float',
		'bulk_price' => 'float',
		'retail_price' => 'float'
	];

	protected $fillable = [
		'promo_id',
		'stock_id',
		'user_id',
		'status',
		'from_date',
		'end_date',
		'created',
		'whole_price',
		'bulk_price',
		'retail_price'
	];

	public function promo()
	{
		return $this->belongsTo(Promo::class);
	}

	public function stock()
	{
		return $this->belongsTo(Stock::class);
	}
}
