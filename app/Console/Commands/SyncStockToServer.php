<?php

namespace App\Console\Commands;

use App\Enums\KafkaAction;
use App\Enums\KafkaTopics;
use App\Jobs\PushDataServer;
use App\Models\Stock;
use Illuminate\Console\Command;

class SyncStockToServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:stock';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        //now finally lets handle stock pushing
        $this->info('Gathering Bulk Stock Data');
        $stocks = Stock::where(function($query){
            $query->orWhere('bulk_price','>',0)->orWhere('retail_price','>',0);
        });
        $chunk_numbers = round(($stocks->count() / 1000));
        $stocks->chunk(1000,function($stocks) use (&$chunk_numbers){
            $all_data = [];
            foreach($stocks as $stock){
                $all_data[] = $stock->getBulkPushData();
            }
            $this->info('Gathering Stock Data Complete');
            $this->info('Parsing Stock Data');
            $this->info('Parsing Stock Data Complete');
            $this->info('Posting Stock Data to '.onlineBase("stocks"));
            dispatch(new PushDataServer(['KAFKA_ACTION' => KafkaAction::CREATE_STOCK, 'KAFKA_TOPICS'=> KafkaTopics::STOCKS, 'action' => 'new',
                'table' => 'stock', 'data' => $all_data, 'endpoint' => 'stocks', 'url'=>onlineBase()."dataupdate/add_or_update_stock"]));
            $this->info('Chunk '. $chunk_numbers. ' send successfully');
            $chunk_numbers --;
        });

        $this->info('Data has been uploaded to server successfully');

        return Command::SUCCESS;
    }
}
