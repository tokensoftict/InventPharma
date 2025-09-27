<?php

namespace App\Repositories;

use App\Jobs\AddLogToProductBinCard;
use App\Jobs\PushStockUpdateToServer;
use App\Models\Purchase;
use App\Models\Purchaseitem;
use App\Models\Stock;
use App\Models\Stockbatch;
use App\Models\Supplier;
use App\Models\SupplierCreditPaymentHistory;
use Arr;
use DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PurchaseOrderRepository
{


    public function __construct()
    {
        //
    }


    public static function purchaseOrder(Purchase $purchase)
    {
        if(isset($purchase->id)){
            return [
                'status_id' => $purchase->status_id,
                'user_id' => $purchase->user_id,
                'completed_by' => NULL,
                'supplier_id' => $purchase->supplier_id,
                'department' => $purchase->department,
                'date_created' => mysql_str_date($purchase->date_created),
                'date_completed' => NULL,
                'batch_no' =>$purchase->batch_no,
                'purchaseitems' =>$purchase->purchaseitems->map->only(array_keys(self::purchaseitems(new Purchaseitem())))->toJson()
            ];
        }else{
            return [
                'status_id' => status("Pending"),
                'user_id' => auth()->id(),
                'completed_by' => "",
                'supplier_id' => "",
                'department' => "",
                'date_created' =>todaysDate(),
                'date_completed' => NULL,
                'batch_no' =>"",
                'purchaseitems' => collect([])->toJson()
            ];
        }
    }

    public static function purchaseitems(Purchaseitem $purchaseitem)
    {
        if(isset($purchaseitem->id)){
            return [
                'stock_id' => $purchaseitem->stock_id,
                'expiry_date' => $purchaseitem->expiry_date,
                'batch_no' => $purchaseitem->batch_no,
                'qty' => $purchaseitem->qty,
                'name' => $purchaseitem->name,
                'cost_price' => $purchaseitem->cost_price,
                'user_id' => $purchaseitem->user_id,
                'total' => ($purchaseitem->qty * $purchaseitem->cost_price)
            ];
        }
        else {
            return [
                'stock_id' => "",
                'expiry_date' => "",
                'qty' => "",
                'batch_no' => "",
                'name' => "",
                'cost_price' => "",
                'user_id' => auth()->id(),
                'total' => 0
            ];
        }
    }


    private function preparePurchaseOrder($items)
    {
        $purchaseItems = [];

        foreach ($items as $item)
        {
            if(!isset($item['cost_price']) || $item['cost_price'] == "" || is_null($item['cost_price'])){
                $item['cost_price'] = 0;
            }
            $purchaseItems[] = new Purchaseitem($item);
        }

        return $purchaseItems;
    }


    public function savePurchaseOrder(Purchase $purchase, array $data) : Purchase
    {
        $items = $data['purchaseitems'];

        unset($data['purchaseitems']);

        $data['created_by'] = \auth()->id();

        if(isset($purchase->id)){

            $purchase->update($data);
            $purchase->purchaseitems()->delete();
            $purchase->purchaseitems()->saveMany($this->preparePurchaseOrder($items));
        }
        else{

            $purchase = Purchase::create($data);
            $purchase->purchaseitems()->saveMany($this->preparePurchaseOrder($items));
        }

        return $purchase;
    }


    public function complete(Purchase $purchase)
    {
        $items = $purchase->purchaseitems->fresh();
        $batchInsert = [];
        $stockUpdate = [];


        $bincards = [];

        foreach ($items as $item)
        {
            $batchInsert[] = [
                'received_date' => todaysDate(),
                'expiry_date' => $item->expiry_date,
                $purchase->department => $item->qty,
                cost_price_column(department_by_quantity_column($purchase->department)->id)=> $item->cost_price,
                'stock_id' => $item->stock_id,
                'supplier_id' => $purchase->supplier_id,
                'batch_no' => $item->batch_no,
            ];

            $stockUpdate[] = [
                cost_price_column(department_by_quantity_column($purchase->department)->id) => $item->cost_price,
                'id' => $item->stock_id
            ];

            $bincards[] = [
                'bin_card_type'=>'APP//RECEIVED',
                'bin_card_date'=>todaysDate(),
                'user_id'=>Auth::id(),
                'in_qty'=>$item->qty,
                'stock_id'=>$item->stock->id,
                'stockbatch_id'=>NULL,
                'to_department'=>$purchase->department ,
                'supplier_id'=>$purchase->supplier_id,
                'purchase_id'=>$purchase->id,
                'comment'=>"Stock Received from Purchase Order : ".$purchase->id." by ".Auth::user()->name,
                'balance'=>$item->stock->totalBalance(),
                'department_balance'=>$item->stock->getCurrentlevel($purchase->department)
            ];

        }

        dispatch(new AddLogToProductBinCard($bincards));


        foreach ($batchInsert as $newBatch){
            Stockbatch::create($newBatch);
        }

        //DB::table('stockbatches')->insert($batchInsert);

        foreach ( $stockUpdate as $update)
        {
            Stock::find($update['id'])->updateQuantity();
        }

        /*
        Stock::upsert(
            $stockUpdate,
            ['id'],
            [ cost_price_column(department_by_quantity_column($purchase->department)->id)]
        );
        */

        Stock::whereIn('id', Arr::pluck($batchInsert, 'stock_id'))->get()->each->updateQuantity();

        $stocks = array_column($batchInsert, 'stock_id');

        dispatch(new PushStockUpdateToServer($stocks));

        $purchase->status_id = status('Complete');
        $purchase->date_completed = todaysDate();
        $purchase->completed_by = \auth()->id();
        $purchase->update();


        //add amount of purchase to credit payment history
        self::createSupplierCreditHistory($purchase);
    }


    public function totalPo(Purchase $purchase)
    {
        return $purchase->purchaseitems->sum(function($item){
            return $item->qty * $item->cost_price;
        });
    }


    public function delete(Purchase $purchase)
    {
        return $purchase->delete();
    }


    public static function createSupplierCreditHistory(Purchase $purchase)
    {
        SupplierCreditPaymentHistory::updateOrCreate([
            'user_id' => \auth()->id(),
            'supplier_id' => $purchase->supplier_id,
            'purchase_id' => $purchase->id,
            'paymentmethod_id' => NULL,
            'amount' => -($purchase->purchaseitems->sum('total')),
            'payment_date' => $purchase->date_completed,
            'payment_info' => NULL
        ],
            ['purchase_id' =>  $purchase->id]
        );
    }


    public static function createSupplierPaymentHistory(array $data)
    {
        SupplierCreditPaymentHistory::create([
            'user_id' => \auth()->id(),
            'supplier_id' => $data['supplier_id'],
            'type' => 'PAYMENT',
            'paymentmethod_id' => $data['paymentmethod_id'],
            'amount' => Str::replace(",","",$data['amount']),
            'payment_date' => $data['payment_date'],
            'payment_info' => $data['payment_info'],
            'remark' => $data['remark']
        ]);
    }


    public static function updateSupplierPaymentHistory($id ,array $data)
    {
        SupplierCreditPaymentHistory::where('id', $id)->update([
            'user_id' => \auth()->id(),
            'supplier_id' => $data['supplier_id'],
            'type' => 'PAYMENT',
            'paymentmethod_id' => $data['paymentmethod_id'],
            'amount' => Str::replace(",","",$data['amount']),
            'payment_info' => $data['payment_info'],
            'remark' => $data['remark']
        ]);
    }

}
