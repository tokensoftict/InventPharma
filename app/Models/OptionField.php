<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class OptionField
 * 
 * @property int $id
 * @property int $option_id
 * @property bool $status
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Option $option
 * @property Collection|OptionFieldValue[] $option_field_values
 *
 * @package App\Models
 */
class OptionField extends Model
{
	protected $table = 'option_fields';

	protected $casts = [
		'option_id' => 'int',
		'status' => 'bool'
	];

	protected $fillable = [
		'option_id',
		'status',
		'name'
	];

	public function option()
	{
		return $this->belongsTo(Option::class);
	}

	public function option_field_values()
	{
		return $this->hasMany(OptionFieldValue::class);
	}
}
