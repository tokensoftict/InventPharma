<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class UploadMissingOnlineOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'upload:missingorders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upload Missing order to website';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
         Invoice::where('onliner_order_id', '>',15152)->whereNotNull('onliner_order_id')->chunk(1, function(Collection $onlineOrders){
            foreach ($onlineOrders as $o){
                $order = ['payload' => json_encode([
                    'order' => $this->parseOrder($o),
                    'product' => $this->orderProduct($o),
                    'ordertotal' => $this->orderTotal($o)
                ])];

                $get = _RAWPOST('https://admin.generaldrugcentre.com/api/data/missingorders', $order);

                if($get->status() !== 200) {
                    Storage::disk('local')->append('missing-orders', $o->id . ":" . $get->status(), null);
                }

            }
        });

        return Command::SUCCESS;
    }


    private function parseOrder($order) : array
    {
        return [
            'invoice_no' => $order->invoice_number,
            'store' =>  $order->department == "bulksales" ? "WHOLESALES" : "SUPERMARKET",
            'sales_rep_id' => NULL,
            'user_type' => $order->department == "bulksales" ? "App\\User" : "App\\RetailCustomer",
            'user_id' => NULL,
            'customer_group_id' => NULL,
            'firstname' => NULL,
            'lastname' => NULL,
            'email' => NULL,
            'telephone' => $order->customer->phone_number,
            'order_date' => $order->invoice_date->format('Y-m-d'),
            'order_validation_error' => NULL,
            'payment_address_id' => NULL,
            'shipping_address_id' => NULL,
            'shipping_method_id' => NULL,
            'order_status_id' => 4,
            'prove_of_payment' => NULL,
            'ip' => NULL,
            'user_agent' => NULL,
            'payment_gateway_reponse' => NULL,
            'ordertotals' => NULL,
            'total' => $order->sub_total,
            'no_of_cartons' => $order->carton_no
        ];
    }


    private function orderProduct($order) : array
    {
        $products = [];
        foreach ($order->invoiceitems as $item)
        {
            $products[] = [
                'store' => $order->department == "bulksales" ? "WHOLESALES" : "SUPERMARKET",
                'sales_rep_id' => NULL,
                'stock_id' => NULL,
                'name' => $item->name,
                'model' => 'NiL',
                'quantity' => $item->quantity,
                'local_id' => $item->stock_id,
                'price' => $item->selling_price,
                'total' => ($item->quantity * $item->selling_price),
                'tax' => 0,
                'reward' => 5,
            ];
        }

        return $products;
    }


    public function orderTotal($order) : array
    {
        $orderTotals = [];
        foreach ($order->onlineordertotals as $total)
        {
            $orderTotals[] = [
                'order_total_id' => NULL,
                'name' => $total->name,
                'value' => $total->value
            ];
        }

        return $orderTotals;
    }
}
