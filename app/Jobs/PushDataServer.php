<?php

namespace App\Jobs;

use App\Enums\KafkaEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Exception;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;

class PushDataServer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    var $data;
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if(config('app.sync_with_online') == 0)  return;
        //check if kafka is enabled and also currently available for connection
        if(config('app.KAFKA_STATUS') !== true) {
            _POST('update_data',$this->data);

        } else {
            $message = new Message(
                headers: ['event' => KafkaEvent::LOCAL_PUSH],
                body: [$this->data, "action" => $this->data['KAFKA_ACTION']],
                key: config('app.KAFKA_HEADER_KEY')
            );

            try {
                Kafka::publish()->onTopic($this->data['KAFKA_TOPICS'])->withMessage($message)->send();
            } catch (Exception $exception) {
                report($exception);
            }
        }

    }
}
