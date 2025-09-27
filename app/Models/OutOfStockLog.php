<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class OutOfStockLog
 * 
 * @property int $id
 * @property int $stock_id
 * @property int $user_id
 * @property int|null $last_click_user_id
 * @property string $department
 * @property int $clicks
 * @property Carbon $last_time_click
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Stock $stock
 * @property User $user
 *
 * @package App\Models
 */
class OutOfStockLog extends Model
{
	protected $table = 'out_of_stock_logs';

	protected $casts = [
		'stock_id' => 'int',
		'user_id' => 'int',
		'last_click_user_id' => 'int',
		'clicks' => 'int',
		'last_time_click' => 'datetime'
	];

	protected $fillable = [
		'stock_id',
		'user_id',
		'last_click_user_id',
		'department',
		'clicks',
		'last_time_click'
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
