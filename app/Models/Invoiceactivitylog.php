<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use App\Traits\ModelFilterTraits;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Invoiceactivitylog
 *
 * @property int $id
 * @property int $invoice_id
 * @property string|null $invoice_number
 * @property string|null $activity
 * @property int|null $user_id
 * @property Carbon $activity_date
 * @property Carbon $activity_time
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Invoice $invoice
 * @property User|null $user
 *
 * @package App\Models
 */
class Invoiceactivitylog extends Model
{
    use ModelFilterTraits;

	protected $table = 'invoiceactivitylogs';

	protected $casts = [
		'invoice_id' => 'int',
		'user_id' => 'int',
		'activity_date' => 'datetime',
		'activity_time' => 'datetime'
	];

	protected $fillable = [
		'invoice_id',
		'invoice_number',
		'activity',
		'user_id',
		'activity_date',
		'activity_time'
	];

	public function invoice()
	{
		return $this->belongsTo(Invoice::class);
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
