<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpayWebhook extends Model
{
    protected $table = 'opay_webhooks';

    protected $casts = [
        'mystore_webhook_log_id' => 'int',
        'opay_payment_log_id' => 'int',
        'invoice_id' => 'int',
        'amount' => 'float',
        'fee' => 'float',
        'settlement_amount' => 'float',
        'settlement_fee' => 'float',
        'signature_verified' => 'boolean',
        'received_at' => 'datetime',
        'split_info' => 'array',
        'headers' => 'array',
        'raw_payload' => 'array',
    ];

    protected $fillable = [
        'mystore_webhook_log_id',
        'opay_payment_log_id',
        'invoice_id',
        'transaction_id',
        'head_merchant_id',
        'merchant_id',
        'out_order_no',
        'order_no',
        'pay_no',
        'status',
        'error_msg',
        'amount',
        'fee',
        'fee_pattern',
        'settlement_amount',
        'settlement_fee',
        'settlement_fee_pattern',
        'currency',
        'pay_method',
        'sender_name',
        'sender_bank',
        'sender_account',
        'recipient_name',
        'receipt_bank',
        'receipt_account',
        'terminal_sn',
        'remark',
        'is_split',
        'split_info',
        'product_info',
        'additional_information',
        'transaction_time',
        'completed_time',
        'signature_verified',
        'source_ip',
        'received_at',
        'headers',
        'raw_payload',
    ];

    public function opayPaymentLog(): BelongsTo
    {
        return $this->belongsTo(OpayPaymentLog::class, 'opay_payment_log_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }
}
