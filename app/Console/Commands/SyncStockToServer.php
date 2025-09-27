<?php

namespace App\Console\Commands;

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
        $stocks = Stock::where(function($query){
            $query->orWhere('bulk_price','>',0)->orWhere('retail_price','>',0);
        })->where('status',1)->orderBy('id', 'DESC');
        $chunk_numbers = round(($stocks->count() / 500));
        $stocks->chunk(500,function($stocks) use (&$chunk_numbers){
            $all_data = [];
            foreach($stocks as $stock){
                $all_data[] = $stock->getBulkPushData();
            }
            $this->info('Gathering Stock Data Complete');
            $this->info('Parsing Stock Data');
            $postdata = ['table'=>'stock','data'=> $all_data];
            $this->info('Parsing Stock Data Complete');
            $this->info('Posting Stock Data to '.onlineBase());
            $response = _POST('bulk_add_data',$postdata);
            $chunk_numbers = $chunk_numbers-1;
            $this->info(json_encode($response));
            $this->info('Stock data has been posted successfully for chunk '.$chunk_numbers);
            sleep(3);
        });
        return Command::SUCCESS;
    }
}
