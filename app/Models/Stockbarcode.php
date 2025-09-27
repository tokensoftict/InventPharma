<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Stockbarcode
 * 
 * @property int $id
 * @property string $barcode
 * @property int $stock_id
 * @property int $user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Stock $stock
 * @property User $user
 *
 * @package App\Models
 */
class Stockbarcode extends Model
{
	protected $table = 'stockbarcodes';

	protected $casts = [
		'stock_id' => 'int',
		'user_id' => 'int'
	];

	protected $fillable = [
		'barcode',
		'stock_id',
		'user_id'
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
