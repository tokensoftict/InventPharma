<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Onlineordertotal
 * 
 * @property int $id
 * @property int $invoice_id
 * @property string|null $name
 * @property float|null $value
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Invoice $invoice
 *
 * @package App\Models
 */
class Onlineordertotal extends Model
{
	protected $table = 'onlineordertotals';

	protected $casts = [
		'invoice_id' => 'int',
		'value' => 'float'
	];

	protected $fillable = [
		'invoice_id',
		'name',
		'value'
	];

	public function invoice()
	{
		return $this->belongsTo(Invoice::class);
	}
}
