<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class MultipleInvoiceScanReport
 * 
 * @property int $id
 * @property int $user_id
 * @property int $invoice_id
 * @property string $invoice_number
 * @property Carbon|null $scan_date
 * @property Carbon|null $scan_time
 * @property int $no_of_items
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class MultipleInvoiceScanReport extends Model
{
	protected $table = 'multiple_invoice_scan_reports';

	protected $casts = [
		'user_id' => 'int',
		'invoice_id' => 'int',
		'scan_date' => 'datetime',
		'scan_time' => 'datetime',
		'no_of_items' => 'int'
	];

	protected $fillable = [
		'user_id',
		'invoice_id',
		'invoice_number',
		'scan_date',
		'scan_time',
		'no_of_items'
	];


    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
