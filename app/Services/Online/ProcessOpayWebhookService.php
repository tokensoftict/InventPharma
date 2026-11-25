<?php

namespace App\Services\Online;

use App\Enums\KafkaAction;
use App\Models\OpayPaymentLog;
use App\Models\OpayWebhook;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Consumes OPay webhook events forwarded from `mystore` over Kafka and
 * persists them into the local `opay_webhooks` table.
 */
class ProcessOpayWebhookService
{
    /**
     * Handle an incoming Kafka message body carrying an OPay webhook.
     */
    public static function handle(array $body): ?OpayWebhook
    {
        if (($body['action'] ?? null) !== KafkaAction::OPAY_WEBHOOK) {
            return null;
        }

        $webhook = is_array($body['webhook'] ?? null)
            ? $body['webhook']
            : (is_array($body['data'] ?? null) ? $body['data'] : []);

        $rawPayload = is_array($webhook['raw_payload'] ?? null)
            ? $webhook['raw_payload']
            : $webhook;

        $outOrderNo = self::stringOrNull($webhook['out_order_no'] ?? ($rawPayload['outOrderNo'] ?? null));
        $orderNo = self::stringOrNull($webhook['order_no'] ?? ($rawPayload['orderNo'] ?? null));
        $payNo = self::stringOrNull($webhook['pay_no'] ?? ($rawPayload['payNo'] ?? null));

        // Attempt to link this webhook to an existing local OpayPaymentLog / Invoice if present.
        $linkedLog = self::findMatchingPaymentLog($outOrderNo, $orderNo, $payNo);

        $attributes = [
            'mystore_webhook_log_id' => isset($webhook['mystore_webhook_log_id']) && is_numeric($webhook['mystore_webhook_log_id'])
                ? (int) $webhook['mystore_webhook_log_id']
                : null,
            'opay_payment_log_id' => $linkedLog?->id,
            'invoice_id' => $linkedLog?->invoice_id,
            'transaction_id' => self::stringOrNull($webhook['transaction_id'] ?? ($rawPayload['payNo'] ?? null)),
            'head_merchant_id' => self::stringOrNull($webhook['head_merchant_id'] ?? ($rawPayload['headMerchantId'] ?? null)),
            'merchant_id' => self::stringOrNull($webhook['merchant_id'] ?? ($rawPayload['merchantId'] ?? null)),
            'out_order_no' => $outOrderNo,
            'order_no' => $orderNo,
            'pay_no' => $payNo,
            'status' => self::stringOrNull($webhook['status'] ?? ($rawPayload['status'] ?? null)),
            'error_msg' => self::stringOrNull($webhook['error_msg'] ?? ($rawPayload['errorMsg'] ?? null)),
            'amount' => self::decimalOrNull($webhook['amount'] ?? ($rawPayload['amount'] ?? null)),
            'fee' => self::decimalOrNull($webhook['fee'] ?? ($rawPayload['fee'] ?? null)),
            'fee_pattern' => self::stringOrNull($webhook['fee_pattern'] ?? ($rawPayload['feePattern'] ?? null)),
            'settlement_amount' => self::decimalOrNull($webhook['settlement_amount'] ?? ($rawPayload['settlementAmount'] ?? null)),
            'settlement_fee' => self::decimalOrNull($webhook['settlement_fee'] ?? ($rawPayload['settlementFee'] ?? null)),
            'settlement_fee_pattern' => self::stringOrNull($webhook['settlement_fee_pattern'] ?? ($rawPayload['settlementFeePattern'] ?? null)),
            'currency' => self::stringOrNull($webhook['currency'] ?? ($rawPayload['currency'] ?? null)) ?? 'NGN',
            'pay_method' => self::stringOrNull($webhook['pay_method'] ?? ($rawPayload['payMethod'] ?? null)),
            'sender_name' => self::stringOrNull($webhook['sender_name'] ?? ($rawPayload['senderName'] ?? null)),
            'sender_bank' => self::stringOrNull($webhook['sender_bank'] ?? ($rawPayload['senderBank'] ?? null)),
            'sender_account' => self::stringOrNull($webhook['sender_account'] ?? ($rawPayload['senderAccount'] ?? null)),
            'recipient_name' => self::stringOrNull($webhook['recipient_name'] ?? ($rawPayload['recipientName'] ?? null)),
            'receipt_bank' => self::stringOrNull($webhook['receipt_bank'] ?? ($rawPayload['receiptBank'] ?? null)),
            'receipt_account' => self::stringOrNull($webhook['receipt_account'] ?? ($rawPayload['receiptAccount'] ?? null)),
            'terminal_sn' => self::stringOrNull($webhook['terminal_sn'] ?? ($rawPayload['sn'] ?? null)),
            'remark' => self::stringOrNull($webhook['remark'] ?? ($rawPayload['remark'] ?? null)),
            'is_split' => self::stringOrNull($webhook['is_split'] ?? ($rawPayload['isSplit'] ?? null)),
            'split_info' => is_array($webhook['split_info'] ?? null)
                ? $webhook['split_info']
                : (is_array($rawPayload['splitInfo'] ?? null) ? $rawPayload['splitInfo'] : null),
            'product_info' => self::stringOrJson($webhook['product_info'] ?? ($rawPayload['productInfo'] ?? null)),
            'additional_information' => self::stringOrJson($webhook['additional_information'] ?? ($rawPayload['additionalInformation'] ?? null)),
            'transaction_time' => self::stringOrNull($webhook['transaction_time'] ?? ($rawPayload['transactionTime'] ?? null)),
            'completed_time' => self::stringOrNull($webhook['completed_time'] ?? ($rawPayload['completedTime'] ?? null)),
            'signature_verified' => array_key_exists('signature_verified', $webhook) ? $webhook['signature_verified'] : null,
            'source_ip' => self::stringOrNull($webhook['source_ip'] ?? null),
            'received_at' => self::parseTimestamp($webhook['received_at'] ?? null),
            'headers' => is_array($webhook['headers'] ?? null) ? $webhook['headers'] : null,
            'raw_payload' => $rawPayload,
        ];

        // Deduplicate on mystore_webhook_log_id when Kafka replays an already-stored event.
        if ($attributes['mystore_webhook_log_id'] !== null) {
            return OpayWebhook::updateOrCreate(
                ['mystore_webhook_log_id' => $attributes['mystore_webhook_log_id']],
                $attributes,
            );
        }

        return OpayWebhook::create($attributes);
    }

    private static function findMatchingPaymentLog(?string $outOrderNo, ?string $orderNo, ?string $payNo): ?OpayPaymentLog
    {
        $candidates = array_values(array_unique(array_filter([$outOrderNo, $orderNo, $payNo])));
        if ($candidates === [] || ! Schema::hasTable('opay_payment_logs')) {
            return null;
        }

        try {
            return OpayPaymentLog::query()
                ->where(function ($query) use ($candidates) {
                    $query->whereIn('order_no', $candidates)
                        ->orWhereIn('order_number', $candidates)
                        ->orWhereIn('transaction_number', $candidates);
                })
                ->latest('id')
                ->first();
        } catch (Throwable $e) {
            Log::warning('[ProcessOpayWebhookService] Failed looking up matching OpayPaymentLog: ' . $e->getMessage());

            return null;
        }
    }

    private static function stringOrNull(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_scalar($value) ? (string) $value : null;
    }

    private static function stringOrJson(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            $encoded = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            return $encoded !== false ? $encoded : null;
        }

        return is_scalar($value) ? (string) $value : null;
    }

    private static function decimalOrNull(mixed $value): ?float
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private static function parseTimestamp(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return now();
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return now();
        }
    }
}
