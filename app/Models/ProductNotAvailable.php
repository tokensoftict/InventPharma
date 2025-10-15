<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ProductNotAvailable
 * 
 * @property int $id
 * @property string $name
 * @property int $user_id
 * @property string $department
 * @property Carbon $date_time
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property User $user
 *
 * @package App\Models
 */
class ProductNotAvailable extends Model
{
	protected $table = 'product_not_availables';

	protected $casts = [
		'user_id' => 'int',
		'date_time' => 'datetime'
	];

	protected $fillable = [
		'name',
		'user_id',
		'department',
		'date_time'
	];

	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
