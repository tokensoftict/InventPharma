<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Invoiceprinthistory
 * 
 * @property int $id
 * @property int $invoice_id
 * @property Carbon|null $print_date
 * @property string $type
 * @property int $status_id
 * @property Carbon $print_time
 * @property int $user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Invoice $invoice
 * @property Status $status
 * @property User $user
 *
 * @package App\Models
 */
class Invoiceprinthistory extends Model
{
	protected $table = 'invoiceprinthistories';

	protected $casts = [
		'invoice_id' => 'int',
		'print_date' => 'datetime',
		'status_id' => 'int',
		'print_time' => 'datetime',
		'user_id' => 'int'
	];

	protected $fillable = [
		'invoice_id',
		'print_date',
		'type',
		'status_id',
		'print_time',
		'user_id'
	];

	public function invoice()
	{
		return $this->belongsTo(Invoice::class);
	}

	public function status()
	{
		return $this->belongsTo(Status::class);
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
