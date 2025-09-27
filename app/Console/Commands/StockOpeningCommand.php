<?php

namespace App\Console\Commands;

use App\Classes\Settings;
use App\Models\Stock;
use App\Models\Stockopening;
use Illuminate\Console\Command;

class StockOpeningCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'open:stock';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Stock Opening runs every day before business open';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(Settings $settings)
    {
        $system = $settings->store();

        //if($system->system_status == "backgroundprocess")  return Command::SUCCESS;

        $settings->put('system_status', 'backgroundprocess');

        $open = StockOpening::where('date_added',todaysDate())->get();

        if($open->count() === 0) {

            Stock::with(['activeBatches'])->where('status', 1)->chunk(1000, function ($stocks) {

                foreach ($stocks as $stock) {

                    $batches = $stock->activeBatches;

                    $total_qty = 0;
                    $total_retail_qty = 0;
                    $average_cost = 0;
                    $retail_average_cost = 0;
                    $total_ws = 0;
                    $total_bk = 0;
                    $total_ms = 0;

                    foreach ($batches as $batch) {
                        $total_qty += ($batch->wholesales + $batch->bulksales + $batch->quantity + round(abs(divide($batch->retail, $stock->box))) );
                        $average_cost += ($batch->wholesales + $batch->bulksales + $batch->quantity + round(abs(divide($batch->retail, $stock->box))) ) * $batch->cost_price;
                        $retail_average_cost += $batch->retail * $batch->retail_cost_price;
                        $total_retail_qty += $batch->retail;
                        $total_ws += $batch->wholesales;
                        $total_bk += $batch->bulksales;
                        $total_ms += $batch->quantity;
                    }

                    $tt_average_cost = ($average_cost == 0 ? 0 : round(divide($average_cost , $total_qty)));
                    $tt_average_cost_price = ($retail_average_cost == 0 ? 0 : round(divide($retail_average_cost , $total_retail_qty)));
                    $last_supplier = $stock->activeBatches->last();

                    $opening = [
                        'stock_id' => $stock->id,
                        'average_retail_cost_price' => $tt_average_cost_price,
                        'average_cost_price' => $tt_average_cost,
                        'wholesales' => $total_ws,
                        'bulksales' => $total_bk,
                        'retail' => $total_retail_qty,
                        'quantity' => $total_ms,
                        'total' => $total_qty,
                        'date_added' => date('Y-m-d'),
                        'supplier_id'=> !empty($last_supplier->supplier_id) ? $last_supplier->supplier_id : NULL
                    ];

                    $open = StockOpening::firstOrCreate(['stock_id' => $stock->id, 'date_added' => date('Y-m-d')],$opening);

                }

            });
        }
        $settings->put('system_status', 'okay');

        return Command::SUCCESS;
    }
}
