<?php

namespace App\Models;

use App\Traits\ModelFilterTraits;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpayPaymentLog extends Model
{
    use ModelFilterTraits;

    protected $table = 'opay_payment_logs';

    protected $casts = [
        'invoice_id' => 'int',
        'creditpaymentlog_id' => 'int',
        'payment_id' => 'int',
        'paymentmethoditem_id' => 'int',
        'customer_id' => 'int',
        'user_id' => 'int',
        'amount' => 'float',
        'amount_kobo' => 'int',
        'raw_payload' => 'array',
    ];

    protected $fillable = [
        'invoice_id',
        'creditpaymentlog_id',
        'payment_id',
        'paymentmethoditem_id',
        'customer_id',
        'user_id',
        'order_no',
        'order_number',
        'transaction_number',
        'terminal_sn',
        'cashier_method',
        'card_type',
        'card_number',
        'amount',
        'amount_kobo',
        'status',
        'response_code',
        'response_message',
        'raw_payload',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function creditpaymentlog(): BelongsTo
    {
        return $this->belongsTo(Creditpaymentlog::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function paymentmethoditem(): BelongsTo
    {
        return $this->belongsTo(Paymentmethoditem::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

