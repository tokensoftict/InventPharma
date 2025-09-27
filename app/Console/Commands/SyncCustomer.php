<?php

namespace App\Console\Commands;

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
        $customers = Customer::where('status',1);
        $chunk_numbers = round(($customers->count() / 500));
        $customers->chunk(500,function($customer) use (&$chunk_numbers){
            $all_data = [];
            foreach($customer as $cus){
                $all_data[] = $cus->getBulkPushData();
            }
            $postdata = ['table'=>'existing_customer','data'=> $all_data];
            $this->info('Parsing Customer Data Complete');
            $this->info('Posting Customer Data to '.onlineBase());
            $response = _POST2('sync_customer',$postdata);

            if($response == true){
                $chunk_numbers = $chunk_numbers-1;
                $this->info('Customer data has been posted successfully '.$chunk_numbers);
                sleep(4);
            }else{
                dd($response);
            }
        });

        return Command::SUCCESS;
    }
}
