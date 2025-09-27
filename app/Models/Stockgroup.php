<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use App\Enums\KafkaAction;
use App\Enums\KafkaTopics;
use App\Jobs\PushDataServer;
use App\Traits\ModelFilterTraits;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;


/**
 * Class Stockgroup
 *
 * @property int $id
 * @property string|null $name
 * @property bool $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @protected Nearoutofstock[] $nearoutofstock
 * @package App\Models
 */
class Stockgroup extends Model
{

    use  ModelFilterTraits;

    protected $table = 'stockgroups';

    protected $casts = [
        'status' => 'bool'
    ];

    protected $fillable = [
        'name',
        'status'
    ];



    public function getBulkPushData() : array{
        return [
            'id'=>$this->id,
            'name'=> strtoupper($this->name),
            'status'=>$this->status
        ];
    }


    public function newonlinePush()
    {
        dispatch(new PushDataServer(['KAFKA_ACTION'=> KafkaAction::CREATE_STOCK_GROUP, 'KAFKA_TOPICS'=>KafkaTopics::GENERAL, 'action'=>'new','table'=>'stock_groups', 'endpoint' => 'productgroups' ,'data'=>$this->getBulkPushData()]));
    }

    public function updateonlinePush()
    {
        dispatch(new PushDataServer(['KAFKA_ACTION'=> KafkaAction::UPDATE_STOCK_GROUP, 'KAFKA_TOPICS'=>KafkaTopics::GENERAL, 'action'=>'update','table'=>'stock_groups', 'endpoint' => 'productgroups', 'data'=>$this->getBulkPushData()]));
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function nearOs()
    {
        return $this->hasMany(Nearoutofstock::class, 'stockgroup_id');
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    /*
    public function retailNearOs()
    {
        return $this->hasMany('App\RetailNearO', 'group_id');
    }
    */

    public function getThreshold(){
        if($this->nearOs()->exists()) {
            $total = 0;
            foreach ($this->nearOs()->get() as $near) {
                $total += $near->threshold_value;
            }
            return $total;
        }
        return "N/A";
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function stocks()
    {
        return $this->hasMany(Stock::class, 'stockgroup_id');
    }

    public function oneStock()
    {
        return new HasOne($this->stocks()->latest()->getQuery(), $this,'stockgroup_id', 'id');
    }


    public function stock()
    {
        return $this->hasOne(Stock::class, 'stockgroup_id');
    }

    public function getLastPurchaseDate(){
        $stocks = $this->stocks()->get();
        if($stocks->count() == 0) return 'N/A';
        $st = [];
        foreach($stocks as $stock){
            $st[] = $stock->id;
        }

        $sup = Purchaseitem::with(['purchase'])->wherein('stock_id',$st)
            ->wherehas('purchase',function($q){
                $q->where('status_id','6');
            })->orderBy('id','DESC')->limit(1)->get()->first();
        if(isset($sup->purchase->date_completed)){
            if($sup->purchase->date_completed =='1970/01/01'){
                return false;
            }
            return $sup->purchase->date_completed;
        }
        return 'N/A';
    }


    public function lastpoItem(){
        $stocks = $this->stocks->pluck('id')->toArray();
        return Purchaseitem::with(['purchase'])->whereIn('stock_id', $stocks )
            ->wherehas('purchase',function($q){
                $q->where('status_id','6');
            })->orderBy('id', 'DESC')->limit(1)->first();
    }


    public function getlastpo_item(){
        $stocks = $this->stocks->pluck('id');
        if(count($stocks) == 0) return 'N/A';

        $sup = Purchaseitem::with(['purchase'])->whereIn('stock_id', $stocks )
            ->wherehas('purchase',function($q){
                $q->where('status_id','6');
            })->orderBy('id', 'DESC')->limit(1)->first();
        if(isset($sup->purchase->date_completed)){
            return $sup;
        }
        return 'N/A';
    }

    public function averageCostPrice(){
        $no = 0;
        $total =0;
        $stocks = $this->stocks()->where('status',"1")->get();
        foreach($stocks as $stock){
            $total = ($stock);
            $no++;
        }
    }

    public function getLastSupplier(){
        $stocks = $this->stocks->pluck('id');
        $sup = Purchaseitem::with(['purchase'])->whereIn('stock_id',$stocks)
            ->wherehas('purchase',function($q){
                $q->where('status_id','6');
            })->latest()->first();

        if($sup && $sup->purchase->supplier_id != "172" && $sup->purchase->supplier_id != "195"){
            return $sup;
        }
        return false;
    }


    public function getLastCategory(){
        $stocks = $this->stocks()->where('status',"1")->first();

        if(isset($stocks->productCategory)){
            return $stocks->productCategory->name;
        }
        return 'N/A';
    }

    public function getLastBox(){
        $po =$this->stocks()->where('status',"1")->orderBy('id')->first();
        if(isset($po->box)){
            return $po->box;
        }
        return 'N/A';
    }

    public function getLastCarton(){
        $po =$this->stocks()->where('status',"1")->orderBy('id')->first();
        if(isset($po->cartoon)){
            return $po->cartoon;
        }
        return 'N/A';
    }

    public function last_qty_purchased(){
        $stocks = $this->stocks()->get();
        if($stocks->count() == 0) return 'N/A';
        $st = [];
        foreach($stocks as $stock){
            $st[] = $stock->id;
        }

        $sup = Purchaseitem::with(['purchase'])->wherein('stock_id',$st)
            ->wherehas('purchase',function($q){
                $q->where('status_id','6');
            })->orderBy('id','DESC')->limit(1)->get()->first();

        if(isset($sup->qty)){
            return $sup->qty;
        }
        return 'N/A';
    }

    public function turn_over_rate(){
        $turn = MovingStock::where('group_os_id',$this->id)->get()->first();
        if($turn){
            return $turn->turn_over_rate;
        }
        return 'N/A';
    }

    public function turn_over_rate2(){
        $turn = MovingStock::where('group_os_id',$this->id)->get()->first();
        if($turn){
            return $turn->turn_over_rate2;
        }
        return 'N/A';
    }

    public function totalBalance(){
        if($this->stocks()->exists()) {
            $total = 0;
            foreach($this->stocks()->where('status',1)->get() as $stocks){
                $total+=$stocks->wholesales + $stocks->bulksales + $stocks->quantity + round(abs((divide($stocks->retail, $stocks->box))));
            }
            return $total;
        }
        return 0;
    }


    public function totalBalanceMax(){
        if($this->stocks()->exists()) {
            $total = 0;
            foreach($this->stocks()->where('status',1)->get() as $stocks){
                $total+=$stocks->wholesales + $stocks->bulksales + $stocks->quantity;
            }
            return $total;
        }
        return 0;
    }



    public function totalstockbox(){
        if($this->stocks()->exists()) {
            $total = 0;
            foreach($this->stocks()->get() as $stocks){
                $total+=$stocks->box;
            }
            return $total;
        }
        return 1;
    }


    public function totalStockopening($filter = false){
        if($this->stocks()->exists()) {
            $total = 0;
            foreach($this->stocks()->get() as $stocks){
                $op = $stocks->stockOpenings();
                if($filter != false){
                    $op->whereBetween('date_added',$filter);
                }else{
                    $op->where('date_added',date('Y-m-d'));
                }
                foreach($op->get() as $st) {
                    $total += $st->total;
                }
            }
            return $total;
        }
        return 0;
    }

    public function totalStockopeningRetail($filter = false){
        if($this->stocks()->exists()) {
            $total = 0;
            foreach($this->stocks()->get() as $stocks){
                $op = $stocks->stockOpenings();
                if($filter != false){
                    $op->whereBetween('date_added',$filter);
                }else{
                    $op->where('date_added',date('Y-m-d'));
                }
                foreach($op->get() as $st) {
                    $total += ($st->retail / $st->stock->box);
                }
            }
            return $total;
        }
        return 0;
    }


    public function totalStockopeningRetail2($filter = false){
        if($this->stocks()->exists()) {
            $total = 0;
            foreach($this->stocks()->get() as $stocks){
                $op = $stocks->stockOpenings();
                if($filter != false){
                    $op->whereBetween('date_added',$filter);
                }else{
                    $op->where('date_added',date('Y-m-d'));
                }
                foreach($op->get() as $st) {
                    $total += ($st->retail / $st->stock->box);
                }
            }
            return $total;
        }
        return 0;
    }


    public function totalRetailBalance(){
        if($this->stocks()->exists()) {
            $total = 0;
            foreach($this->stocks()->get() as $stocks){
                $total+=round(abs((divide($stocks->retail,$stocks->box))));
            }
            return $total;
        }
        return 0;
    }



    public function getAverageStockPriceOpening($filter = false){
        if($this->stocks()->exists()) {
            $total =0;
            $count = 0;
            foreach($this->stocks()->get() as $stocks){
                $op = $stocks->stockOpenings();
                if($filter != false){
                    $op->whereBetween('date_added',$filter);
                }else{
                    $op->where('date_added',date('Y-m-d'));
                }
                foreach($op->get() as $st) {
                    $total += $st->average_cost_price;
                    $count++;
                }
            }

            if($total == 0 || $count ==0) return 0;

            return round(($total/ $count));
        }
        return 0;
    }


    public function getAverageRetailStockPriceOpening($filter = false){
        if($this->stocks()->exists()) {
            $total =0;
            $count = 0;
            foreach($this->stocks()->get() as $stocks){
                $op = $stocks->stockOpenings();
                if($filter != false){
                    $op->whereBetween('date_added',$filter);
                }else{
                    $op->where('date_added',date('Y-m-d'));
                }
                foreach($op->get() as $st) {
                    $total += $st->average_retail_cost_price;
                    $count++;
                }
            }
            if($total == 0 || $count == 0) return 0;

            return round(($total/ $count));
        }
        return 0;
    }





    public function getAverageStockPriceOpening2($filter = false){
        if($this->stocks()->exists()) {
            $total =0;
            $count = 0;
            foreach($this->stocks()->get() as $stocks){
                $op = $stocks->stockOpenings();
                if($filter != false){
                    $op->whereBetween('date_added',$filter);
                }else{
                    $op->where('date_added',date('Y-m-d'));
                }

                foreach($op->get() as $st) {
                    $tt = $st->total - ($st->retail/$stocks->box);
                    $total+= $st->average_cost_price * round($tt);
                    $count++;
                }
            }
            return $total;
        }
        return 0;
    }


    public function getAverageRetailStockPriceOpening2($filter = false){
        if($this->stocks()->exists()) {
            $total =0;
            $count = 0;
            foreach($this->stocks()->get() as $stocks){
                $op = $stocks->stockOpenings();
                if($filter != false){
                    $op->whereBetween('date_added',$filter);
                }else{
                    $op->where('date_added',date('Y-m-d'));
                }
                foreach($op->get() as $st) {
                    $total += $st->average_retail_cost_price * $st->retail;
                    $count++;
                }
            }
            return $total;
        }
        return 0;
    }


    public function nearoutofstock()
    {
        return $this->belongsToMany(Nearoutofstock::class, 'stockgroup_id');
    }


}
