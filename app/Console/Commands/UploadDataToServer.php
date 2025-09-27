<?php

namespace App\Console\Commands;


use App\Enums\KafkaAction;
use App\Enums\KafkaTopics;
use App\Jobs\PushDataServer;
use App\Models\Category;
use App\Models\Classification;
use App\Models\Customer;
use App\Models\Manufacturer;
use App\Models\Stock;
use App\Models\Stockgroup;

use Illuminate\Console\Command;

class UploadDataToServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'online:upload';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upload All Required Data From Local Database to Online Database';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        set_time_limit(0);
        //start with normal data first before we move to stock to avoid relationship problem
        //not we will stock upload immediately we encounter an error, no worries any we push data to online bulk
        // the tables are truncated before insertion and also on the server we have turn off foreign key check
        //to avoid relation error

        //now we begin with manufacture

        //$this->info('Gathering Manufacturer Data');

        $manufacturers = Manufacturer::all();
        $all_data = [];
        foreach($manufacturers as $manufacturer){
            $all_data[] = $manufacturer->getBulkPushData();
        }
        $this->info('Gathering Manufacturer Data Complete');
        $this->info('Parsing Manufacturer Data');
        $this->info('Parsing Manufacturer Data Complete');
        $this->info('Posting Manufacturer Data to '.onlineBase('manufacturers'));
        dispatch(new PushDataServer(['KAFKA_ACTION'=> KafkaAction::CREATE_MANUFACTURER, 'KAFKA_TOPICS'=>KafkaTopics::GENERAL,
            'action'=>'new','table'=>'manufacturers', 'endpoint' => 'manufacturers' ,'data'=>$all_data]));



        //now we begin with category data
        $this->info('Gathering Category Data');
        $categories = Category::all();
        $all_data = [];
        foreach($categories as $category){
            $all_data[] = $category->getBulkPushData();
        }
        $this->info('Gathering Category Data Complete');
        $this->info('Parsing Category Data');
        $this->info('Parsing Category Data Complete');
        $this->info('Posting Category Data to '.onlineBase('productcategories'));
        dispatch(new PushDataServer(['KAFKA_ACTION'=> KafkaAction::CREATE_CATEGORY, 'KAFKA_TOPICS'=>KafkaTopics::GENERAL,
            'action'=>'new','table'=>'manufacturers', 'endpoint' => 'manufacturers' ,'data'=>$all_data]));


        //now we begin with classification data

        $this->info('Gathering Classification Data');
        $classifications = Classification::all();
        $all_data = [];
        foreach($classifications as $classification){
            $all_data[] = $classification->getBulkPushData();
        }
        $this->info('Gathering Classification Data Complete');
        $this->info('Parsing Classification Data');
        $this->info('Parsing Classification Data Complete');
        $this->info('Posting Classification Data to '.onlineBase("classifications"));
        dispatch(new PushDataServer(['KAFKA_ACTION'=> KafkaAction::CREATE_CLASSIFICATION, 'KAFKA_TOPICS'=>KafkaTopics::GENERAL,
            'action'=>'new','table'=>'manufacturers', 'endpoint' => 'manufacturers' ,'data'=>$all_data]));



        $this->info('Gathering Customer Data');
        $customer =   Customer::query();
        $customerCount = round(($customer->count() / 2000));
        Customer::query()->chunk(2000, function($customers) use (&$customerCount){
            $all_data = [];
            foreach($customers as $customer){
                $all_data[] = $customer->getBulkPushData();
            }
            $this->info('Gathering Customer Data Complete');
            $this->info('Parsing Customer Data');
            $this->info('Parsing Customer Data Complete');
            $this->info('Posting Customer Data to '.onlineBase('customers'));
            dispatch(new PushDataServer(['KAFKA_ACTION'=> KafkaAction::CREATE_CUSTOMER, 'KAFKA_TOPICS'=>KafkaTopics::GENERAL,
                'action'=>'new','table'=>'existing_customer', 'endpoint' => 'manufacturers' ,'data'=>$all_data]));
            $this->info('Chunk '. $customerCount. ' send successfully');
            $customerCount --;
        });




        //now we begin with group data

        $this->info('Gathering Stock Group Data');
        $stockgroups = StockGroup::all();
        $all_data = [];
        foreach($stockgroups as $stockgroup){
            $all_data[] = $stockgroup->getBulkPushData();
        }
        $this->info('Gathering Stock Group Data Complete');
        $this->info('Parsing Stock Group Data');
        $this->info('Parsing Stock Group Data Complete');
        $this->info('Posting Stock Group Data to '.onlineBase("productgroups"));
        dispatch(new PushDataServer(['KAFKA_ACTION'=> KafkaAction::CREATE_STOCK_GROUP, 'KAFKA_TOPICS'=>KafkaTopics::GENERAL,
            'action'=>'new','table'=>'manufacturers', 'endpoint' => 'manufacturers' ,'data'=>$all_data]));



        //now finally lets handle stock pushing
        $this->info('Gathering Bulk Stock Data');
        $stocks = Stock::where(function($query){
            $query->orWhere('bulk_price','>',0)->orWhere('retail_price','>',0);
        });
        $chunk_numbers = round(($stocks->count() / 2000));
        $stocks->chunk(2000,function($stocks) use (&$chunk_numbers){
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
