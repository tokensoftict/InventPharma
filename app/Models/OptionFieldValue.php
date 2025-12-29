<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class OptionFieldValue
 * 
 * @property int $id
 * @property int $option_field_id
 * @property bool $status
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property OptionField $option_field
 * @property Collection|StockOptionValue[] $stock_option_values
 *
 * @package App\Models
 */
class OptionFieldValue extends Model
{
	protected $table = 'option_field_values';

	protected $casts = [
		'option_field_id' => 'int',
		'status' => 'bool'
	];

	protected $fillable = [
		'option_field_id',
		'status',
		'name'
	];

	public function option_field()
	{
		return $this->belongsTo(OptionField::class);
	}

	public function stock_option_values()
	{
		return $this->hasMany(StockOptionValue::class);
	}
}
