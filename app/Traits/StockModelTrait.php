<?php
namespace App\Traits;

use App\Enums\KafkaAction;
use App\Enums\KafkaTopics;
use App\Jobs\AddLogToProductBinCard;
use App\Jobs\PushDataServer;
use App\Models\Invoice;
use App\Models\Stockbatch;
use App\Models\Stocktransfer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

trait StockModelTrait
{


    public function activeBatches()
    {
        $departments = departments(true)->filter(function($item){
            return $item->quantity_column !== NULL;
        });

        return $this->stockbatches()->where(function($query) use ($departments){
            foreach ($departments as $department)
            {
                if($department->quantity_column == NULL) continue;
                $query->orWhere($department->quantity_column,">", 0);
            }
        })->lockForUpdate()->orderBy("expiry_date", "ASC");

    }

    public function minimumBatches()
    {
        $departments = departments(true)->filter(function($item){
            return $item->quantity_column !== NULL;
        });

        return $this->stockbatches()->orderBy('received_date', 'DESC')
            ->where(function($query) use($departments){
                foreach ($departments as $department)
                {
                    if($department->quantity_column == NULL) continue;
                    $query->orWhere($department->quantity_column,">", 0);
                }
            })->lockForUpdate();
    }

    public static function removeSaleableBatches(Invoice $invoice ,$batches, $columns = []){

        $bincards = [];

        foreach ($batches as $batch)
        {
            $b =  Stockbatch::with(['stock'])->where('id', $batch['id'])->lockForUpdate()->first();
            $b->{$batch['department']} =  $batch[$batch['department']];
            $b->update();
            $b->refresh();
            $b->stock->updateQuantity();
        }

        //Stock::whereIn('id', Arr::pluck($invoice->invoiceitems->toArray(), 'stock_id'))->get()->each->updateQuantity();

        if($invoice->department !=="bulksales") {
            $comment = "Stock Sold from online - Invoice Number :".$invoice->invoice_number." by ". auth()->user()->name;
        }else{
            $comment =  "Stock Sold Invoice Number :".$invoice->invoice_number." by ". auth()->user()->name;
        }

        $invoice->invoiceitembatches()->lockForUpdate()->get()->each(function($stock) use(&$bincards, $invoice, &$comment){
            $bincards[] =  [
                'bin_card_type'=>"APP//SOLD",
                'bin_card_date'=>todaysDate(),
                'user_id'=>Auth::id(),
                'sold_qty'=>$stock->quantity,
                'stock_id'=>$stock->stock_id,
                'stockbatch_id'=>$stock->stockbatch_id,
                'from_department'=>$stock->department,
                'invoice_id'=>$invoice->id,
                'comment'=>$comment,
                'balance'=>$stock->stock->totalBalance(),
                'department_balance'=>$stock->stock->getCurrentlevel($stock->department)
            ];
        });

        dispatch(new AddLogToProductBinCard($bincards));

        return true;
    }


    public static function returnStocks(Invoice $invoice, $batches, $columns = [])
    {
        //Stockbatch::upsert($batches, ['id'], $columns);

        foreach ($batches as $batch)
        {
            $b =  Stockbatch::with(['stock'])->where('id', $batch['id'])->lockForUpdate()->first();
            $b->{$batch['department']} =  $batch[$batch['department']];
            $b->update();
            $b->refresh();
            $b->stock->updateQuantity();
        }


        //Stock::whereIn('id', Arr::pluck($invoice->invoiceitems->toArray(), 'stock_id'))->get()->each->updateQuantity();

        $cards = [];
        if($invoice->department !=="bulksales") {
            $comment =  "Stock Returned Invoice : by " .auth()->user()->name;
        }else{
            $comment =  "Stock Returned from Online Invoice Deleted Because of repacking or re-process : by ".auth()->user()->name;
        }
        $invoice->invoiceitembatches()->lockForUpdate()->get()->each(function ($item) use(&$invoice, &$cards, &$comment) {
            $cards[] = [
                'bin_card_type' => "APP//RETURN",
                'bin_card_date' => todaysDate(),
                'user_id' => auth()->id(),
                'stock_id' => $item->stock_id,
                'return_qty' => $item->quantity,
                'stockbatch_id' => $item->stockbatch_id,
                'to_department' => $item->department,
                'comment' => $comment,
                'balance' => $item->stock->totalBalance(),
                'department_balance' => $item->stock->getCurrentlevel($item->department)
            ];
        });

        dispatch(new AddLogToProductBinCard($cards));


    }


    /**
     * @param $online_quantity
     * @param $activeBatches
     * @param $departments
     * @return array|bool
     */
    public function pingStockLocation($online_quantity, $activeBatches = false, $departments = []) : array|bool
    {
        if($activeBatches === false){

            $activeBatches = $this->activeBatches;
        }

        $allbatches = [];

        foreach ($departments as $department){

            if($department === 'retail') {
                $cost_price_column = cost_price_column(4);
            }else{
                $cost_price_column = cost_price_column();
            }

            foreach ($activeBatches as $batch)
            {
                if($batch->{$department} === 0) continue;
                if($batch->{$department} - $online_quantity < 0){
                    $online_quantity = $online_quantity - $batch->{$department};
                    $allbatches[] = array_merge([
                        'id' => $batch->id,
                        $department => 0,
                        'qty' => $batch->{$department},
                        'cost_price' => $batch->{$cost_price_column},
                        'department' => $department
                    ], addOtherDepartment($batch, $department));
                }else{
                    $newqty = $batch->{$department} - $online_quantity;

                    $allbatches[] = array_merge([
                        'id' => $batch->id,
                        $department => $newqty,
                        'cost_price' => $batch->{$cost_price_column},
                        'department' => $department,
                        'qty' => $online_quantity
                    ],addOtherDepartment($batch, $department));
                    $online_quantity = 0;
                }

                if($online_quantity === 0) return $allbatches;
            }

            if($online_quantity === 0) break;
        }

        if(count($allbatches) === 0) {
            return false;
        }

        if($online_quantity > 0)  {
            return false;
        }

        return $allbatches;
    }

    public function pingSaleableBatches($from, $qty, $activeBatches = false){

        if($activeBatches === false){

            $activeBatches = $this->activeBatches;
        }

        if($this->{$from} < $qty) return false;

        $neededBatches = [];
        if($from == 'retail') {
            $cost_price_column = cost_price_column(4);
        }else{
            $cost_price_column = cost_price_column();
        }

        foreach ($activeBatches as $batch)
        {
            if($batch->{$from} === 0) continue;
            if($batch->{$from} - $qty < 0){
                $qty = $qty - $batch->{$from};
                $neededBatches[] = [
                    'id' => $batch->id,
                    $from => 0,
                    'qty' => $batch->{$from},
                    'cost_price' => $batch->{$cost_price_column},
                    'department' => $from,
                    'batch_no' => $batch->batch_no
                ];
            }else{
                $newqty = $batch->{$from} - $qty;

                $neededBatches[] = [
                    'id' => $batch->id,
                    $from => $newqty,
                    'cost_price' => $batch->{$cost_price_column},
                    'department' => $from,
                    'qty' => $qty,
                    'batch_no' => $batch->batch_no
                ];
                $qty = 0;
            }

            if($qty === 0) return $neededBatches;
        }

        if(count($neededBatches) > 0 && $qty === 0) return  $neededBatches;

        return false;
    }


    public function pingTransferStock($from, $to, $qty)
    {
        if($this->{$from} < $qty) return false;

        $cost_price = $to === 'retail' ? 'retail_cost_price' : 'cost_price';

        $neededBatches = [];

        foreach ($this->activeBatches as $batch)
        {
            if($batch->{$from} - $qty < 0){
                $qty = $qty - $batch->{$from};
                $neededBatches[] = [
                    'id' => $batch->id,
                    $from => 0,
                    'qty' => $batch->{$from},
                    $to =>  ($to !=='retail' ?  $batch->{$from} :  $batch->{$from} * $this->box),
                    $cost_price => ($to !== 'retail') ? $batch->{$cost_price} : round(abs($batch->cost_price / $this->box))
                ];
            }else{
                $newqty = $batch->{$from} - $qty;
                $neededBatches[] = [
                    'id' => $batch->id,
                    'qty' => $qty,
                    $from => $newqty ,
                    $to =>  ($to !=='retail' ? ($batch->{$to} + $qty) :  $batch->{$to} + ($qty * $this->box)),
                    $cost_price => ($to !== 'retail') ? $batch->{$cost_price} : round(abs($batch->cost_price / $this->box))
                ];
                $qty = 0;
            }

            if($qty === 0) return $neededBatches;
        }

        if(count($neededBatches) > 0 && $qty === 0) return  $neededBatches;

        return false;
    }


    public function updateQuantity()
    {
        $depts = departments(true)->filter(function($item){
            return $item->quantity_column !== NULL;
        });

        foreach ($depts as $dept) {
            $this->{$dept->quantity_column} =  $this->stockbatches()->sum($dept->quantity_column);
            $this->update();
            $this->refresh();
        }
    }


    public static function completeTransfer(array $batches, Stocktransfer $stocktransfer)
    {
        $cost_price =  $stocktransfer->to === 'retail' ? 'retail_cost_price' : 'cost_price';

        $bincards = [];

        foreach ($batches as $batch)
        {
            $b =  Stockbatch::with(['stock'])->where('id', $batch['id'])->lockForUpdate()->first();
            $b->{$stocktransfer->from} = $batch[$stocktransfer->from];
            $b->{$cost_price} = $batch[$cost_price];
            $b->update();
            $b->refresh();
            $b->stock->updateQuantity();

            $stocktransfer->stocktransferitems()->where('stock_id', $b->stock->id)->update(['stockbatch_id' => $batch['id']]);

            $bincards[] = [
                'bin_card_type'=>'APP//TRANSFER',
                'bin_card_date'=>todaysDate(),
                'user_id'=>Auth::id(),
                'out_qty'=>$batch['qty'],
                'in_qty' => 0,
                'stock_id'=>$b->stock->id,
                'stockbatch_id'=>$batch['id'],
                'from_department'=>$stocktransfer->from,
                'to_department'=>$stocktransfer->to,
                'stocktransfer_id'=>$stocktransfer->id,
                'comment'=>"Stock Transfer Transfer ID : ".$stocktransfer->id." by ".Auth::user()->name,
                'balance'=>$b->stock->totalBalance(),
                'department_balance'=>$b->stock->getCurrentlevel($stocktransfer->from)
            ];

            $b = Stockbatch::with(['stock'])->find($batch['id']);
            $b->{$stocktransfer->to} = $batch[$stocktransfer->to];
            $b->update();
            $b->refresh();
            $b->stock->updateQuantity();


            $bincards[] = [
                'bin_card_type'=>'APP//RECEIVED',
                'bin_card_date'=>todaysDate(),
                'user_id'=>Auth::id(),
                'in_qty'=>$batch['qty'],
                'out_qty' => 0,
                'stock_id'=> $b->stock->id,
                'stockbatch_id'=>$batch['id'],
                'from_department'=>$stocktransfer->from,
                'to_department'=>$stocktransfer->to,
                'stocktransfer_id'=>$stocktransfer->id,
                'comment'=>"Stock Received Transfer ID : ".$stocktransfer->id." by ".Auth::user()->name,
                'balance'=>$b->stock->totalBalance(),
                'department_balance'=>$b->stock->getCurrentlevel($stocktransfer->to)
            ];

        }




        dispatch(new AddLogToProductBinCard($bincards));

        return true;
    }



    public function getBulkPushData() : array{
        $data =  [
            'local_stock_id'=>$this->id,
            'description'=>$this->description,
            'name'=>$this->name,
            'classification_id'=>$this->classification_id,
            'productcategory_id'=>$this->category_id,
            'manufacturer_id'=>$this->manufacturer_id,
            'productgroup_id'=>$this->stockgroup_id,
            'box'=>$this->box,
            'is_wholesales'=>($this->bulk_price > 0 ? 1 : 0 ),
            'max'=>"0",
            'carton'=>$this->carton,
            'sachet'=>1,
        ];

        $stockPrices = [];
        $expiry_date = NULL;
        $re_expiry_date = NULL;

        $ex = $this->getOnlineExpiryDate();
        if( $ex ) {
            $expiry_date =  $ex->format('Y-m-d');
        }

        $re = $this->getOnlineRetailExpiryDate();
        if( $re ) {
            $re_expiry_date =  $re->format('Y-m-d');
        }
        if($this->bulk_price > 0) {
            $stockPrices['wholesales'] = [
                "app_id" => 5,
                "price" => $this->bulk_price,
                "quantity" => $this->getOnlineQuantity(),
                "status" => $this->status,
                "expiry_date" => $expiry_date,
                "custom_price" => []
            ];
        }

        // for OnlineSuperMarket Push
        if($this->retail_price > 0){
            $stockPrices['supermarket'] = [
                "app_id" => 6,
                "price" => $this->retail_price,
                "quantity" => $this->getRetailQuantity(),
                "status" => $this->status,
                "expiry_date" => $re_expiry_date,
                "custom_price" => $this->stockquantityprices->map->only(['price', 'min_qty', 'max_qty'])->toArray(),
            ];
        }

        $data['stock_prices'] = $stockPrices;

        return $data;
    }


    public function getBulkOldPushData() : array{
        $data =  [
            'local_stock_id'=>$this->id,
            'description'=>$this->description,
            'name'=>$this->name,
            'classification_id'=>$this->classification_id,
            'category_id'=>$this->category_id,
            'brand_id'=>$this->brand_id,
            'manufacturer_id'=>$this->manufacturer_id,
            'group_id'=>$this->stockgroup_id,
            'price'=>$this->bulk_price,
            'quantity'=>$this->getOnlineQuantity(),
            'retail_quantity' => $this->getRetailQuantity(),
            'box'=>$this->box,
            'is_wholesales'=>($this->bulk_price > 0 ? 1 : 0 ),
            'max'=>"0",
            'cartoon'=>$this->carton,
            'sachet'=>1,
            //'status'=>$this->status,
            'retail_status'=>$this->status,
        ];

        $ex = $this->getOnlineExpiryDate();
        if( $ex ) {
            $data['expiry_date'] =  $ex->format('Y-m-d');
        }

        // for OnlineSuperMarket Push
        if($this->retail_price > 0){

            $data['retail_price'] = $this->retail_price;

            $data['retail_quantity'] = $this->retail;
        }

        return $data;
    }

    public function getOnlineExpiryDate()
    {
        $batch = $this->activeBatches->filter(function($item, $index){
            return ($item->wholesales > 0 || $item->bulksales > 0 || $item->quantity > 0);
        })->first();

        if($batch) return (new Carbon($batch->expiry_date));

        return false;
    }

    public function getOnlineRetailExpiryDate()
    {
        $batch = $this->activeBatches->filter(function($item, $index){
            return ($item->retail > 0);
        })->first();

        if($batch) return $batch->expiry_date;

        return false;
    }

    public function getOnlineQuantity()
    {
        return $this->stockbatches()->where('bulksales', '>',0)->sum('bulksales') +
            $this->stockbatches()->where('quantity', '>',0)->sum('quantity') +
            $this->stockbatches()->where('wholesales', '>',0)->sum('wholesales') ;
    }

    public function getCurrentlevel($department)
    {
        return $this->stockbatches()->sum($department);
    }

    public function getRetailQuantity()
    {
        return round(divide($this->stockbatches()->sum('retail'),$this->box), 0);
    }

    public function cacheTotalBalance(){
        return $this->quantity + $this->bulksales + $this->wholesales + (divide($this->retail , $this->box));
    }
    public function totalBalance()
    {
        return $this->stockbatches()->sum(DB::raw('bulksales + quantity + wholesales'))+
           divide($this->stockbatches()->sum('retail') , $this->box);
    }

    public function newonlinePush()
    {
        if($this->bulk_price > 0 || $this->retail_price > 0) {
            dispatch(new PushDataServer(['KAFKA_ACTION' => KafkaAction::CREATE_STOCK, 'KAFKA_TOPICS'=> KafkaTopics::STOCKS, 'action' => 'new', 'table' => 'stock', 'data' => $this->getBulkPushData(), 'endpoint' => 'stocks', 'url'=>onlineBase()."dataupdate/add_or_update_stock"]));
        }

    }

    public function updateonlinePush()
    {
        if(($this->bulk_price > 0 || $this->retail_price > 0)  && !$this->isDirty('batched')) {
            dispatch(new PushDataServer(['KAFKA_ACTION' => KafkaAction::UPDATE_STOCK, 'KAFKA_TOPICS'=> KafkaTopics::STOCKS, 'action' => 'update', 'table' => 'stock', 'data' => $this->getBulkPushData(), 'endpoint' => 'stocks', 'url'=>onlineBase()."dataupdate/add_or_update_stock"]));
        }
    }


    public function checkifStockcanTransfer($qty,$from,$to){
        $batch_ids = [];
        foreach($this->stockbatches()->lockForUpdate()->where($from, ">","0")->orderBy("expiry_date","ASC")->get() as $batch){
            if($batch->$from - $qty < 0){
                $qty = $qty - $batch->$from;
                $batch->$to =  $batch->$from;
                $batch_ids[$batch->id] =$batch->$from;
                $batch->$from = 0;
            }else{
                $batch->$from = $batch->$from - $qty;
                $batch->$to = $qty;
                $batch_ids[$batch->id] = $qty;
                $qty = 0;
            }
            if($qty === 0){
                return $batch_ids;
            }
        }
        return false;
    }

    public function transfer_stock($qty,$from,$to,$transfer){
        $batch_ids = [];
        $bincards = [];
        foreach($this->stockbatches()->lockForUpdate()->where($from, ">","0")->orderBy("expiry_date","ASC")->get() as $batch){
            if($batch->$from - $qty < 0){
                $ini = $batch->$from;
                $qty = $qty - $batch->$from;
                $batch_ids[$batch->id] =$batch->$from;

                if($to == "retail"){
                    $col = selling_price_column(department_by_quantity_column($from)->id);
                    $batch->retail_cost_price = round(abs(divide($batch->stock->$col , $batch->stock->box)));
                    $batch->$to =  $batch->$to + ($this->box * $batch->$from);
                    $batch->$from = 0;
                }else{
                    $batch->$to =   ($batch->$to+$batch->$from);
                    $batch->$from = 0;
                }
                $batch->update();


                $bincards[] = [
                    'bin_card_type'=>'APP//TRANSFER',
                    'bin_card_date'=>todaysDate(),
                    'user_id'=>\auth()->id(),
                    'out_qty'=>$ini,
                    'stock_id'=>$this->id,
                    'stockbatch_id'=>$batch->id,
                    'to_department'=>$to,
                    'from_department'=>$from,
                    'stocktransfer_id'=>$transfer->id,
                    'comment'=>"Stock Transfer Transfer ID : ".$transfer->id." by ".Auth::user()->name,
                    'balance'=>$this->totalBalance(),
                    'department_balance'=>$this->getCurrentlevel($from)
                ];

                $bincards[] = [
                    'bin_card_type'=>'APP//RECEIVED',
                    'bin_card_date'=>date('Y-m-d'),
                    'user_id'=>\auth()->id(),
                    'in_qty'=>$ini,
                    'stock_id'=>$this->id,
                    'stockbatch_id'=>$batch->id,
                    'to_department'=>$to,
                    'from_department'=>$from,
                    'stocktransfer_id'=>$transfer->id,
                    'comment'=>"Stock Received Transfer ID : ".$transfer->id." by ".Auth::user()->name,
                    'balance'=>$this->totalBalance(),
                    'department_balance'=>$this->getCurrentlevel($to)
                ];
            }else{
                $batch_ids[$batch->id] = $qty;
                if($to == "retail"){
                    $batch->$from = $batch->$from - $qty;
                    $col = selling_price_column(department_by_quantity_column($from)->id);
                    $batch->retail_cost_price = round(abs(divide($batch->stock->$col , $batch->stock->box)));
                    $batch->$to =  $batch->$to + ($batch->stock->box * $qty);
                }else{
                    $batch->$from = $batch->$from - $qty;
                    $batch->$to = $batch->$to+ $qty;
                }
                $batch->update();

                $bincards[] = [
                    'bin_card_type'=>'APP//TRANSFER',
                    'bin_card_date'=>date('Y-m-d'),
                    'user_id'=>Auth::id(),
                    'out_qty'=>$qty,
                    'stockbatch_id'=>$batch->id,
                    'stock_id'=>$this->id,
                    'to_department'=>$to,
                    'from_department'=>$from,
                    'stocktransfer_id'=>$transfer->id,
                    'comment'=>"Stock Transfer Transfer ID : ".$transfer->id." by ".Auth::user()->name,
                    'balance'=>$this->totalBalance(),
                    'department_balance'=>$this->getCurrentlevel($from)
                ];

                $bincards[] = [
                    'bin_card_type'=>'APP//RECEIVED',
                    'bin_card_date'=>date('Y-m-d'),
                    'user_id'=>Auth::id(),
                    'in_qty'=>$qty,
                    'stock_id'=>$this->id,
                    'stockbatch_id'=>$batch->id,
                    'to_department'=>$to,
                    'from_department'=>$from,
                    'stocktransfer_id'=>$transfer->id,
                    'comment'=>"Stock Received Transfer ID : ".$transfer->id." by ".Auth::user()->name,
                    'balance'=>$this->totalBalance(),
                    'department_balance'=>$this->getCurrentlevel($to)
                ];
                $qty = 0;
            }


            $batch->stock->updateQuantity();

            if($qty === 0){
                dispatch(new AddLogToProductBinCard($bincards));
                return $batch_ids;
            }
        }
    }



    public function getWholePriceAttribute()
    {
        if(!isset($this->promotion_item->status_id)) return $this->attributes['whole_price'];

        $promo = $this->promotion_items->filter(function ($item)  {
            return $item->whole_price > 0 && $item->status_id === status('Approved');
        })->first();

        if(!$promo) return $this->attributes['whole_price'];

        return (isset($promo->whole_price) && $promo->whole_price > 0) ? $promo->whole_price : $this->attributes['whole_price'];
    }

    public function getBulkPriceAttribute()
    {
        if(!isset($this->promotion_item->status_id)) return $this->attributes['bulk_price'];

        $promo = $this->promotion_items->filter(function ($item)  {
            return $item->bulk_price > 0 && $item->status_id === status('Approved');
        })->first();

        if(!$promo) return $this->attributes['bulk_price'];

        return (isset($promo->bulk_price) && $promo->bulk_price > 0) ? $promo->bulk_price : $this->attributes['bulk_price'];
    }
    public function getRetailPriceAttribute()
    {
        if(!isset($this->promotion_item->status_id)) return $this->attributes['retail_price'];

        $promo = $this->promotion_items->filter(function ($item)  {
            return $item->retail_price > 0 && $item->status_id === status('Approved');
        })->first();

        if(!$promo) return $this->attributes['retail_price'];

        return (isset($promo->retail_price) && $promo->retail_price > 0 ) ? $promo->retail_price : $this->attributes['retail_price'];
    }


    public function getHasPromoAttribute()
    {
        return isset($this->promotion_item->status_id);
    }


    public function getUneditedValues() : array
    {
        return $this->attributes;
    }


    /**
     * @param string $department
     * @param int $quantity
     * @return bool
     */
    public final function pingIfQuantityHasNotExceededTheMinimumQuantity(string $department, int $quantity) : bool
    {
        if (!is_null($this->minimum_quantity) and $this->minimum_quantity > 0) {
            $minimumQuantity = $this->minimum_quantity;
            $totalQtyAfterRemovingQuantity = $this->getCurrentlevel($department) - $quantity;
            if($totalQtyAfterRemovingQuantity < $minimumQuantity) {
                return true;
            }
        }

        return false;
    }
}
