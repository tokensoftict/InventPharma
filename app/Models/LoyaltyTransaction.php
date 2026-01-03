<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class LoyaltyTransaction
 * 
 * @property int $id
 * @property int $customer_id
 * @property float $points
 * @property string $type
 * @property string $action_type
 * @property int $action_id
 * @property string|null $reference
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Customer $customer
 *
 * @package App\Models
 */
class LoyaltyTransaction extends Model
{
	protected $table = 'loyalty_transactions';

	protected $casts = [
		'customer_id' => 'int',
		'points' => 'float',
		'action_id' => 'int'
	];

	protected $fillable = [
		'customer_id',
		'points',
		'type',
		'action_type',
		'action_id',
		'reference',
		'description'
	];

	public function customer()
	{
		return $this->belongsTo(Customer::class);
	}
}
