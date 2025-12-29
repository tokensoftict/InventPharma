<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class StockOption
 * 
 * @property int $id
 * @property int $stock_id
 * @property int $option_field_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property OptionField $option_field
 * @property Stock $stock
 * @property Collection|StockOptionValue[] $stock_option_values
 *
 * @package App\Models
 */
class StockOption extends Model
{
	protected $table = 'stock_options';

	protected $casts = [
		'stock_id' => 'int',
		'option_field_id' => 'int'
	];

	protected $fillable = [
		'stock_id',
		'option_field_id'
	];

	public function option_field()
	{
		return $this->belongsTo(OptionField::class);
	}

	public function stock()
	{
		return $this->belongsTo(Stock::class);
	}

	public function stock_option_values()
	{
		return $this->hasMany(StockOptionValue::class);
	}
}
