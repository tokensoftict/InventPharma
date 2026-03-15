<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class CustomPrice
 * 
 * @property int $id
 * @property string $name
 * @property string $department
 * @property bool $status
 * @property bool $default_price
 * @property string $user_id
 * @property string $role_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class CustomPrice extends Model
{
	protected $table = 'custom_prices';

	protected $casts = [
		'status' => 'bool',
		'default_price' => 'bool'
	];

	protected $fillable = [
		'name',
		'department',
		'status',
		'default_price',
		'user_id',
		'role_id'
	];
}
