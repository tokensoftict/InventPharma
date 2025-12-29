<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class StockOptionValue
 * 
 * @property int $id
 * @property int $stock_option_id
 * @property int $option_field_value_id
 * @property int $stock_id
 * @property int $option_id
 * @property bool $status
 * @property bool $subtract
 * @property float $retail_price
 * @property string $retail_price_prefix
 * @property float $wholesales_price
 * @property string $wholesales_price_prefix
 * @property bool $required
 * @property bool $retail_status
 * @property bool $wholesales_status
 * @property int $quantity
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property OptionFieldValue $option_field_value
 * @property Option $option
 * @property Stock $stock
 * @property StockOption $stock_option
 *
 * @package App\Models
 */
class StockOptionValue extends Model
{
	protected $table = 'stock_option_values';

	protected $casts = [
		'stock_option_id' => 'int',
		'option_field_value_id' => 'int',
		'stock_id' => 'int',
		'option_id' => 'int',
		'status' => 'bool',
		'subtract' => 'bool',
		'retail_price' => 'float',
		'wholesales_price' => 'float',
		'required' => 'bool',
		'quantity' => 'int',
        'retail_status' => 'int',
        'wholesales_status' => 'int',
	];

	protected $fillable = [
		'stock_option_id',
		'option_field_value_id',
		'stock_id',
		'option_id',
		'status',
		'subtract',
		'retail_price',
		'retail_price_prefix',
		'wholesales_price',
		'wholesales_price_prefix',
		'required',
		'quantity',
        'wholesales_status',
        'retail_status'
	];

	public function option_field_value()
	{
		return $this->belongsTo(OptionFieldValue::class);
	}

	public function option()
	{
		return $this->belongsTo(Option::class);
	}

	public function stock()
	{
		return $this->belongsTo(Stock::class);
	}

	public function stock_option()
	{
		return $this->belongsTo(StockOption::class);
	}
}
