<?php

namespace App\Console\Commands;

use App\Classes\Settings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Schema;

class ImportExistingData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:project';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Old Database from project database';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(Settings $settings)
    {
        ini_set('memory_limit', '-1');

        Schema::disableForeignKeyConstraints();
/*

        $stores = DB::connection('mysql2')->table('store')->get()->first();

        $stores = (array)$stores;

        \Arr::forget($stores, ['id','created_at','updated_at']);

        $settings->put($stores);




        $classifications = DB::connection('mysql2')->table('classifications')->get();

        DB::transaction(function() use($classifications) {

            foreach ($classifications->chunk(500) as $chunk) {

                DB::table('classifications')->insert(json_decode($chunk->toJson(), true));
            }

        });


        $manufacturers = DB::connection('mysql2')->table('manufacturers')->get();

        DB::transaction(function()  use($manufacturers) {
            foreach ($manufacturers->chunk(500) as $chunk) {
                DB::table('manufacturers')->insert(json_decode($chunk->toJson(), true));
            }
        });

        $product_category =  DB::connection('mysql2')->table('product_category')->get();

        DB::transaction(function() use($product_category) {
            foreach ($product_category->chunk(500) as $chunk) {
                DB::table('categories')->insert(json_decode($chunk->toJson(), true));
            }
        });

        $stock_groups =  DB::connection('mysql2')->table('stock_groups')->get();

        DB::transaction(function() use($stock_groups) {
            foreach ($stock_groups->chunk(500) as $chunk) {
                DB::table('stockgroups')->insert(json_decode($chunk->toJson(), true));
            }
        });


        $cities =  DB::connection('mysql2')->table('cities')->get();

        DB::transaction(function() use ($cities) {
            foreach ($cities->chunk(500) as $chunk) {
                DB::table('cities')->insert(json_decode($chunk->toJson(), true));
            }
        });


        $customers = DB::connection('mysql2')->table('customers')->get();

        DB::transaction(function() use($customers) {
            foreach ($customers->chunk(500) as $chunk) {
                DB::table('customers')->insert(json_decode($chunk->toJson(), true));
            }
        });


        $supplier = DB::connection('mysql2')->table('supplier')->get();

        DB::transaction(function() use($supplier) {
            foreach ($supplier->chunk(500) as $chunk) {
                DB::table('suppliers')->insert(json_decode($chunk->toJson(), true));
            }

        });



        $groups = DB::connection('mysql2')->table('groups')->select('name','status','created_at', 'updated_at')->get();

        DB::transaction(function() use($groups) {
            foreach ($groups->chunk(20) as $chunk) {
                DB::table('usergroups')->insert(json_decode($chunk->toJson(), true));
            }

        });


        $users = DB::connection('mysql2')->table('users')->select(
            'name',
            'email',
            'username',
            'password',
            'group_id as usergroup_id',
            'status',
            DB::raw( '(CASE
                WHEN department = "administrator" THEN 5
                WHEN department = "wholesales" THEN 2
                WHEN department = "retail" THEN 4
                WHEN department = "quantity" THEN 1
                WHEN department = "bulksales" THEN 3
             END) AS department_id'),
            'remember_token',
            'created_at',
            'updated_at'
        )->get();

        DB::transaction(function() use($users) {
            foreach ($users->chunk(200) as $chunk) {
                DB::table('users')->insert(json_decode($chunk->toJson(), true));
            }

        });

*/
        // make sure you drop added_by column
        $stocks = DB::connection('mysql2')->table('stocks')->select(
            'id',
            'name',
            'description',
            'code',
            'category_id',
            'manufacturer_id',
            'classification_id',
            'brand_id',
            'whole_price',
            'bulk_price',
            'retail_price',
            'barcode',
            'location',
            'batched',
            'expiry',
            'piece',
            'box',
            'sachet',
            'status',
            'reorder',
            'group_id as stockgroup_id',
            'cartoon as carton',
            'last_updated_by as user_id',
            'created_at',
            'updated_at'
        )->get();

        DB::transaction(function() use($stocks) {

            foreach ($stocks->chunk(500) as $chunk) {

                DB::table('stocks')->insert(json_decode($chunk->toJson(), true));
            }

        });


        $stock_batch =  DB::connection('mysql2')->table('stock_batch')->select(
            'id',
            'received_date',
            DB::raw('(CASE
                WHEN expiry_date = "0000-00-00" THEN NULL
                ELSE expiry_date
             END) AS expiry_date'),
            'wholesales',
            'bulksales',
            'retail',
            'cost_price',
            'retail_cost_price',
            'quantity',
            'product_id as stock_id',
            'supplier_id',
            'created_at',
            'updated_at'
        )->get();

        DB::transaction(function() use($stock_batch) {

            foreach ($stock_batch->chunk(3500) as $chunk) {

                DB::table('stockbatches')->insert(json_decode($chunk->toJson(), true));
            }

        });

        DB::statement(
            "UPDATE stocks INNER JOIN (SELECT stock_id, SUM(wholesales) as wholesum, SUM(bulksales) as bulksum, SUM(retail) as retailsum, SUM(quantity) as quantitysum from stockbatches GROUP BY stock_id)  b ON stocks.id = b.stock_id SET stocks.wholesales = b.wholesum, stocks.bulksales=b.bulksum, stocks.retail = b.retailsum, stocks.quantity =b.quantitysum");

        DB::statement("UPDATE stocks INNER JOIN (SELECT stockbatches.stock_id, stockbatches.retail_cost_price from stockbatches INNER JOIN (SELECT stock_id, MAX(received_date) as rec_date FROM `stockbatches` GROUP BY stock_id ORDER BY stock_id ) b ON stockbatches.stock_id=b.stock_id where stockbatches.received_date = b.rec_date GROUP BY stockbatches.stock_id ORDER BY stockbatches.stock_id) ba ON stocks.id = ba.stock_id SET stocks.retail_cost_price=ba.retail_cost_price");


        DB::statement("UPDATE stocks INNER JOIN (SELECT stockbatches.stock_id, stockbatches.cost_price from stockbatches INNER JOIN (SELECT stock_id, MAX(received_date) as rec_date FROM `stockbatches` GROUP BY stock_id ORDER BY stock_id ) b ON stockbatches.stock_id=b.stock_id where stockbatches.received_date = b.rec_date GROUP BY stockbatches.stock_id ORDER BY stockbatches.stock_id) ba ON stocks.id = ba.stock_id SET stocks.cost_price=ba.cost_price");


        //import invoice
/*
        $invoices =  DB::connection('mysql2')->table('invoices')->select(
            'id',
            'invoice_number',
            'customer_id',
            'payment_id',
            'department',
            'in_department',
            'discount_amount',
            'discount_type',
            'discount_value',
            DB::raw('(CASE
                WHEN status = "COMPLETE" THEN 6
                WHEN status = "DRAFT" THEN 3
                WHEN status = "PAID" THEN 2
                WHEN status = "DELETED" THEN 9
                WHEN status = "DISCOUNT" THEN 10
             END) AS status_id'),
            'sub_total',
            'total_amount_paid',
            'total_profit',
            'total_cost',
            'vat',
            'vat_amount',
            'created_by',
            'last_updated_by',
            'voided_by',
            'invoice_date',
            'sales_time',
            'void_reason',
            'date_voided',
            'void_time',
            'picked_by',
            'packed_by',
            'checked_by',
            'carton_no',
            'online_order_status',
            'online_order_debit',
            'onliner_order_id',
            'created_at',
            'updated_at',
            'before_customer_id'
        )->get();

        DB::transaction(function() use($invoices) {

            foreach ($invoices->chunk(1000) as $chunk) {

                DB::table('invoices')->insert(json_decode($chunk->toJson(), true));
            }

        });

        //import invoiceitems

        $invoiceitems = DB::connection('mysql2')->table('invoices_item')->select(
            'id',
            'invoice_id',
            'stock_id',
            'quantity',
            'customer_id',
            'added_by',
            'discount_added_by',
            'cost_price',
            'selling_price',
            'department',
            'profit',
            'discount_type',
            'discount_value',
            'discount_amount',
            'created_at',
            'updated_at',
            'before_customer_id'
        )->get();

        DB::transaction(function() use( $invoiceitems) {

            foreach ($invoiceitems->chunk(1000) as $chunk) {

                DB::table('invoiceitems')->insert(json_decode($chunk->toJson(), true));
            }

        });

        //import invoicebatches

        $invoicebatches = DB::connection('mysql2')->table('invoice_items_batches')->select(
            'id',
            'invoice_id',
            'invoice_item_id as invoiceitem_id',
            'stock_id',
            'batch_id as stockbatch_id',
            'cost_price',
            'selling_price',
            'department',
            'quantity',
            'created_at',
            'updated_at'
        )->get();

        DB::transaction(function() use($invoicebatches) {

            foreach ($invoicebatches->chunk(1000) as $chunk) {

                DB::table('invoiceitembatches')->insert(json_decode($chunk->toJson(), true));
            }

        });

        //import online order total

        $onlineordertotals = DB::connection('mysql2')->table('onlineordertotal')->get();
        DB::transaction(function() use($onlineordertotals) {

            foreach ($onlineordertotals->chunk(1000) as $chunk) {

                DB::table('onlineordertotals')->insert(json_decode($chunk->toJson(), true));
            }

        });


        //import payment

        $payments = DB::connection('mysql2')->table('payments')->select('*')->get();
        DB::transaction(function() use($payments) {

            foreach ($payments->chunk(1000) as $chunk) {

                DB::table('payments')->insert(json_decode($chunk->toJson(), true));

                DB::table('payments')->update(['invoice_type'=>"App\\Models\\Invoice"]);
            }

        });
        $paymentmethoditems = DB::connection('mysql2')->table('payment_method_table')->select(
            'id',
            'user_id',
            'customer_id',
            'payment_id',
            DB::raw('(CASE
                WHEN payment_method_id = 1 THEN 2
                WHEN payment_method_id = 2 THEN 3
                WHEN payment_method_id = 3 THEN 1
                WHEN payment_method_id = 4 THEN 4
                WHEN payment_method_id = 5 THEN 7
             END) AS paymentmethod_id'),
            'invoice_id',
            'department',
            'payment_date',
            'amount',
            'payment_info',
            'bank_id as bank_account_id',
            'amount_tendered',
            'created_at',
            'updated_at',
            'before_customer_id'
        )->get();
        DB::transaction(function() use($paymentmethoditems) {

            foreach ($paymentmethoditems->chunk(1000) as $chunk) {

                DB::table('paymentmethoditems')->insert(json_decode($chunk->toJson(), true));

                DB::table('paymentmethoditems')->update(['invoice_type'=>"App\\Models\\Invoice"]);
            }

        });


        //import stock transfer

        $stocktransfer = DB::connection('mysql2')->table('stock_transfer')->select(
            'id',
            'transfer_date',
            'transfer_user as user_id',
            'from',
            'to',
            DB::raw('(CASE
                WHEN status = "approved" THEN 7
                WHEN status = "draft" THEN 3 END) as status_id'),
            'created_at',
            'updated_at',
            'note'
        )->get();

        DB::transaction(function() use($stocktransfer) {

            foreach ($stocktransfer->chunk(1000) as $chunk) {

                DB::table('stocktransfers')->insert(json_decode($chunk->toJson(), true));

            }

        });
        //import stock transfer items
        $stock_transfer_items = DB::connection('mysql2')->table('stock_transfer_items')->select(
            'id',
            'transfer_id as stocktransfer_id',
           'stock_id',
            'quantity',
            'rem_quantity',
            'selling_price',
            'cost_price',
            'batch_id as stockbatch_id',
            'added_by as user_id',
            'created_at',
            'updated_at'
        )->get();
        DB::transaction(function() use($stock_transfer_items) {

            foreach ($stock_transfer_items->chunk(1000) as $chunk) {

                DB::table('stocktransferitems')->insert(json_decode($chunk->toJson(), true));

            }

        });





        $stock_bincard =  DB::connection('mysql2')->table('stock_bincard')
            ->select(
                'product_id as stock_id',
        'bin_card_type',
        'bin_card_date',
        'bin_card_user as user_id',
        'to_department',
        'from_department',
        'supplier_id',
        'invoice_id',
        'transfer_id as stocktransfer_id',
        'po_id as purchase_id',
        'in_qty',
        'out_qty',
        'sold_qty',
        'return_qty',
        'comment',
        'balance',
        'department_balance',
        'batch_id as stockbatch_id'

            )->get();

        DB::transaction(function() use($stock_bincard) {

            foreach ($stock_bincard->chunk(1000) as $chunk) {

                DB::table('stockbincards')->insert(json_decode($chunk->toJson(), true));

            }

        });


        $debit_payment_logs = DB::connection('mysql2')->table('debit_payment_logs')->select(
            'id',
                'payment_id',
                'user_id',
                'customer_id',
                'payment_method_id as paymentmethoditem_id',
                DB::raw( "'App\\\Models\\\Invoice' as invoicelog_type"),
                'invoice_id as invoicelog_id',
                DB::raw('-amount as amount'),
                'payment_date',
                'created_at',
                'updated_at'
        )->get();

        DB::transaction(function() use($debit_payment_logs) {

            foreach ($debit_payment_logs->chunk(1000) as $chunk) {

                DB::table('creditpaymentlogs')->insert(json_decode($chunk->toJson(), true));

            }

        });


        $debit_payment_history = DB::connection('mysql2')->table('debit_payment_history')->select(
            'user_id',
                    'customer_id',
                    DB::raw('(CASE
                        WHEN payment_method_id = 1 THEN 2
                        WHEN payment_method_id = 2 THEN 3
                        WHEN payment_method_id = 3 THEN 1
                        WHEN payment_method_id = 4 THEN 4
                        WHEN payment_method_id = 5 THEN 7
                     END) AS paymentmethod_id'),
                    'payment_date',
                    'amount',
        )->get();


        DB::transaction(function() use($debit_payment_history) {

            foreach ($debit_payment_history->chunk(1000) as $chunk) {

                DB::table('creditpaymentlogs')->insert(json_decode($chunk->toJson(), true));

            }

        });



        //import po

        $purchases = DB::connection('mysql2')->table('po')->select(
            'id',
            DB::raw('(CASE
                        WHEN status = "completed" THEN 6
                        WHEN status = "draft" THEN 3
                        WHEN status = "approved" THEN 7
                     END) AS status_id'),
            'created_by as user_id',
            'approved_by as completed_by',
            'supplier_id',
            'for_department as department',
            'date_created',
            'date_completed',
            'created_at',
            'updated_at'
        )->get();

        DB::transaction(function() use($purchases) {

            foreach ($purchases->chunk(3500) as $chunk) {

                DB::table('purchases')->insert(json_decode($chunk->toJson(), true));

            }

        });


        //import po items

        $purchasesitesm = DB::connection('mysql2')->table('po_items')->select(
            'id',
            'po_id as purchase_id',
            'stock_id',
            'expiry_date',
            'qty',
            'cost_price',
            'added_by as user_id',
            'created_at',
            'updated_at'
        )->get();


        DB::transaction(function() use($purchasesitesm) {

            foreach ($purchasesitesm->chunk(3500) as $chunk) {

                DB::table('purchaseitems')->insert(json_decode($chunk->toJson(), true));

            }

        });



           $stock_opening = DB::connection('mysql2')->table('stock_opening')->orderBy('id', 'ASC')->chunk(2000, function($chunks){
               DB::table('stockopenings')->insert(json_decode($chunks->toJson(), true));
           });
*/
        Schema::enableForeignKeyConstraints();


        return self::SUCCESS;
    }
}
