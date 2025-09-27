<?php

namespace App\Console\Commands;

use App\Classes\Settings;
use App\Jobs\RunChunkMovingStock;
use App\Models\Movingstock;
use App\Models\Stock;
use App\Models\Stockgroup;
use Illuminate\Console\Command;

class MovingStocksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'run:movingstocks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command run product that are really moving alot';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(Settings $settings)
    {
        $this->info('Initializing moving stock command');
        $process_count =0;
        Movingstock::truncate();
        $stocks = Stock::where('status',1);
        $process_count = $stocks->count();
        $stocks->chunk(50,function($stocks) use (&$settings){
            dispatch(new RunChunkMovingStock('stock', $stocks,false, $settings));
        });

        //for grouped product
        $groups = Stockgroup::where('status',"1");
        $process_count+=$groups->count();

        $groups->chunk(300,function($group) use (&$settings){
            dispatch(new RunChunkMovingStock('group',false, $group, $settings));
        });

        /*
        $settings->put('moving_stocks_run_status',  "backgroundprocess");
        $settings->put('total_moving_to_process', $process_count);
        $settings->put('total_moving_processed', 0);
        */

        $this->store->put('moving_stocks_run_status', 'okay');
        $this->store->put('total_moving_to_process', 0);
        $this->store->put('total_moving_processed', 0);
        $this->store->put('m_run_moving_stock', 'okay');
        $this->store->put('moving_stock_last_run', Carbon::now()->toDateTimeLocalString());

        $this->info('Moving stock executed successfully');
        return Command::SUCCESS;
    }
}
