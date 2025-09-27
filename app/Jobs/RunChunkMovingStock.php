<?php

namespace App\Jobs;

use App\Classes\Settings;
use App\Models\Invoiceitembatch;
use App\Models\Movingstock;
use App\Models\Stockopening;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class RunChunkMovingStock //implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    var $type;
    var $stock_id;
    var $from;
    var $to;
    var $nconstant;
    var $nconstant2;
    var $ndays;
    var $group_ids;
    var $store;


    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($type,$stock_ids,$group_ids, Settings $settings)
    {
        $store =  $settings->store();
        $this->store = $settings;
        $this->ndays = $store->moving_stocks_ndays;
        $this->nconstant = $store->moving_stocks_constant;
        $this->nconstant2 = $store->moving_stocks_constant2;
        $from = date('Y-m-d', strtotime(' - '.$this->ndays.' days'));
        $to = date('Y-m-d');
        $this->type = $type;
        $this->group_ids = $group_ids;
        $this->stock_id = $stock_ids;
        $this->from = $from;
        $this->to = $to;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if($this->type == "group"){
            $this->runMovinggroup();
        }else if($this->type == "stock"){
            $this->runStockMoving();
        }
    }



    protected function runMovinggroup(){
        $to = $this->to;
        $from = $this->from;
        foreach($this->group_ids as $group) {
            $total_qty_sold = 0;
            $total_qty_retail =0;
            $_product = DB::table('invoiceitembatches	 as batch_item')
                ->select(DB::raw('SUM(batch_item.quantity) as qty'))
                ->join('stocks as st', 'st.id', '=', 'batch_item.stock_id')
                ->join('stockgroups as group', 'group.id', '=', 'st.stockgroup_id')
                ->join('invoices as invoices', 'invoices.id', '=', 'batch_item.invoice_id')
                ->where('st.stockgroup_id', '=', $group->id)
                ->where('batch_item.department', "!=", "retail")
                ->whereBetween('invoices.invoice_date', [$from, $to])
                ->groupBy('st.stockgroup_id')
                ->get();

            $last_supplier_date = $group->getLastPurchaseDate();
            if ($_product->count() > 0) {
                foreach ($_product as $_prod) {
                    $total_qty_sold += $_prod->qty;
                }
            }

            //get for retail
            $_product = DB::table('invoiceitembatches	 as batch_item')
                ->select(DB::raw('SUM(batch_item.quantity) as qty'))
                ->join('stocks as st', 'st.id', '=', 'batch_item.stock_id')
                ->join('stockgroups as group', 'group.id', '=', 'st.stockgroup_id')
                ->join('invoices as invoices', 'invoices.id', '=', 'batch_item.invoice_id')
                ->where('st.stockgroup_id', '=', $group->id)
                ->where('batch_item.department', "=", "retail")
                ->whereBetween('invoices.invoice_date', [$from, $to])
                ->groupBy('st.stockgroup_id')
                ->get();


            if($_product->count() > 0) {
                foreach ($_product as $_prod) {
                    $box = $group->totalstockbox();
                    $total_qty_sold += round(abs(divide($_prod->qty , $box)));
                    $total_qty_retail = round(abs(divide($_prod->qty , $box)));
                }
            }

            $total_opening_inventory = $group->totalStockopening([$from,$to]);

            if(($total_qty_sold > 0) && ($total_opening_inventory > 0)) {

                $daily_qty_sold = divide($total_qty_sold , $this->ndays);

                $average_inventory = divide($total_opening_inventory , $this->ndays);

                $turn_over_ratio = (divide($daily_qty_sold , $average_inventory));

                $turn_over_rate = $turn_over_ratio * $this->nconstant;

                if($last_supplier_date!=false){
                    $now = time(); // or your date as well
                    $your_date = strtotime($last_supplier_date);
                    $datediff = $now - $your_date;
                    $noofdaysoflastpur = round($datediff / (60 * 60 * 24));
                    $noofdaysoflastpur = ($noofdaysoflastpur == 0 ? 1 : $noofdaysoflastpur);
                }else{
                    $noofdaysoflastpur =1;
                }
                //calculate turn over rate too
                $turn_over_rate2 = $daily_qty_sold /($average_inventory * $noofdaysoflastpur);

                $turn_over_rate2 = $turn_over_rate2 * $this->nconstant2;


                $insert = [
                    "stockgroup_id"=>$group->id,
                    "no_qty_sold"=>($total_qty_sold - $total_qty_retail),
                    "retail_qty"=>$total_qty_retail,
                    "daily_qty_sold"=>$daily_qty_sold,
                    "average_inventory"=>$average_inventory,
                    "turn_over_rate"=>$turn_over_rate,
                    "turn_over_ratio"=> $turn_over_ratio,
                    "turn_over_rate2"=>$turn_over_rate2,
                    "lastpurchase_days"=>$noofdaysoflastpur,
                    "name"=>$group->name,
                    "box"=>$group->getLastBox(),
                    "threshold"=>$group->getThreshold(),
                    "cartoon"=>$group->getLastCarton(),
                    "supplier_name"=>($group->getLastSupplier() ? $group->getLastSupplier()->purchase->supplier->name : "N/A"),
                    "av_cost_price"=>$group->getAverageStockPriceOpening(),
                    "av_rt_cost_price"=>$group->getAverageRetailStockPriceOpening(),
                    "rt_qty"=>$group->totalStockopeningRetail()."(".$group->getLastBox()." Box)",
                    "all_qty"=>round($group->totalStockopening() - $group->totalStockopeningRetail()),
                    "tt_av_cost_price"=>(($group->totalStockopening() - $group->totalStockopeningRetail()) * $group->getAverageStockPriceOpening()),
                    "tt_av_rt_cost_price"=>($group->getAverageRetailStockPriceOpening() *  $group->totalStockopeningRetail()),
                    'last_supply_quantity' =>  ($group->getLastSupplier() ? $group->getLastSupplier()->qty : NULL),
                    'last_supply_date' => ($group->getLastSupplier() ? $group->getLastSupplier()->purchase->date_completed : NULL)
                ];

                Movingstock::create($insert);

            }else{
                //every thing will bt zero
                if($total_qty_sold > 0){
                    $daily_qty_sold = ($total_qty_sold) / $this->ndays;
                }else{
                    $daily_qty_sold = 0;
                }

                if($total_opening_inventory > 0){
                    $average_inventory = ($total_opening_inventory) / $this->ndays;
                }else{
                    $average_inventory = 0;
                }


                if($last_supplier_date!=false){
                    $now = time(); // or your date as well
                    $your_date = strtotime($last_supplier_date);
                    $datediff = $now - $your_date;
                    $noofdaysoflastpur = round($datediff / (60 * 60 * 24));
                    $noofdaysoflastpur = ($noofdaysoflastpur == 0 ? 1 : $noofdaysoflastpur);
                }else{
                    $noofdaysoflastpur =1;
                }

                $insert = [
                    "stockgroup_id"=>$group->id,
                    "no_qty_sold"=>$total_qty_sold,
                    "daily_qty_sold"=>$daily_qty_sold,
                    "average_inventory"=>$average_inventory,
                    "turn_over_rate"=>0,
                    "turn_over_ratio"=>0,
                    "retail_qty"=>0,
                    "turn_over_rate2"=>0,
                    "lastpurchase_days"=>$noofdaysoflastpur,
                    "name"=>$group->name,
                    "box"=>$group->getLastBox(),
                    "threshold"=>$group->getThreshold(),
                    "cartoon"=>$group->getLastCarton(),
                    "supplier_name"=>($group->getLastSupplier() ? $group->getLastSupplier()->purchase->supplier->name : "N/A"),
                    "av_cost_price"=>$group->getAverageStockPriceOpening(),
                    "av_rt_cost_price"=>$group->getAverageRetailStockPriceOpening(),
                    "rt_qty"=>$group->totalStockopeningRetail()."(".$group->getLastBox()." Box)",
                    "all_qty"=>round($group->totalStockopening() - $group->totalStockopeningRetail()),
                    "tt_av_cost_price"=>(($group->totalStockopening() - $group->totalStockopeningRetail()) * $group->getAverageStockPriceOpening()),
                    "tt_av_rt_cost_price"=>($group->getAverageRetailStockPriceOpening() *  $group->totalStockopeningRetail()),
                    'last_supply_quantity' =>  ($group->getLastSupplier() ? $group->getLastSupplier()->qty : NULL),
                    'last_supply_date' => ($group->getLastSupplier() ? $group->getLastSupplier()->purchase->date_completed : NULL)
                ];

                Movingstock::create($insert);
            }

        }
        $this->updateMovingJobsDone(count($this->group_ids));
    }



    protected function runStockMoving(){
        $to = $this->to;
        $from = $this->from;
        foreach($this->stock_id as $stock){
            $total_qty_sold = 0;
            $total_qty_retail =0;
            //get Total Qty sold
            //get for wholesales,bulksales and main store
            $_product =  Invoiceitembatch::select(
                'stock_id',
                DB::raw( 'SUM(quantity) as qty')
            )
                ->where('department',"!=","retail")
                ->where('stock_id',$stock->id)
                ->whereHas('invoice',function($q) use(&$from,&$to){
                    $q->whereBetween('invoice_date',[$from,$to]);
                })
                ->groupBy('stock_id')
                ->get();
            if($_product->count() > 0) {
                $p = $_product->first()->toArray();
                $total_qty_sold +=$p['qty'];
            }

            //get for retail
            $_product =   Invoiceitembatch::select(
                'stock_id',
                DB::raw( 'SUM(quantity) as qty')
            )
                ->where('department',"retail")
                ->where('stock_id',$stock->id)
                ->whereHas('stock',function($q){
                    $q->whereNull('stockgroup_id');
                })
                ->whereHas('invoice',function($q) use(&$from,$to){
                    $q->whereBetween('invoice_date',[$from,$to]);
                })
                ->groupBy('stock_id')
                ->get();
            if($_product->count() > 0) {
                $p = $_product->first()->toArray();
                $total_qty_sold +=round(abs(divide($p['qty'], $_product->first()->stock->box)));
                $total_qty_retail = round(abs(divide($p['qty'], $_product->first()->stock->box)));
            }
            //get Total Average inventory
            $total_opening_inventory = Stockopening::select('stock_id',
                DB::raw('SUM(total) as total_qty')
            )
                ->where('stock_id', $stock->id)
                ->whereBetween('date_added', [$from, $to])
                ->groupBy('stock_id')->get()->first();
            if(isset($total_opening_inventory->total_qty)){
                $total_opening_inventory = $total_opening_inventory->total_qty;
            }else{
                $total_opening_inventory = 0;
            }

            $last_sup = $stock->purchaseitems()->latest()->first();

            //calculate turn over rate
            if(($total_qty_sold > 0) && ($total_opening_inventory > 0)) {
                $last_supplier = $stock->stockbatches()->orderBy('id','DESC')->get()->first();

                $daily_qty_sold = divide($total_qty_sold , $this->ndays);

                $average_inventory = divide($total_opening_inventory , $this->ndays);

                $turn_over_ratio = (divide($daily_qty_sold , $average_inventory));

                $turn_over_rate = $turn_over_ratio * $this->nconstant;

                if(!empty($last_supplier->created_at)){
                    $now = time(); // or your date as well
                    $your_date = strtotime($last_supplier->created_at);
                    $datediff = $now - $your_date;
                    $noofdaysoflastpur = round($datediff / (60 * 60 * 24));
                    $noofdaysoflastpur = ($noofdaysoflastpur == 0 ? 1 : $noofdaysoflastpur);
                }else{
                    $noofdaysoflastpur =1;
                }
                //calculate turn over rate too
                $turn_over_rate2 = divide($daily_qty_sold ,($average_inventory * $noofdaysoflastpur)) ;

                $turn_over_rate2 = $turn_over_rate2 * $this->nconstant2;

                if(!$stock->stockOpening->first()) {
                    $rt_qty = 0;
                }else{
                    $rt_qty = round($stock->stockOpening->first()->total - (divide($stock->stockOpening->first()->retail,$stock->box)));
                }

                if(!$stock->stockOpening->first()) {
                    $all_qty =  0;
                }else{
                    $all_qty= round($stock->stockOpening->first()->total - (divide($stock->stockOpening->first()->retail,$stock->box)));
                }

                if(!$stock->stockOpening->first()){
                    $tt_av_cost_price = 0;
                }else{
                    $tt = $stock->stockOpening->first()->total - (divide($stock->stockOpening->first()->retail,$stock->box));
                    $tt_av_cost_price = ($stock->stockOpening->first()->average_cost_price * round($tt));
                }

                if(!$stock->stockOpening->first()){
                    $tt_av_rt_cost_price = 0;
                } else {
                    $tt_av_rt_cost_price = ($stock->stockOpening->first()->average_retail_cost_price * $stock->stockOpening->first()->retail);
                }



                $insert = [
                    "stock_id"=>$stock->id,
                    "no_qty_sold"=>($total_qty_sold - $total_qty_retail),
                    "retail_qty"=>$total_qty_retail,
                    "daily_qty_sold"=>$daily_qty_sold,
                    "turn_over_rate2"=>$turn_over_rate2,
                    "lastpurchase_days"=>$noofdaysoflastpur,
                    "average_inventory"=>$average_inventory,
                    "turn_over_rate"=>$turn_over_rate,
                    "turn_over_ratio"=> $turn_over_ratio,
                    "group_os_id"=>$stock->stockgroup_id,
                    'is_grouped'=>($stock->stockgroup_id ? 1 : 0),
                    "supplier_id"=>!empty($last_supplier->supplier_id) ? $last_supplier->supplier_id : NULL,
                    "name"=>$stock->name,
                    "box"=>$stock->box,
                    "threshold"=>isset($stock->nearOsOne->threshold_value) ? $stock->nearOsOne->threshold_value : 0,
                    "cartoon"=>$stock->carton,
                    "supplier_name"=>(isset($last_supplier->supplier->name) ? $last_supplier->supplier->name: "N/A"),
                    "av_cost_price"=>$stock->stockOpening->first()->average_cost_price,
                    "av_rt_cost_price"=>$stock->stockOpening->first()->average_retail_cost_price,
                    "rt_qty"=>$rt_qty,
                    "all_qty"=>$all_qty,
                    "tt_av_cost_price"=>$tt_av_cost_price,
                    "tt_av_rt_cost_price"=>$tt_av_rt_cost_price,
                    'last_supply_quantity' =>  $last_sup ?  $last_sup->qty : NULL,
                    'last_supply_date' => $last_sup ?  $last_sup->purchase->date_completed : NULL
                ];

               Movingstock::create($insert);

            }else{

                //every thing will bt zero
                $last_supplier = $stock->stockbatches()->orderBy('id','DESC')->get()->first();

                if(!empty($last_supplier->created_at)){
                    $now = time(); // or your date as well
                    $your_date = strtotime($last_supplier->created_at);
                    $datediff = $now - $your_date;
                    $noofdaysoflastpur = round($datediff / (60 * 60 * 24));
                    $noofdaysoflastpur = ($noofdaysoflastpur == 0 ? 1 : $noofdaysoflastpur);
                }else{
                    $noofdaysoflastpur =1;
                }
                if($total_qty_sold > 0){
                    $daily_qty_sold = divide(($total_qty_sold) , $this->ndays);
                }else{
                    $daily_qty_sold = 0;
                }

                if($total_opening_inventory > 0){
                    $average_inventory = divide(($total_opening_inventory) , $this->ndays);
                    $turn_over_rate2 = divide($daily_qty_sold ,($average_inventory * $noofdaysoflastpur));

                    $turn_over_rate2 = $turn_over_rate2 * $this->nconstant2;

                }else{
                    $average_inventory = 0;
                    $turn_over_rate2 =0;
                }

                if(!$stock->stockOpening->first()) {
                    $rt_qty = 0;
                }else{
                    $rt_qty = round($stock->stockOpening->first()->total - (divide($stock->stockOpening->first()->retail, $stock->box)));
                }

                if(!$stock->stockOpening->first()) {
                    $all_qty =  0;
                }else{
                    $all_qty= round($stock->stockOpening->first()->total - (divide($stock->stockOpening->first()->retail, $stock->box)));
                }

                if(!$stock->stockOpening->first()){
                    $tt_av_cost_price = 0;
                }else{
                    $tt = $stock->stockOpening->first()->total - (divide($stock->stockOpening->first()->retail, $stock->box));
                    $tt_av_cost_price = ($stock->stockOpening->first()->average_cost_price * round($tt));
                }

                if(!$stock->stockOpening->first()){
                    $tt_av_rt_cost_price = 0;
                } else {
                    $tt_av_rt_cost_price = ($stock->stockOpening->first()->average_retail_cost_price * $stock->stockOpening->first()->retail);
                }


                //calculate turn over rate too
                $insert = [
                    "stock_id"=>$stock->id,
                    "no_qty_sold"=>$total_qty_sold,
                    "daily_qty_sold"=>$daily_qty_sold,
                    "average_inventory"=>$average_inventory,
                    "turn_over_rate2"=>$turn_over_rate2,
                    "lastpurchase_days"=>$noofdaysoflastpur,
                    "turn_over_rate"=>0,
                    "turn_over_ratio"=>0,
                    "group_os_id"=>$stock->stockgroup_id,
                    'is_grouped'=>($stock->stockgroup_id ? 1 : 0),
                    "retail_qty"=>0,
                    "supplier_id"=>!empty($last_supplier->supplier_id) ? $last_supplier->supplier_id : NULL,
                    "name"=>$stock->name,
                    "box"=>$stock->box,
                    "threshold"=>isset($stock->nearOsOne->threshold_value) ? $stock->nearOsOne->threshold_value : 0,
                    "cartoon"=>$stock->cartoon,
                    "supplier_name"=>(isset($last_supplier->supplier->name) ? $last_supplier->supplier->name : "N/A"),
                    "av_cost_price"=>$stock->stockOpening->first()->average_cost_price,
                    "av_rt_cost_price"=>$stock->stockOpening->first()->average_retail_cost_price,
                    "rt_qty"=>$rt_qty,
                    "all_qty"=>$all_qty,
                    "tt_av_cost_price"=>$tt_av_cost_price,
                    "tt_av_rt_cost_price"=>$tt_av_rt_cost_price,
                    'last_supply_quantity' =>  $last_sup ?  $last_sup->qty : NULL,
                    'last_supply_date' => $last_sup ?  $last_sup->purchase->date_completed : NULL
                ];

                Movingstock::create($insert);
            }

        }
        $this->updateMovingJobsDone(count($this->stock_id));
    }




    protected function updateMovingJobsDone($count){
        $pre_count = $this->store->store()->total_moving_processed;
        $pre_count+=$count;
        $this->store->store()->total_moving_processed = $pre_count;
        if($this->store->store()->total_moving_to_process == $this->store->store()->total_moving_processed){
            $this->completeMovingJob();
        }

    }

    protected function completeMovingJob(){
        $this->store->put('moving_stocks_run_status', 'okay');
        $this->store->put('total_moving_to_process', 0);
        $this->store->put('total_moving_processed', 0);
        $this->store->put('moving_stock_last_run', Carbon::now()->toDateTimeLocalString());
    }



}
