<?php

namespace App\Console\Commands;

use App\Enums\KafkaTopics;
use App\Services\Online\ProcessOrderService;
use Carbon\Exceptions\Exception;
use Illuminate\Console\Command;
use Junges\Kafka\Exceptions\ConsumerException;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Contracts\ConsumerMessage;

class KafkaConsumer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kafka:consume';
    protected $description = 'Consume messages from Kafka topics';

    /**
     * The console command description.
     *
     * @var string
     */

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $consumer = Kafka::consumer([KafkaTopics::GENERAL, KafkaTopics::ORDERS, KafkaTopics::STOCKS])
            ->withConsumerGroupId(config('kafka.consumer_group_id'))
            ->withHandler(function ($message) {
                $topic = $message->getTopicName();
                switch ($topic) {
                    case KafkaTopics::ORDERS:
                        ProcessOrderService::handle($message->getBody());
                        break;
                    default:
                }
            })
            ->build();

        try {
            $consumer->consume();
        } catch (Exception|ConsumerException $e) {
            dump($e->getMessage());
        }

    }
}
