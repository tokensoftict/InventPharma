<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Option
 * 
 * @property int $id
 * @property string $type
 * @property bool $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|OptionField[] $option_fields
 * @property Collection|Stock[] $stocks
 *
 * @package App\Models
 */
class Option extends Model
{
	protected $table = 'options';

	protected $casts = [
		'status' => 'bool'
	];

	protected $fillable = [
		'type',
		'status'
	];

	public function option_fields()
	{
		return $this->hasMany(OptionField::class);
	}

	public function stocks()
	{
		return $this->belongsToMany(Stock::class, 'stock_options')
					->withPivot('id')
					->withTimestamps();
	}
}
