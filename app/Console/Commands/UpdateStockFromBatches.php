<?php

namespace App\Console\Commands;

use App\Models\Stock;
use Illuminate\Console\Command;

class UpdateStockFromBatches extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:update';

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

        $stocks = Stock::all();

        foreach ($stocks as $stock)
        {
            $stock->wholesales = $stock->stockbatches()->sum('wholesales');
            $stock->bulksales = $stock->stockbatches()->sum('bulksales');
            $stock->retail = $stock->stockbatches()->sum('retail');
            $stock->quantity = $stock->stockbatches()->sum('quantity');

            $stock->update();
        }

        $this->info('Stock Quantity has been restores back to first launch successfully');

        return Command::SUCCESS;
    }
}
