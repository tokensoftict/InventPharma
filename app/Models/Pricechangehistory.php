<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use App\Traits\ModelFilterTraits;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Pricechangehistory
 * 
 * @property int $id
 * @property int|null $user_id
 * @property float|null $from
 * @property float|null $to
 * @property int $stock_id
 * @property string $department
 * @property Carbon $change_date
 * @property Carbon $change_time
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Stock $stock
 * @property User|null $user
 *
 * @package App\Models
 */
class Pricechangehistory extends Model
{

    use ModelFilterTraits;

	protected $table = 'pricechangehistories';

	protected $casts = [
		'user_id' => 'int',
		//'from' => 'float',
		//'to' => 'float',
		'stock_id' => 'int',
		'change_date' => 'datetime',
		'change_time' => 'datetime'
	];

	protected $fillable = [
		'user_id',
		'from',
		'to',
		'stock_id',
		'department',
		'change_date',
		'change_time'
	];

	public function stock()
	{
		return $this->belongsTo(Stock::class);
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
