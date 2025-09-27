<?php

namespace App\Console\Commands;

use App\Models\Stock;
use App\Models\Stockbarcode;
use Illuminate\Console\Command;

class MigrateStockBarCodeToNewTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:barcode';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate all existing barcode to new table';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Stock::whereNotNull('barcode')->where('barcode', '!=', '')->chunk(300,function($stocks){
            foreach ($stocks as $stock)
            {
                Stockbarcode::create([
                    'stock_id' => $stock->id,
                    'barcode' => $stock->barcode,
                    'user_id' => 1
                ]);
            }
        });
        return Command::SUCCESS;
    }
}
