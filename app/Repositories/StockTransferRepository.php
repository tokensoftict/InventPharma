<?php

namespace App\Repositories;

use App\Jobs\PushStockUpdateToServer;
use App\Models\Stock;
use App\Models\Stocktransfer;
use App\Models\Stocktransferitem;

class StockTransferRepository
{
    public function __construct()
    {
        //
    }


    public static function stockTransferItems(Stocktransferitem $item)
    {
        if(isset($item->id)){
            return [
                'stock_id' => $item->stock_id,
                'name' => $item->name,
                'quantity' => $item->quantity,
                'location' => $item->location,
                'selling_price' => $item->selling_price,
                'cost_price' => $item->cost_price,
                'stockbatch_id' => $item->stockbatch_id,
                'label_qty' => ($item->stocktransfer->to === "retail" || $item->stocktransfer->to === "retail_store") ? ($item->quantity."(".$item->quantity * $item->stock->box.")") : $item->quantity,
                'user_id' => $item->user_id,
                'total' => $item->quantity * $item->selling_price
            ];
        }else{
            return [
                'stock_id' => "",
                'quantity' => 1,
                'name' => '',
                'selling_price' => "",
                'location' => "",
                'cost_price' => "",
                'stockbatch_id' => "",
                'label_qty' => "",
                'user_id' => auth()->id(),
                'total' => $item->quantity * $item->selling_price
            ];
        }

    }

    public static function stockTransfer(Stocktransfer $stocktransfer) : array
    {
        if(!isset($stocktransfer->id))
        {
            return [
                'transfer_date' => todaysDate(),
                'user_id' => auth()->id(),
                'from' => $stocktransfer->from,
                'to' => $stocktransfer->to,
                'status_id' => status("Draft"),
                'note' => "",
                'stocktransferitems' => collect([])->toJson()
            ];
        }else{
            return [
                'transfer_date' => mysql_str_date($stocktransfer->transfer_date),
                'user_id' => $stocktransfer->user_id,
                'from' => $stocktransfer->from,
                'to' => $stocktransfer->to,
                'status_id' =>$stocktransfer->status_id,
                'note' => "",
                'stocktransferitems' => json_encode(self::parseTransferItems($stocktransfer->stocktransferitems))
            ];
        }

    }

    public static function parseTransferItems($items)
    {
        $transferItems = [];
        foreach ($items as $item)
        {
            $transferItems[] = self::stockTransferItems($item);
        }

        return $transferItems;
    }


    public function saveTransfer(Stocktransfer $transfer, array $data) : Stocktransfer | array
    {
        $transfer_item = [];

        $items = $data['stocktransferitems'];

        unset($data['stocktransferitems']);

        $errors = [];

        foreach ($items as $stock)
        {
            $sk = Stock::find($stock['stock_id']);
            $batch = $sk->checkifStockcanTransfer( $stock['quantity'],$data['from'],$data['to']);

            if($batch === false)
            {
                $errors[$stock['stock_id']] = "Insufficient quantity for ".$sk->name." Available qty is ".$sk->getCurrentlevel($data['from']);
                continue ;
            }

            $price_column = department_by_quantity_column($data['from'])->price_column;

            $qty_ =0;

            foreach($batch as $batch_id=>$qty_trans) {
                $b_id = $batch_id;
                $qty_+=$qty_trans;
            }
            $item = [
                "stock_id" => $sk->id,
                "quantity" => $qty_,
                "selling_price"=>$sk->{$price_column},
                "cost_price" => $sk->{cost_price_column(department_by_quantity_column($data['from'])->id)},
                "batch_id" =>$b_id,
                "added_by"=>auth()->id()
            ];

            $transfer_item[] = new Stocktransferitem($item);
        }

        if(count($errors) > 0) return $errors;

        if(isset($transfer->id)){
            $transfer->update(
                [
                    "transfer_date" => $data['transfer_date'],
                    "user_id" => auth()->id(),
                    "status_id" => status('Draft'),
                    "from" => $data['from'],
                    "to" => $data['to'],
                    "note" => " "
                ]
            );

            $trans = $transfer;

        }else {
            $trans = StockTransfer::create(
                [
                    "transfer_date" => $data['transfer_date'],
                    "user_id" => auth()->id(),
                    "status_id" => status('Draft'),
                    "from" => $data['from'],
                    "to" => $data['to'],
                    "note" => " "
                ]
            );
        }

        if(isset($transfer->id)){
            $transfer->stocktransferitems()->delete();
        }

        $trans->stocktransferitems()->saveMany($transfer_item);

        return $trans;

    }


    public function complete(Stocktransfer $stocktransfer) : Stocktransfer|array
    {
        $to = $stocktransfer->to;

        $from = $stocktransfer->from;

        if($stocktransfer->status_id == status("Approved")){

            $from = ucwords(($from == "quantity" ? "Main Store" : $from));
            $to = ucwords($to);
            return $stocktransfer;
        }

        $wrap_it = [];

        $__stocks = $stocktransfer->stocktransferitems()->get()->toArray();

        foreach($__stocks as $stock){
            $pro =$stocktransfer->stocktransferitems()->where("stock_id",$stock['stock_id'])->get();
            $qty_ = 0;
            if($pro->count() > 1){
                foreach ($pro->toArray() as $p){
                    $qty_+=$p['quantity'];
                }
            }else{
                $qty_ = $stock['quantity'];
            }

            $wrap_it[] = [
                "selling_price"=>$stock['selling_price'],
                "quantity"=>$qty_,
                "stock_id"=>$stock['stock_id']
            ];

        }

        //after gathering the necessary data delete the old transfer items

        $transfer_item = [];

        //validate the transfer before proceeding
        $errors = [];

        foreach($wrap_it as $transfer){
            $sk =Stock::find($transfer['stock_id']);
            $batch = $sk->checkifStockcanTransfer($transfer['quantity'],$from,$to);
            if($batch === false || count($batch) == 0 ){
                $errors[$sk->id] = $sk->name." can not be transfer because the available quantity is not enough, Total available quantity is ".$sk->getCurrentlevel($from);
            }
        }


        if(count($errors) > 0) return $errors;

        foreach ($wrap_it as $transfer) {

            $sk = Stock::find($transfer['stock_id']);

            $batch = $sk->transfer_stock($transfer['quantity'], $from, $to, $stocktransfer);

            $price_column = selling_price_column(department_by_quantity_column($from)->id);

            foreach ($batch as $batch_id => $qty_trans) {
                $b_id = $batch_id;
            }

            $item = [
                "stock_id" => $transfer['stock_id'],
                "quantity" => $transfer['quantity'],
                "selling_price" => $transfer['selling_price'],
                "rem_quantity"=>$sk->getCurrentlevel($stocktransfer->from),
                "transfer_date" =>$stocktransfer->transfer_date,
                "batch_id" => $b_id,
                "added_by" => auth()->id()
            ];

            $transfer_item[] = new StockTransferItem($item);

        }

        $stocktransfer->stockTransferItems()->delete();

        $stocktransfer->stockTransferItems()->saveMany($transfer_item);

        $stocktransfer->status_id = status('Approved');

        $stocktransfer->update();

        if(config('app.sync_with_online')== 1) {
            dispatch(new PushStockUpdateToServer(array_column($stocktransfer->stockTransferItems->toArray(), 'stock_id')));
        }
        return $stocktransfer;
    }


    public function delete(Stocktransfer $stocktransfer)
    {
        return $stocktransfer->delete();
    }

}
