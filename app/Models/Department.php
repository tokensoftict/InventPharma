<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use App\Traits\ModelFilterTraits;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Department
 *
 * @property int $id
 * @property string $name
 * @property string|null $label
 * @property string|null $quantity_column
 * @property string|null $price_column
 * @property string|null $type
 * @property bool $status
 * @property string|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Collection|User[] $users
 *
 * @package App\Models
 */
class Department extends Model
{

    use  ModelFilterTraits;


	protected $table = 'departments';

	protected $casts = [
		'status' => 'bool'
	];

	protected $fillable = [
		'name',
		'label',
		'quantity_column',
		'price_column',
		'type',
		'status'
	];

	public function users()
	{
		return $this->hasMany(User::class);
	}
}
