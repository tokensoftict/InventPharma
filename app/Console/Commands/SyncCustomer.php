<?php

namespace App\Console\Commands;

use App\Enums\KafkaAction;
use App\Enums\KafkaTopics;
use App\Jobs\PushDataServer;
use App\Models\Customer;
use Illuminate\Console\Command;

class SyncCustomer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:customer';

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

        return Command::SUCCESS;
    }
}
