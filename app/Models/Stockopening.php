<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Stockopening
 * 
 * @property int $id
 * @property int $stock_id
 * @property float|null $average_retail_cost_price
 * @property float|null $average_cost_price
 * @property int|null $wholesales
 * @property int|null $bulksales
 * @property int|null $retail
 * @property int|null $quantity
 * @property int|null $supplier_id
 * @property int|null $total
 * @property Carbon|null $date_added
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Stock $stock
 * @property Supplier|null $supplier
 *
 * @package App\Models
 */
class Stockopening extends Model
{
	protected $table = 'stockopenings';

	protected $casts = [
		'stock_id' => 'int',
		'average_retail_cost_price' => 'float',
		'average_cost_price' => 'float',
		'wholesales' => 'int',
		'bulksales' => 'int',
		'retail' => 'int',
		'quantity' => 'int',
		'supplier_id' => 'int',
		'total' => 'float',
		'date_added' => 'datetime'
	];

	protected $fillable = [
		'stock_id',
		'average_retail_cost_price',
		'average_cost_price',
		'wholesales',
		'bulksales',
		'retail',
		'quantity',
		'supplier_id',
		'total',
		'date_added'
	];

	public function stock()
	{
		return $this->belongsTo(Stock::class);
	}

	public function supplier()
	{
		return $this->belongsTo(Supplier::class);
	}
}
