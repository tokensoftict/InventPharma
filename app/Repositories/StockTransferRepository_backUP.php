<?php
/*
namespace App\Repositories;

use App\Jobs\PushStockUpdateToServerFromTransfer;
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
                'user_id' => $item->user_id,
                'total' => $item->quantity * $item->quantity
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
                'user_id' => auth()->id(),
                'total' => $item->quantity * $item->quantity
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
                'stocktransferitems' => $stocktransfer->stocktransferitems->map->only(array_keys(self::stockTransferItems(new Stocktransferitem())))->toJson()
            ];
        }

    }

    private function prepareStockTransfer($items)
    {
        $stockItems = [];

        foreach ($items as $item)
        {
            \Arr::forget($item, ['name','location','total']);

            $item['selling_price'] =  $item['cost_price'];

            $item['stockbatch_id'] = NULL;

            $stockItems[] = new Stocktransferitem($item);
        }

        return $stockItems;
    }

    public function saveTransfer(Stocktransfer $stocktransfer,$data) : Stocktransfer
    {
        $items = $data['stocktransferitems'];

        unset($data['stocktransferitems']);

        if(isset($stocktransfer->id)){

            $stocktransfer->update($data);

            $stocktransfer->stocktransferitems()->delete();

            $stocktransfer->stocktransferitems()->saveMany($this->prepareStockTransfer($items));

        }else {

            $stocktransfer = Stocktransfer::create($data);

            $stocktransfer->stocktransferitems()->saveMany($this->prepareStockTransfer($items));
        }

        return $stocktransfer;
    }


    public function complete(Stocktransfer $stocktransfer) : Stocktransfer|array
    {
        $errors = [];

        $items = $stocktransfer->stocktransferitems;

        $batches = [];

        foreach ($items as $item)
        {
            $neededBatches = $item->stock->pingTransferStock($stocktransfer->from, $stocktransfer->to, $item->quantity);
            if ($neededBatches === false)
            {
                $errors[$item->stock_id] = "Insufficient quantity for ".$item->stock->name." Available qty is ".$item->stock->{$stocktransfer->from};

            }else {
                foreach ($neededBatches as $batch) {
                    $batches[] = $batch;
                }
            }
        }

        if(count($errors) > 0) return $errors;

        Stock::completeTransfer($batches, $stocktransfer);

        dispatch(new PushStockUpdateToServerFromTransfer(array_column($items->toArray(), 'stock_id')));

        $stocktransfer->status_id = status('Approved');

        $stocktransfer->update();

        return $stocktransfer;
    }

    public function delete(Stocktransfer $stocktransfer)
    {
        return $stocktransfer->delete();
    }

}
*/
