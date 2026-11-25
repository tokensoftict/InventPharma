<?php

namespace Tests\Feature;

use App\Console\Commands\KafkaOpayWebhookConsumer;
use App\Enums\KafkaAction;
use App\Enums\KafkaTopics;
use App\Models\OpayPaymentLog;
use App\Models\OpayWebhook;
use App\Services\Online\ProcessOpayWebhookService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\ConsumedMessage;
use Tests\TestCase;

class OpayWebhookKafkaConsumerTest extends TestCase
{
    public function createApplication()
    {
        $app = require __DIR__ . '/../../bootstrap/app.php';

        if (! is_writable($app->storagePath())) {
            $tmpStorage = '/tmp/psgdc-kafka-opay-test-storage';
            foreach ([
                $tmpStorage,
                $tmpStorage . '/app',
                $tmpStorage . '/framework',
                $tmpStorage . '/framework/cache',
                $tmpStorage . '/framework/cache/data',
                $tmpStorage . '/framework/sessions',
                $tmpStorage . '/framework/views',
                $tmpStorage . '/logs',
            ] as $dir) {
                if (! is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }
            }
            $app->useStoragePath($tmpStorage);
        }

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        \DB::purge('sqlite');
        \DB::reconnect('sqlite');

        // Minimal stub tables so FK linking can be tested cleanly in sqlite :memory:
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->nullable();
            $table->timestamps();
        });

        Schema::create('opay_payment_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->string('order_no', 64)->nullable();
            $table->string('order_number', 64)->nullable();
            $table->string('transaction_number', 64)->nullable();
            $table->decimal('amount', 20, 2)->default(0);
            $table->unsignedBigInteger('amount_kobo')->default(0);
            $table->string('status', 32)->nullable();
            $table->timestamps();
        });

        $this->artisan('migrate', [
            '--path' => 'database/migrations/2026_11_24_131500_create_opay_webhooks_table.php',
        ]);
    }

    public function test_it_stores_opay_webhook_received_over_kafka_and_links_matching_payment_log(): void
    {
        $invoiceId = \DB::table('invoices')->insertGetId([
            'invoice_number' => 'INV-2026-001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $paymentLog = OpayPaymentLog::create([
            'invoice_id' => $invoiceId,
            'order_no' => 'ORD-POS-7788',
            'amount' => 300.00,
            'amount_kobo' => 30000,
            'status' => 'SUCCESS',
        ]);

        $kafkaBody = [
            'action' => KafkaAction::OPAY_WEBHOOK,
            'webhook' => [
                'mystore_webhook_log_id' => 42,
                'transaction_id' => 'TRAN-998877',
                'head_merchant_id' => '256622022480363',
                'merchant_id' => '256622022480364',
                'out_order_no' => 'ORD-POS-7788',
                'order_no' => '20261124741380171865833472',
                'pay_no' => 'PAY-554433',
                'status' => 'SUCCESS',
                'error_msg' => null,
                'amount' => '300.00',
                'fee' => '1.50',
                'fee_pattern' => 'IN_DEDUCT',
                'settlement_amount' => '298.50',
                'settlement_fee' => '1.50',
                'settlement_fee_pattern' => 'OUT_DEDUCT',
                'currency' => 'NGN',
                'pay_method' => 'BankCard',
                'sender_name' => 'JOHN DOE',
                'sender_bank' => 'ubaBank',
                'sender_account' => '2131****09',
                'recipient_name' => 'PS GENERAL DRUG CENTRE',
                'receipt_bank' => 'opay',
                'receipt_account' => '6151576968',
                'terminal_sn' => 'SN9210264890',
                'remark' => 'POS payment',
                'is_split' => 'N',
                'split_info' => null,
                'transaction_time' => '1732446000000',
                'completed_time' => '1732446005000',
                'signature_verified' => true,
                'source_ip' => '102.89.1.1',
                'received_at' => '2026-11-24T13:05:00+01:00',
                'headers' => ['x-opay-tranid' => 'TRAN-998877'],
                'raw_payload' => ['outOrderNo' => 'ORD-POS-7788', 'amount' => '300.00', 'status' => 'SUCCESS'],
            ],
        ];

        $stored = ProcessOpayWebhookService::handle($kafkaBody);

        $this->assertInstanceOf(OpayWebhook::class, $stored);
        $this->assertSame(1, OpayWebhook::count());
        $this->assertSame(42, $stored->mystore_webhook_log_id);
        $this->assertSame($paymentLog->id, $stored->opay_payment_log_id);
        $this->assertSame($invoiceId, $stored->invoice_id);
        $this->assertSame('TRAN-998877', $stored->transaction_id);
        $this->assertSame('ORD-POS-7788', $stored->out_order_no);
        $this->assertSame('20261124741380171865833472', $stored->order_no);
        $this->assertSame('SUCCESS', $stored->status);
        $this->assertSame(300.0, $stored->amount);
        $this->assertSame('SN9210264890', $stored->terminal_sn);
        $this->assertTrue($stored->signature_verified);

        // Replaying the same Kafka message with same mystore_webhook_log_id is idempotent
        $replayed = ProcessOpayWebhookService::handle($kafkaBody);
        $this->assertSame($stored->id, $replayed->id);
        $this->assertSame(1, OpayWebhook::count());
    }

    public function test_it_ignores_non_opay_webhook_kafka_actions(): void
    {
        $result = ProcessOpayWebhookService::handle([
            'action' => KafkaAction::PROCESS_ORDER,
            'order' => ['id' => 1],
        ]);

        $this->assertNull($result);
        $this->assertSame(0, OpayWebhook::count());
    }

    public function test_kafka_opay_webhook_consumer_consumes_opay_kafka_topic_and_persists_webhook(): void
    {
        $this->assertSame('OPAY_KAFKA_TOPIC', KafkaTopics::OPAY_KAFKA_TOPIC);

        Kafka::fake();
        Kafka::shouldReceiveMessages([
            new ConsumedMessage(
                topicName: KafkaTopics::OPAY_KAFKA_TOPIC,
                partition: 0,
                headers: ['action' => KafkaAction::OPAY_WEBHOOK],
                body: [
                    'action' => KafkaAction::OPAY_WEBHOOK,
                    'webhook' => [
                        'mystore_webhook_log_id' => 99,
                        'transaction_id' => 'TRAN-CONSUMER-99',
                        'out_order_no' => 'ORD-CONSUMER-99',
                        'order_no' => '202611240000999999',
                        'status' => 'SUCCESS',
                        'amount' => '1250.50',
                        'currency' => 'NGN',
                        'raw_payload' => ['outOrderNo' => 'ORD-CONSUMER-99', 'amount' => '1250.50'],
                    ],
                ],
                key: 'PSGDC',
                offset: 0,
                timestamp: time(),
            ),
        ]);

        $this->artisan('kafka:consume-opay-webhooks')->assertSuccessful();

        $stored = OpayWebhook::where('mystore_webhook_log_id', 99)->firstOrFail();
        $this->assertSame('TRAN-CONSUMER-99', $stored->transaction_id);
        $this->assertSame('ORD-CONSUMER-99', $stored->out_order_no);
        $this->assertSame(1250.5, $stored->amount);
    }
}
