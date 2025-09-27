<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use App\Enums\InvoicePaymentApprovalTypeEnum;
use App\Enums\InvoicePaymentApprovalTypeStatusEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class InvoicePaymentApprovalStatus
 * 
 * @property int $id
 * @property InvoicePaymentApprovalTypeEnum $type
 * @property Carbon $date
 * @property Carbon $date_added
 * @property float $amount
 * @property string|null $comment
 * @property int $bank_id
 * @property int $invoice_id
 * @property int $payment_id
 * @property int $user_id
 * @property int $resolved_by_id
 * @property int $paymentmethoditem_id
 * @property InvoicePaymentApprovalTypeStatusEnum $approval_status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Bank $bank
 * @property Invoice $invoice
 * @property Payment $payment
 * @property Paymentmethoditem $paymentmethoditem
 *
 * @package App\Models
 */
class InvoicePaymentApprovalStatus extends Model
{
	protected $table = 'invoice_payment_approval_statuses';

	protected $casts = [
		'date' => 'datetime',
		'date_added' => 'datetime',
		'amount' => 'float',
		'bank_id' => 'int',
		'invoice_id' => 'int',
		'payment_id' => 'int',
		'paymentmethoditem_id' => 'int',
        'approval_status' => InvoicePaymentApprovalTypeStatusEnum::class,
        'type' => InvoicePaymentApprovalTypeEnum::class,
        'user_id' => 'int',
        'resolved_by_id' => 'int'
	];

	protected $fillable = [
		'type',
		'date',
		'date_added',
		'amount',
		'comment',
		'bank_id',
		'invoice_id',
		'payment_id',
		'paymentmethoditem_id',
		'approval_status',
        'user_id',
        'resolved_by_id'
	];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function resolved_by()
    {
        return $this->belongsTo(User::class);
    }

	public function bank()
	{
		return $this->belongsTo(Bank::class);
	}

	public function invoice()
	{
		return $this->belongsTo(Invoice::class);
	}

	public function payment()
	{
		return $this->belongsTo(Payment::class);
	}

	public function paymentmethoditem()
	{
		return $this->belongsTo(Paymentmethoditem::class);
	}
}
