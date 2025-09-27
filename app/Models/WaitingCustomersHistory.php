<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class WaitingCustomersHistory
 * 
 * @property int $id
 * @property int $waiting_customer_id
 * @property int|null $waiting
 * @property int|null $picking
 * @property int|null $complete_picking
 * @property int|null $packing
 * @property int|null $packed
 * @property int|null $complete
 * @property int|null $dispatched
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property WaitingCustomer $waiting_customer
 *
 * @package App\Models
 */
class WaitingCustomersHistory extends Model
{
	protected $table = 'waiting_customers_histories';

	protected $casts = [
		'waiting_customer_id' => 'int',
		'waiting' => 'int',
		'picking' => 'int',
		'complete_picking' => 'int',
		'packing' => 'int',
		'packed' => 'int',
		'complete' => 'int',
		'dispatched' => 'int'
	];

	protected $fillable = [
		'waiting_customer_id',
		'waiting',
		'picking',
		'complete_picking',
		'packing',
		'packed',
		'complete',
		'dispatched'
	];

	public function waiting_customer()
	{
		return $this->belongsTo(WaitingCustomer::class);
	}
}
