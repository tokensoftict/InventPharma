<?php

namespace App\Repositories;

//use App\Jobs\AddLogToCustomerLedger;
//use App\Jobs\AddLogToProductBinCard;
use App\Livewire\InvoiceAndSales\InvoiceFormComponent;
use App\Jobs\AddLogToCustomerLedger;
use App\Jobs\AddLogToProductBinCard;
use App\Jobs\PushStockUpdateToServer;
use App\Models\Creditpaymentlog;
use App\Models\Invoice;
use App\Models\Invoiceitem;
use App\Models\Invoiceitembatch;
use App\Models\Onlineordertotal;
use App\Models\Stock;
use App\Models\WaitingCustomer;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvoiceRepository
{
    public function __construct()
    {
        //
    }

    public static function invoice(Invoice $invoice, InvoiceFormComponent $component)
    {
        if(isset($invoice->id))
        {

            return [
                'invoice_number' => $invoice->invoice_number,
                'customer_id' => $invoice->customer->toArray(),
                'payment_id' => $invoice->payment_id,
                'department' => $invoice->department,
                'in_department' => $invoice->in_department,
                'discount_amount' => $invoice->discount_amount,
                'discount_type' => $invoice->discount_type,
                'discount_value' => $invoice->discount_value,
                'status_id' => $invoice->status_id,
                'sub_total' => $invoice->sub_total,
                'total_amount_paid' => $invoice->total_amount_paid,
                'total_profit' => $invoice->total_profit,
                'total_cost' => $invoice->total_cost,
                'vat' => $invoice->vat,
                'vat_amount' => $invoice->vat_amount,
                'created_by' => $invoice->created_by,
                'last_updated_by' => $invoice->last_updated_by,
                'voided_by' => $invoice->voided_by,
                'invoice_date' => mysql_str_date($invoice->invoice_date),
                'sales_time' => $invoice->sales_time,
                'void_reason' => $invoice->void_reason,
                'date_voided' =>  $invoice->date_voided,
                'void_time' =>  $invoice->void_time,
                'picked_by' =>  $invoice->picked_by,
                'packed_by' =>   $invoice->packed_by,
                'checked_by' =>  $invoice->checked_by,
                'carton_no' =>  $invoice->carton_no,
                'online_order_status' =>  $invoice->online_order_status,
                'online_order_debit' =>  $invoice->online_order_debit,
                'onliner_order_id'=>  $invoice->onliner_order_id,
                //'invoiceitems' => $invoice->invoiceitems->map->only(array_keys(self::invoiceitems(new Invoiceitem())))->toJson(),
                'invoiceitems' => $invoice->invoiceitems->map(function($item){
                    return self::invoiceitems($item);
                })->toJson()
            ];

        }else {

            return [
                'invoice_number' => $component->invoice_number,
                'customer_id' => ['firstname'=>""],
                'department' => NULL,
                'in_department' => department_by_id(auth()->user()->department_id)->quantity_column,
                'discount_amount' => 0,
                'discount_type' => 'Fixed',
                'discount_value' => 0,
                'status_id' => status('Draft'),
                'sub_total' => 0,
                'total_amount_paid' => 0,
                'total_profit' => 0,
                'total_cost' => 0,
                'vat' => 0,
                'vat_amount' => 0,
                'created_by' => auth()->id(),
                'last_updated_by' => auth()->id(),
                'invoice_date' => todaysDate(),
                'sales_time' => Carbon::now()->toDateTimeLocalString(),
                'invoiceitems' =>  collect([])->toJson()
            ];
        }

    }


    public static function invoiceitems(Invoiceitem $invoiceitem)
    {
        if(isset($invoiceitem->id)) {
            return [
                'invoice_id' => $invoiceitem->invoice_id,
                'stock_id' => $invoiceitem->stock_id,
                'quantity' => $invoiceitem->quantity,
                'customer_id' => $invoiceitem->customer_id,
                'added_by' => $invoiceitem->added_by,
                'discount_added_by' => $invoiceitem->discount_added_by,
                'cost_price' => $invoiceitem->cost_price,
                'selling_price' => $invoiceitem->selling_price,
                'profit' => $invoiceitem->profit,
                'discount_value' => $invoiceitem->discount_value,
                'discount_amount' => $invoiceitem->discount_amount,
                'discount_type' =>$invoiceitem->discount_type,
                'box' => $invoiceitem->box,
                'carton' => $invoiceitem->carton,
                'name' => $invoiceitem->name,
                'av_qty' => $invoiceitem->av_qty,
                'custom_price' => $invoiceitem->invoice->department === 'retail' ? $invoiceitem->stock->stockquantityprices : [],
                'default_selling_price' => $invoiceitem->invoice->department === 'retail' ? $invoiceitem->stock->retail_price : $invoiceitem->stock->whole_price
            ];
        }
        else {
            return [
                'invoice_id' => NULL,
                'stock_id' => NULL,
                'quantity' => NULL,
                'customer_id' => NULL,
                'added_by' => auth()->id(),
                'discount_added_by' =>NULL,
                'cost_price' => NULL,
                'selling_price' => NULL,
                'profit' => NULL,
                'discount_value' => NULL,
                'discount_amount' => NULL,
                'discount_type' => 'Fixed',
                'box' => NULL,
                'carton' =>NULL,
                'name' => NULL,
                'av_qty' =>NULL,
                'custom_price' => [],
                'default_selling_price' => NULL
            ];

        }
    }


    public function resolvePriceByQuantity(int $quantity, float $defaultSellingPrice, array $customPrices): float
    {
        foreach ($customPrices as $priceRule) {
            $min = (int) $priceRule['min_qty'];
            $max = (int) $priceRule['max_qty'];

            if ($quantity >= $min && $quantity < $max) {
                return (float) $priceRule['price'];
            }
        }

        return $defaultSellingPrice;
    }

    public function validateInvoiceItems(array $items, string $from)
    {
        $items = collect($items);

        $stocks = [];

        $errors = [];

        $items->each(function($item, $key) use(&$stocks){
            $stocks[$item['stock_id']]['item'] = $item;
        });

        $products = Stock::with(['activeBatches', 'stockquantityprices'])->whereIn('id', array_keys($stocks))->get();

        $products->each(function($product, $key) use(&$stocks, &$errors, &$from) {
            //$stocks[$product->id]['product'] = $product;
            $batch = $product->pingSaleableBatches($from, $stocks[$product->id]['item']['quantity'],  $product->activeBatches);

            $status = $product->pingIfQuantityHasNotExceededTheMinimumQuantity($from, $stocks[$product->id]['item']['quantity']);
            if($status === true and $batch !== false) {
                $errors[$product->id] = $product->name." has exceeded the minimum quantity of ".$product->minimum_quantity." set by the administrator";
            }

            $total_cost_batch = collect($batch)->sum('cost_price');

            if($batch === false) {
                $errors[$product->id] = "Not enough available quantity to process ".$product->name.", available quantity is ". $product->{$from};
            } else {
                if($from == "retail") {
                    if($product->stockquantityprices->count() > 0) {
                        $stocks[$product->id]['item']['selling_price'] = $this->resolvePriceByQuantity(
                            quantity:$stocks[$product->id]['item']['quantity'],
                            defaultSellingPrice:$product->{selling_price_column(4)},
                            customPrices: $product->stockquantityprices->toArray()
                        );
                    } else {
                        $stocks[$product->id]['item']['selling_price'] = $product->{selling_price_column(4)};
                    }
                }else{
                    $stocks[$product->id]['item']['selling_price'] = $product->{selling_price_column()};
                }
                $stocks[$product->id]['item']['cost_price'] = abs($total_cost_batch / count($batch));
                $stocks[$product->id]['batches'] = $batch;
            }
        });

        if(count($errors) > 0) return ['status'=> false , 'errors'=>$errors];

        return ['status' => true, 'results'=> $stocks];

    }


    public function calculateInvoiceTotal(array &$invoiceData, array $items)
    {
        $items = collect($items);

        $invoiceData['sub_total'] = $items->sum(function($item){
            return $item['quantity'] * $item['selling_price'];
        });
        $invoiceData['total_cost'] =  $items->sum(function($item){
            return $item['quantity'] * $item['cost_price'] ?? 0;
        });

        $invoiceData['total_profit'] =  $invoiceData['sub_total'] -  $invoiceData['total_cost'];

    }




    public function createInvoice(array $invoiceData, $validate = true) : Invoice|array
    {

        $invoice = Invoice::where('invoice_number', $invoiceData['invoice_number'])->first();

        if($invoice) {
            logActivity($invoice->id, $invoice->invoice_number, "Duplicate Invoice found, [Duplicate prevented]");
            return $invoice;
        }

        $items = json_decode($invoiceData['invoiceitems'],true);

        //set the cost price of items that does not have cost price to zero
        foreach($items as $key => $item){
            if(!isset($items[$key]['cost_price']) || $items[$key]['cost_price'] == "" || is_null($items[$key]['cost_price'])) {
                $items[$key]['cost_price'] = 0;
            }
        }

        Arr::forget($invoiceData, ['invoiceitems']);

        $results =  $this->validateInvoiceItems($items, $invoiceData['department']);

        if($results['status'] === false) return $results['errors'];

        $this->calculateInvoiceTotal($invoiceData, array_column($results['results'], 'item'));

        $invoice = Invoice::create($invoiceData);

        $invoiceItems = array_column($results['results'], 'item');

        $invoiceItems = collect($invoiceItems);
        $removeQuantity = [];

        $columns = [];

        $invoiceItems->each(function($item, $key) use(&$invoice, $results, &$removeQuantity, &$columns){
            Arr::forget($item, ['name','box','carton','av_qty','total_incentives']);
            Arr::set($item,'customer_id', $invoice->customer_id);
            Arr::set($item,'department', $invoice->department);
            $invoiceItemBatches = [];
            collect($results['results'][$item['stock_id']]['batches'])->each(function($batch, $index) use($item, &$invoiceItemBatches, &$invoice, &$removeQuantity,  &$columns) {
                $columns[] = $batch['department'];
                $invoiceItemBatches[] = new Invoiceitembatch([
                    'invoice_id' => $invoice->id,
                    'stock_id' => $item['stock_id'],
                    'stockbatch_id' => $batch['id'],
                    'cost_price' => $batch['cost_price'] ?? 0,
                    'selling_price' => $item['selling_price'],
                    'department' => $batch['department'],
                    'quantity' => $batch['qty'],
                    'batch_no' => $batch['batch_no']
                ]);
                $removeQuantity[] =  $batch;
            });

            $invoice->invoiceitems()->save(
                new Invoiceitem($item)
            )->invoiceitembatches()->saveMany($invoiceItemBatches);

        });

        Stock::removeSaleableBatches($invoice, $removeQuantity, array_unique($columns));


        logActivity($invoice->id, $invoice->invoice_number,'Invoice was generated status :'.$invoice->status->name);

        if($invoice->customer_id !== 1) { // customer ledger for walking customer

            dispatch(new AddLogToCustomerLedger([
                'payment_id' => NULL,
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'amount' => -($invoice->sub_total - $invoice->discount_amount),
                'transaction_date' => $invoice->invoice_date,
                'user_id' => auth()->id(),
            ]));
        }

        logActivity($invoice->id, $invoice->invoice_number, "Invoice was created".$invoice->status->name);

        if(config('app.sync_with_online')== 1) {
            dispatch(new PushStockUpdateToServer(array_column($invoice->invoiceitems->toArray(), 'stock_id')));
        }

        return $invoice;
    }


    public static function returnStock(Invoice $invoice)
    {
        $invoiceItemsBatches = [];
        $reverseItemsBatches = [];
        $columns = [];

        $invoice->invoiceitembatches->map->only(['stockbatch_id', 'department', 'av_qty', 'quantity'])->each(function($item, $key) use (&$invoiceItemsBatches, &$reverseItemsBatches, &$columns){
            $columns[] = $item['department'];
            $invoiceItemsBatches[] = [
                'id' => $item['stockbatch_id'],
                $item['department'] =>  $item['av_qty'] +  $item['quantity'],
                'department' =>  $item['department']
            ];

        });

        Stock::returnStocks($invoice, $invoiceItemsBatches, array_unique($columns));
    }

    public function updateInvoice(Invoice $invoice ,array $invoiceData) : Invoice|array
    {
        $items = json_decode($invoiceData['invoiceitems'],true);

        //set the cost price of items that does not have cost price to zero
        foreach($items as $key => $item){
            if(!isset($items[$key]['cost_price']) || $items[$key]['cost_price'] == "" || is_null($items[$key]['cost_price'])) {
                $items[$key]['cost_price'] = 0;
            }
        }

        Arr::forget($invoiceData, ['invoiceitems']);

        $invoiceItemsBatches = [];
        $reverseItemsBatches = [];
        $columns = [];
        //prepare to return stocks  here
        $invoice->invoiceitembatches->map->only(['stockbatch_id', 'department', 'av_qty', 'quantity'])->each(function($item, $key) use (&$invoiceItemsBatches, &$reverseItemsBatches, &$columns){
            $columns[] = $item['department'];
            $invoiceItemsBatches[] = [
                'id' => $item['stockbatch_id'],
                $item['department'] =>  $item['av_qty'] +  $item['quantity'],
                'department' => $item['department']
            ];

            /*
            $reverseItemsBatches[] = [
                'id' => $item['stockbatch_id'],
                $item['department'] =>  $item['av_qty'], //- $item['quantity']
                'department' => $item['department']
            ];
            */
        });

        Stock::returnStocks($invoice, $invoiceItemsBatches, array_unique($columns));

        $results =  $this->validateInvoiceItems($items, $invoiceData['department']);


        if($results['status'] === false) {

            $invoice->invoiceitembatches()->lockForUpdate()->get()->map->only(['stockbatch_id', 'department', 'av_qty', 'quantity'])->each(function($item, $key)use (&$invoiceItemsBatches, &$reverseItemsBatches, &$columns) {

                $reverseItemsBatches[] = [
                    'id' => $item['stockbatch_id'],
                    $item['department'] =>  $item['av_qty'] - $item['quantity'],
                    'department' => $item['department']
                ];

            });

            Stock::removeSaleableBatches($invoice, $reverseItemsBatches, array_unique($columns));

            return $results['errors'];
        }

        $this->calculateInvoiceTotal($invoiceData, array_column($results['results'], 'item'));

        Arr::forget($invoiceData, ['created_by']);

        $invoiceData['last_updated_by'] = auth()->id();

        $invoice->update($invoiceData);

        $invoiceItems = array_column($results['results'], 'item');

        $invoiceItems = collect($invoiceItems);

        $removeQuantity = [];

        $invoice->invoiceitems()->delete();

        $invoice->invoiceitembatches()->delete();

        $invoice->invoiceprinthistories()->delete();

        if($invoice->payment_id !== NULL)
        {
            Creditpaymentlog::where('payment_id',  $invoice->payment_id)->delete(); // delete credit payment logs

            $invoice->customer->updateCreditBalance();

            $invoice->payment()->delete();

            $invoice->payment_id = NULL;

            $invoice->retail_printed = "0";

            if( $invoice->date_voided === NULL) {
                $invoice->date_voided = todaysDate();
            }

            $invoice->void_reason = $invoice->void_reason === NULL ? 1 :( $invoice->void_reason +1);

            $invoice->update();

            logActivity($invoice->id, $invoice->invoice_number, 'invoice was returned RETURNED');
        }

        $columns = [];

        $invoiceItems->each(function($item, $key) use(&$invoice, $results, &$removeQuantity, &$columns){
            Arr::forget($item, ['name','box','carton','av_qty','total_incentives']);
            Arr::set($item,'customer_id', $invoice->customer_id);
            Arr::set($item,'department', $invoice->department);
            $invoiceItemBatches = [];
            collect($results['results'][$item['stock_id']]['batches'])->each(function($batch, $index) use($item, &$invoiceItemBatches, &$invoice, &$removeQuantity, &$columns) {
                $columns[] = $batch['department'];
                $invoiceItemBatches[] = new Invoiceitembatch([
                    'invoice_id' => $invoice->id,
                    'stock_id' => $item['stock_id'],
                    'stockbatch_id' => $batch['id'],
                    'cost_price' => $batch['cost_price'] ?? 0,
                    'selling_price' => $item['selling_price'],
                    'department' => $batch['department'],
                    'quantity' => $batch['qty']
                ]);

                $removeQuantity[] =  $batch;
            });

            $invoice->invoiceitems()->save(
                new Invoiceitem($item)
            )->invoiceitembatches()->saveMany($invoiceItemBatches);

        });


        Stock::removeSaleableBatches($invoice, $removeQuantity, array_unique( $columns));

        logActivity($invoice->id, $invoice->invoice_number, "Invoice was updated".$invoice->status->name);

        if($invoice->customer_id !== 1) { // customer ledger for walking customer

            dispatch(new AddLogToCustomerLedger([
                'payment_id' => NULL,
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'amount' => -($invoice->sub_total - $invoice->discount_amount),
                'transaction_date' => $invoice->invoice_date,
                'user_id' => auth()->id(),
            ]));
        }
        if(config('app.sync_with_online')== 1) {
            dispatch(new PushStockUpdateToServer(array_column($invoice->invoiceitems->toArray(), 'stock_id')));
        }

        //$this->initiateBinCard($invoice);

        return $invoice;

    }



    public function createOnlineInvoice(array $invoiceData, Collection $orderTotal) : Invoice
    {
        return DB::transaction(function() use (&$invoiceData, &$orderTotal){

            $invoiceitems = collect($invoiceData['invoiceitems']);

            Arr::forget($invoiceData, ['invoiceitems']);

            $invoice = Invoice::create($invoiceData);

            $removeQuantity = [];
            $columns = [];
            $invoiceitems->each(function ($item) use(&$invoice, &$columns,&$removeQuantity) {
                $batches = $item['batches'];

                Arr::forget($item, ['name','box','carton','av_qty','total_incentives','batches']);
                Arr::set($item,'customer_id', $invoice->customer_id);
                Arr::set($item,'department', $invoice->department);
                $invoiceItemBatches = [];

                collect($batches)->each(function($batch) use(&$invoice, &$removeQuantity, &$item, &$columns, &$invoiceItemBatches){

                    $columns[] = $batch['department'];

                    $invoiceItemBatches[] = new Invoiceitembatch([
                        'invoice_id' => $invoice->id,
                        'stock_id' => $item['stock_id'],
                        'stockbatch_id' => $batch['id'],
                        'cost_price' => $batch['cost_price'],
                        'selling_price' => $item['selling_price'],
                        'department' => $batch['department'],
                        'quantity' => $batch['qty']
                    ]);

                    $removeQuantity[] =  $batch;

                    //Arr::only(, ['id','bulksales','quantity','wholesales','retail']);

                });

                $invoice->invoiceitems()->save(
                    new Invoiceitem($item)
                )->invoiceitembatches()->saveMany($invoiceItemBatches);

            });

            Stock::removeSaleableBatches($invoice, $removeQuantity, array_unique($columns)); // remove stock quantity

            $totals = [];
            $orderTotal->each(function($total) use(&$totals) {
                $totals[] = new Onlineordertotal([
                    'name'=>$total['name'],
                    'value'=>$total['value']
                ]);
            });

            $invoice->onlineordertotals()->saveMany($totals);

            //$this->initiateBinCard($invoice);


            if($invoice->customer_id !== 1) { // customer ledger for walking customer

                dispatch(new AddLogToCustomerLedger([
                    'payment_id' => NULL,
                    'invoice_id' => $invoice->id,
                    'customer_id' => $invoice->customer_id,
                    'amount' => -($invoice->sub_total - $invoice->discount_amount),
                    'transaction_date' => $invoice->invoice_date,
                    'user_id' => auth()->id(),
                ]));
            }


            dispatch(new PushStockUpdateToServer(array_column($invoice->invoiceitems->toArray(), 'stock_id')));

            return $invoice;
        });
    }


    private function initiateBinCard(Invoice $invoice)
    {
        $returnBatches = [];

        $cards = $invoice->invoiceitembatches->map(function($item) use(&$returnBatches, &$invoice){
            return [
                'bin_card_type'=>"APP//SOLD",
                'bin_card_date'=>todaysDate(),
                'user_id'=>auth()->id(),
                'stock_id' => $item->stock_id,
                'sold_qty'=>$item->quantity,
                'stockbatch_id'=> $item->stockbatch_id,
                'to_department'=>$item->department,
                'comment'=>"Stock Sold By : by ".$invoice->create_by->name,
                'balance'=>$item->stock->totalBalance(),
                'department_balance'=>$item->stock->{$item->department}
            ];
        })->toArray();

        dispatch(new AddLogToProductBinCard($cards));
    }


    public function findByInvoiceNumber($invoice_number){

        $invoice = Invoice::where('invoice_number', $invoice_number)->first();

        if(!$invoice) {$invoice = Invoice::find($invoice_number);}

        if(!$invoice) return false;

        return $invoice;
    }


    public function checkOut(Invoice $invoice) : array
    {
        if($invoice->scan_user_id !== NULL)
        {
            logActivity($invoice->id, $invoice->invoice_number, "Trying to scan / checkout invoice has been checkout already");

            return ['status'=>false, 'message'=>'Invoice has already been checkout by '.$invoice->scan_by->name];
        }else{

            $invoice->scan_user_id = \auth()->id();
            $invoice->scan_time = Carbon::now();
            $invoice->scan_date = todaysDate();
            $invoice->update();

            if(isset($invoice->waitingCustomer->status)) {
                $invoice->waitingCustomer->status = WaitingCustomer::$waitingInvoiceStatus['complete'];
                $invoice->waitingCustomer->save();
                addCustomerWaitingListStatusHistory($invoice->waitingCustomer, 'complete');
            }

            logActivity($invoice->id, $invoice->invoice_number, "Invoice number was been Scan / Checkout");

            return ['status'=>true];
        }
    }

}
