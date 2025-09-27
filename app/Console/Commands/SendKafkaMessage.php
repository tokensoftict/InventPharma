<?php

namespace App\Console\Commands;

use App\Enums\KafkaAction;
use App\Enums\KafkaEvent;
use App\Enums\KafkaTopics;
use Illuminate\Console\Command;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;

class SendKafkaMessage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-kafka-message';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $message = new Message(
            headers: ['event' => KafkaEvent::LOCAL_PUSH],
            body: [['KAFKA_ACTION'=> KafkaAction::CREATE_STOCK_GROUP, 'KAFKA_TOPICS'=>KafkaTopics::GENERAL, 'action'=>'new','table'=>'stock_groups', 'endpoint' => 'productgroups' ,'data'=>[
                ["id"=>1, "name"=>"Hello World", "status"=>true],
                ["id"=>2, "name"=>"Hello World 2", "status"=>true],
                ["id"=>3, "name"=>"Hello World 3", "status"=>true],
                ["id"=>4, "name"=>"Hello World 4", "status"=>true],
            ]]
                , "action" => KafkaAction::CREATE_MANUFACTURER],
            key: config('app.KAFKA_HEADER_KEY')
        );

        $status = Kafka::publish()->onTopic(KafkaTopics::GENERAL)->withMessage($message)->send();
        dump($status);
    }
}
