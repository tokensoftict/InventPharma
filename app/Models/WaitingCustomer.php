<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use App\Traits\ModelFilterTraits;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class WaitingCustomer
 * 
 * @property int $id
 * @property int $invoice_id
 * @property int|null $invoice_number
 * @property int $customer_id
 * @property Carbon $date_added
 * @property string $status
 * @property Carbon|null $entered_at
 * @property Carbon|null $processed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Customer $customer
 * @property Invoice $invoice
 *
 * @package App\Models
 */
class WaitingCustomer extends Model
{
    use ModelFilterTraits;

	protected $table = 'waiting_customers';

    public static array $waitingInvoiceStatus = [
        'waiting' => 'Waiting',
        'picking' => 'Picking',
        'complete_picking' => 'Picking Completed',
        'packing' => 'Now Packing',
        'packed' => 'Packing Completed',
        'complete' => 'Complete',
        'dispatched' => 'Dispatched',
    ];


	protected $casts = [
		'invoice_id' => 'int',
		'invoice_number' => 'int',
		'customer_id' => 'int',
		'date_added' => 'datetime',
		'entered_at' => 'datetime',
		'processed_at' => 'datetime'
	];

	protected $fillable = [
		'invoice_id',
		'invoice_number',
		'customer_id',
		'date_added',
		'status',
		'entered_at',
		'processed_at'
	];

	public function customer()
	{
		return $this->belongsTo(Customer::class);
	}

	public function invoice()
	{
		return $this->belongsTo(Invoice::class);
	}
}
