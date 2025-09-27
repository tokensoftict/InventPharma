<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Promotion
 * 
 * @property int $id
 * @property string|null $name
 * @property int|null $user_id
 * @property int $status_id
 * @property Carbon $from_date
 * @property Carbon $end_date
 * @property Carbon $created
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Status $status
 * @property User|null $user
 * @property Collection|PromotionItem[] $promotion_items
 *
 * @package App\Models
 */
class Promotion extends Model
{
	protected $table = 'promotions';

	protected $casts = [
		'user_id' => 'int',
		'status_id' => 'int',
		'from_date' => 'datetime',
		'end_date' => 'datetime',
		'created' => 'datetime'
	];

	protected $fillable = [
		'name',
		'user_id',
		'status_id',
		'from_date',
		'end_date',
		'created'
	];

	public function status()
	{
		return $this->belongsTo(Status::class);
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}

	public function promotion_items()
	{
		return $this->hasMany(PromotionItem::class);
	}
}
