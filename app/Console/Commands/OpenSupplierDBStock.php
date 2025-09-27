<?php

namespace App\Console\Commands;

use App\Models\Purchase;
use App\Models\Stockopening;
use App\Models\Supplier;
use App\Models\SupplierCreditPaymentHistory;
use App\Models\SupplierStockOpening;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class OpenSupplierDBStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'open:supplierdbstock';

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

        $suppliers = Supplier::where("status", 1)->get();
        foreach ($suppliers as $supplier) {

            $check = Stockopening::query()->where("date_added", now()->format("Y-m-d"))->where("supplier_id", $supplier->id)->first();

            if(is_null($check)) {
                continue ;
            }

            $stockOpening = Stockopening::query()
                ->select(
                    DB::raw("stockopenings.supplier_id as supplier_id"),
                    DB::raw("SUM(stockopenings.average_cost_price * (stockopenings.wholesales+stockopenings.bulksales+stockopenings.quantity)) as total_opening_cost_price"),
                    DB::raw("SUM(stockopenings.wholesales+stockopenings.bulksales+stockopenings.quantity) as total_opening_quantity"),

                    DB::raw("SUM(stockopenings.average_retail_cost_price * stockopenings.retail) as total_opening_retail_cost_price"),
                    DB::raw("SUM(stockopenings.retail) as total_opening_quantity_retail"),
                )
                ->where("stockopenings.date_added", now()->format("Y-m-d"))
                ->where("stockopenings.supplier_id", $supplier->id)
                ->groupBy("stockopenings.supplier_id")->first();

            $purchase = Purchase::query()
                ->select(
                    DB::raw("MAX(purchases.date_completed) as last_supplier_date")
                )->where("supplier_id", $supplier->id)->first();


            $total_supplier_outstanding = SupplierCreditPaymentHistory::query()
                ->where("supplier_id", $supplier->id)
                ->sum("amount");

            if(is_null($purchase?->last_supplier_date)) {
                continue;
            }

            SupplierStockOpening::updateOrCreate([
                'supplier_id' => $supplier->id,
                'date_added' => now()->format("Y-m-d"),
            ], [
                'supplier_id' => $supplier->id,
                'last_supplier_date' => $purchase?->last_supplier_date,

                'total_opening_quantity_retail' => $stockOpening?->total_opening_quantity_retail ?? 0,
                'total_opening_retail_cost_price' => $stockOpening?->total_opening_retail_cost_price ?? 0,

                'total_opening_quantity' => $stockOpening->total_opening_quantity ?? 0,
                'total_opening_cost_price' => $stockOpening->total_opening_cost_price ?? 0,
                'date_added' => now()->format("Y-m-d"),

                'total_supplier_outstanding' => $total_supplier_outstanding,
            ]);
        }



        return Command::SUCCESS;
    }
}
