<?php

namespace App\Services\Online;

use App\Enums\KafkaAction;
use App\Enums\KafkaEvent;
use App\Enums\KafkaTopics;
use App\Jobs\PushStockUpdateToServer;
use App\Livewire\InvoiceAndSales\InvoiceFormComponent;
use App\Models\Creditpaymentlog;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Invoice;
use App\Models\Stock;
use App\Repositories\InvoiceRepository;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;

class ProcessOrderService
{
    public static array $onlineWholeSalesDepartment = [
        "bulksales",
        "quantity",
        "wholesales",
    ];

    public static array $onlineRetailSalesDepartment = ['retail'];
    public static array $OnlineStoreIDMapper = [
        6 => 'SUPERMARKET',
        5 => 'WHOLESALES'
    ];

    public static function handle(array $order) : bool|string|array|object
    {
        if($order['action'] !== KafkaAction::PROCESS_ORDER) {
            return false;
        }

        if(!isset($order['action'])) return false;

        $order = $order['order'];

        $errors = [];
        Auth::loginUsingId(1);

        if(!isset($order['store'])) {
            $order['store'] = self::$OnlineStoreIDMapper[$order['app_id']];
        }

        $invoice = Invoice::with(['invoiceitembatches','invoiceitembatches.stock','invoiceitems', 'customer', 'payment', 'customer', 'user'])->where('invoice_number',$order['invoice_no'])->first();

        //this invoice exist we need to really delete it from the database
        if($invoice) {
            DB::transaction(function() use ($invoice) {

                if($invoice->payment_id && $invoice->payment()->exists()){

                    Creditpaymentlog::where('payment_id',  $invoice->payment_id)->delete();

                    CustomerLedger::where('payment_id', $invoice->payment_id )->delete();

                    $invoice->customer->updateCreditBalance();

                    $invoice->payment->delete();
                }

                CustomerLedger::where('invoice_id', $invoice->id)->delete();

                $returnBatches = [];
                $columns = [];

                $invoice->invoiceitembatches->each(function($item) use(&$returnBatches, &$columns) {

                    $columns[] = $item->department;

                    $returnBatches[] = array_merge([
                        'id' => $item->stockbatch_id,
                        $item->department=> $item->av_qty + $item->quantity,
                        'department' => $item->department
                    ], addOtherDepartment($item->stockbatch, $item->department ));

                });

                Stock::returnStocks($invoice, $returnBatches, array_unique($columns)); //return the stock batches

                dispatch(new PushStockUpdateToServer(array_column( $invoice->invoiceitems->toArray(), 'stock_id')));

                $invoice->delete();

            });
        }

        // if the invoice have been deleted or the invoice does not exists on the database let proceed by processing the invoice
        $invoiceRepo = new InvoiceRepository();

        //now lets try to process the order, let get all department we can pick stock from
        //before we start, the default place to pick the stock from the bulk department
        //so lets start
        $orderproducts = $order['order_products']; // lets get the order product
        //validating local database stock level to see if it can process this order
        $products = [];

        array_walk( $orderproducts, function($item) use(&$products){
            $products[$item['localid']]['item'] = $item;
        });


        $stocks = Stock::with(['activeBatches'])->whereIn('id',array_keys($products))->get();

        $stocks->each(function($stock, $key) use(&$products) {
            $products[$stock->id]['stock'] = $stock;
        });


        foreach ($products as $key=>$product)
        {
            $batches = $product['stock']->pingStockLocation($product['item']['quantity'],  $product['stock']->activeBatches, (self::$OnlineStoreIDMapper[$order['app_id']] == "WHOLESALES" ? self::$onlineWholeSalesDepartment : self::$onlineRetailSalesDepartment));
            if($batches === false) {
                $errors[$key] = "Not enough quantity to process <b>".$product['item']['name']."</b> on local database ";
            }else {
                $products[$key]['batches'] = $batches;
            }
        }

        //all have been validated and store successfully, now we can store the order products inside the array
        //lets check if there is an error
        if(count($errors) > 0){
            self::sendBackErrorMessage($order['id'], $order['local_order_id'], $errors);
            return $errors;
        }


        //after validating the order we can remove stock finalize processing

        //oya lets do normal local sales computation first

        $customer = $order['user']['cus_exist'] ?? 1;
        if($customer == "1") {
            $localCustomer = Customer::where('phone_number', $order['user']['phone'])->first();
            if($localCustomer) {
                $customer = $localCustomer->id;
            }
        }

        if($customer === "") {
            $customer = 1;
        }


        $invoiceData = InvoiceRepository::invoice(new Invoice(), new InvoiceFormComponent());

        $total_cost = 0;

        $total_sub = 0;

        $invoiceData['invoiceitems'] = collect($products)->map(function($item, $key) use($customer, &$total_cost, &$total_sub) {

            $cost =  abs((collect($item['batches'])->sum('cost_price')) / count($item['batches']));

            $total_cost += ($cost * $item['item']['quantity']);
            $total_sub  +=($cost *  $item['item']['price']);

            return [
                'stock_id' => $key,
                'quantity' => $item['item']['quantity'],
                'customer_id' => $customer,
                'added_by' => auth()->user()->id,
                'discount_added_by' => null,
                'cost_price' => abs((collect($item['batches'])->sum('cost_price')) / count($item['batches'])),
                'selling_price' => $item['item']['price'],
                'profit' => $item['item']['price'] - $item['stock']->cost_price,
                'discount_value' => 0,
                'discount_amount' => 0,
                'discount_type' => 'Fixed',
                'batches' => $item['batches']
            ] ;
        })->toArray();


        $invoiceData['customer_id'] = $customer;

        $invoiceData['invoice_number'] = $order['invoice_no'];

        $invoiceData['department'] =  $order['store'] == "WHOLESALES" ? 'bulksales' : "retail";

        $invoiceData['in_department'] =$order['store'] == "WHOLESALES" ? 'bulksales' : "retail";

        $invoiceData['online_order_status'] = 1;

        $invoiceData['onliner_order_id']= $order['id'];

        $invoiceData["sub_total"] = $order['total'];

        $invoiceData['total_cost'] = $total_cost;

        $invoiceData['total_profit'] = $total_sub - $total_cost;

        $invoiceRepo->createOnlineInvoice($invoiceData, collect($order['order_total_orders']));


        self::sendBackSuccessMessage($order['id'], $order['local_order_id']);

        return true;
    }

    /**
     * @param int $order_id
     * @param array|string $errors
     * @return void
     */
    public static function sendBackErrorMessage(int $order_id, int|null $online_local_order_id, array|string $errors) : void
    {
        if(config('app.sync_with_online') == 0)  return;

        if(config('app.KAFKA_STATUS') !== true) {
            _POST('order_validation_error/'.$online_local_order_id, ['errors'=>$errors]);
        } else {
            $message = new Message(
                headers: ['event' => KafkaEvent::LOCAL_PUSH],
                body: [['orderId' => $order_id, "error" => $errors, 'status_code' => 'Validation Error'], "action" => KafkaAction::PROCESS_ORDER_ERROR],
                key: config('app.KAFKA_HEADER_KEY')
            );

            Storage::append("validation_error.log", json_encode($message->getBody()));

            try {
                Kafka::publish()->onTopic(KafkaTopics::ORDERS)->withMessage($message)->send();
            } catch (Exception $exception) {
                report($exception);
            }

            _POST('order_validation_error/'.$online_local_order_id, ['errors'=>$errors]);
        }

    }

    /**
     * @param int $order_id
     * @return void
     */
    public static function sendBackSuccessMessage(int $order_id, int|null $online_local_order_id) : void
    {
        if(config('app.sync_with_online') == 0)  return;

        if(config('app.KAFKA_STATUS') !== true) {

            _GET('processorder/'.$online_local_order_id."/2");

        } else {

            $message = new Message(
                headers: ['event' => KafkaEvent::LOCAL_PUSH],
                body: [['orderId' => $order_id, 'status_code' => 'Packing'], "action" => KafkaAction::ORDER_PROCESSED_UPDATE],
                key: config('app.KAFKA_HEADER_KEY')
            );

            Storage::append("success_message.log",  json_encode($message->getBody()));

            try {
                Kafka::publish()->onTopic(KafkaTopics::ORDERS)->withMessage($message)->send();
            } catch (Exception $exception) {
                report($exception);
            }

            if(!is_null($online_local_order_id)) {
                _GET('processorder/'.$online_local_order_id."/2");
            }
        }
    }


    /**
     * @param int $order_id
     * @return void
     */
    public static function sendBackCancelOrderMessage(int $order_id, int|null $online_local_order_id) : void
    {
        if(config('app.sync_with_online') == 0)  return;

        if(config('app.KAFKA_STATUS') !== true) {

            _GET('processorder/'.$online_local_order_id."/5");

        } else {

            $message = new Message(
                headers: ['event' => KafkaEvent::LOCAL_PUSH],
                body: [['orderId' => $order_id, 'status_code' => 'Cancelled'], "action" => KafkaAction::ORDER_PROCESSED_UPDATE],
                key: config('app.KAFKA_HEADER_KEY')
            );

            Storage::append("cancel_message.log",  json_encode($message->getBody()));

            try {
                Kafka::publish()->onTopic(KafkaTopics::ORDERS)->withMessage($message)->send();
            } catch (Exception $exception) {
                report($exception);
            }

            _GET('processorder/'.$online_local_order_id."/5");
        }
    }

    /**
     * @param int $order_id
     * @return void
     */
    public static function sendBackWaitingForPaymentMessage(int $order_id, int|null $online_local_order_id) : void
    {
        if(config('app.sync_with_online') == 0)  return;

        if(config('app.KAFKA_STATUS') !== true) {

            _GET('processorder/'.$online_local_order_id."/6");

        } else {

            $message = new Message(
                headers: ['event' => KafkaEvent::LOCAL_PUSH],
                body: [['orderId' => $order_id, 'status_code' => 'Waiting For Payment'], "action" => KafkaAction::ORDER_PROCESSED_UPDATE],
                key: config('app.KAFKA_HEADER_KEY')
            );

            Storage::append("waiting_for_payment.log",  json_encode($message->getBody()));
            try {
                Kafka::publish()->onTopic(KafkaTopics::ORDERS)->withMessage($message)->send();
            } catch (Exception $exception) {
                report($exception);
            }

            _GET('processorder/'.$online_local_order_id."/6");
        }
    }

    /**
     * @param int $order_id
     * @return void
     */
    public static function sendBackPaymentConfirmedMessage(int $order_id, int|null $online_local_order_id) : void
    {
        if(config('app.sync_with_online') == 0)  return;

        if(config('app.KAFKA_STATUS') !== true) {

            _GET('processorder/'.$online_local_order_id."/3");

        } else {

            $message = new Message(
                headers: ['event' => KafkaEvent::LOCAL_PUSH],
                body: [['orderId' => $order_id, 'status_code' => 'Payment Confirmed'], "action" => KafkaAction::ORDER_PROCESSED_UPDATE],
                key: config('app.KAFKA_HEADER_KEY')
            );

            Storage::append("payment_confirmed.log",  json_encode($message->getBody()));

            try {
                Kafka::publish()->onTopic(KafkaTopics::ORDERS)->withMessage($message)->send();
            } catch (Exception $exception) {
                report($exception);
            }

            _GET('processorder/'.$online_local_order_id."/3");
        }
    }

    /**
     * @param int $order_id
     * @param string $carton
     * @return void
     */
    public static function sendBackOrderDispatchedMessage(int $order_id,  int|null $online_local_order_id, string $carton) : void
    {
        if(config('app.sync_with_online') == 0)  return;

        if(config('app.KAFKA_STATUS') !== true) {

            _GET('processorder/'.$online_local_order_id."/4?cartoon=".$carton);

        } else {

            $message = new Message(
                headers: ['event' => KafkaEvent::LOCAL_PUSH],
                body: [['orderId' => $order_id, 'status_code' => 'Dispatched', 'carton' => $carton], "action" => KafkaAction::ORDER_PROCESSED_UPDATE],
                key: config('app.KAFKA_HEADER_KEY')
            );

            Storage::append("dispatched_message.log",  json_encode($message->getBody()));

            try {
                Kafka::publish()->onTopic(KafkaTopics::ORDERS)->withMessage($message)->send();
            } catch (Exception $exception) {
                report($exception);
            }

            _GET('processorder/'.$online_local_order_id."/4?cartoon=".$carton);
        }
    }

}
