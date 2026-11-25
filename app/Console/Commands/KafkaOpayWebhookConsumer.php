<?php

namespace App\Console\Commands;

use App\Enums\KafkaTopics;
use App\Services\Online\ProcessOpayWebhookService;
use Carbon\Exceptions\Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Junges\Kafka\Exceptions\ConsumerException;
use Junges\Kafka\Facades\Kafka;
use Throwable;

class KafkaOpayWebhookConsumer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kafka:consume-opay-webhooks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consume OPay webhook messages from Kafka and store them in opay_webhooks';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $consumer = Kafka::consumer([KafkaTopics::OPAY_KAFKA_TOPIC])
            ->withConsumerGroupId(config('kafka.consumer_group_id'))
            ->withHandler(function ($message) {
                try {
                    $topic = $message->getTopicName();
                    $body = $message->getBody();

                    if ($topic === KafkaTopics::OPAY_KAFKA_TOPIC && is_array($body)) {
                        ProcessOpayWebhookService::handle($body);
                    }
                } catch (Throwable $e) {
                    Log::error('[KafkaOpayWebhookConsumer] Error handling OPay webhook Kafka message: ' . $e->getMessage(), [
                        'topic' => method_exists($message, 'getTopicName') ? $message->getTopicName() : null,
                    ]);
                    report($e);
                }
            })
            ->build();

        try {
            $consumer->consume();
        } catch (Exception|ConsumerException $e) {
            Log::error('[KafkaOpayWebhookConsumer] Consumer error: ' . $e->getMessage());
            dump($e->getMessage());
        }
    }
}
