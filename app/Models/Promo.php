<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Promo
 * 
 * @property int $id
 * @property string|null $name
 * @property int|null $user_id
 * @property string $status
 * @property Carbon $from_date
 * @property Carbon $end_date
 * @property Carbon $created
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property User|null $user
 * @property Collection|Promoitem[] $promoitems
 *
 * @package App\Models
 */
class Promo extends Model
{
	protected $table = 'promos';

	protected $casts = [
		'user_id' => 'int',
		'from_date' => 'datetime',
		'end_date' => 'datetime',
		'created' => 'datetime'
	];

	protected $fillable = [
		'name',
		'user_id',
		'status',
		'from_date',
		'end_date',
		'created'
	];

	public function user()
	{
		return $this->belongsTo(User::class);
	}

	public function promoitems()
	{
		return $this->hasMany(Promoitem::class);
	}
}
