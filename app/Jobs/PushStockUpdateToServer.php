<?php

namespace App\Jobs;

use App\Enums\KafkaAction;
use App\Enums\KafkaEvent;
use App\Enums\KafkaTopics;
use App\Models\Stock;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;

class PushStockUpdateToServer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    var $stock_array;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($_stock_array)
    {
        $this->stock_array = $_stock_array;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if(config('app.sync_with_online')== 0)  return;

        $items = Stock::whereIn('id',$this->stock_array)->get();
        $stock = [];
        $oldStock = [];

        foreach ($items as $item){
            $stock[] = $item->getBulkPushData();
            $oldStock[] = $item->getBulkOldPushData();
        }

        $data = ['KAFKA_ACTION' => KafkaAction::UPDATE_STOCK, 'KAFKA_TOPICS'=> KafkaTopics::STOCKS, 'action' => 'update', 'table' => 'stock', 'data' => $stock, 'url'=>onlineBase()."dataupdate/add_or_update_stock"];


        if(config('app.KAFKA_STATUS') !== true) {

            _POST('add_or_update_stock',$data);

        } else {

            $message = new Message(
                headers: ['event' => KafkaEvent::LOCAL_PUSH],
                body: [$data, "action" => $data['KAFKA_ACTION']],
                key: config('app.KAFKA_HEADER_KEY')
            );

            try {
                Kafka::publish()->onTopic($data['KAFKA_TOPICS'])->withMessage($message)->send();
            } catch (Exception $exception) {
                report($exception);
            }

            $data = ['action' => 'update', 'table' => 'stock', 'data' => $oldStock, 'url'=>onlineBase()."dataupdate/add_or_update_stock"];
            _POST('add_or_update_stock',$data);
        }

    }
}
