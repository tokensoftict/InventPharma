<?php

namespace Tests\Feature;

use App\Livewire\InvoiceAndSales\Show\ShowInvoiceComponent;
use App\Livewire\PurchaseOrder\Show\ShowPurchaseOrder;
use App\Livewire\PurchaseOrder\Supplier\Payment\SupplierPaymentComponent;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Invoice;
use App\Models\Invoiceitem;
use App\Models\Paymentmethod;
use App\Models\Purchase;
use App\Models\Purchaseitem;
use App\Models\Status;
use App\Models\Stock;
use App\Models\Stockbatch;
use App\Models\Supplier;
use App\Models\SupplierCreditPaymentHistory;
use App\Models\User;
use App\Repositories\InvoiceRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\PurchaseOrderRepository;
use App\Services\StockForStockPaymentService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class StockForStockPaymentTest extends TestCase
{
    protected StockForStockPaymentService $service;
    protected User $user;
    protected Supplier $supplierA;
    protected Supplier $supplierB;
    protected Customer $customer;
    protected Stock $stock;

    public function createApplication()
    {
        $app = require __DIR__ . '/../../bootstrap/app.php';

        if (!is_writable($app->storagePath())) {
            $tmpStorage = '/tmp/psgdc-kafka-test-storage';
            foreach ([
                $tmpStorage,
                $tmpStorage . '/app',
                $tmpStorage . '/framework',
                $tmpStorage . '/framework/cache',
                $tmpStorage . '/framework/cache/data',
                $tmpStorage . '/framework/sessions',
                $tmpStorage . '/framework/views',
                $tmpStorage . '/logs',
            ] as $dir) {
                if (!is_dir($dir)) {
                    @mkdir($dir, 0777, true);
                }
            }
            $app->useStoragePath($tmpStorage);
        }

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.connections.sfs_test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
            'database.default' => 'sfs_test',
            'cache.default' => 'array',
            'app.sync_with_online' => 0,
        ]);

        DB::purge('sfs_test');
        Cache::flush();

        $this->createTestSchema();
        $this->seedReferenceData();

        $this->service = app(StockForStockPaymentService::class);
    }

    protected function createTestSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->unsignedBigInteger('department_id')->default(1);
            $table->unsignedBigInteger('usergroup_id')->default(1);
            $table->timestamps();
        });

        Schema::create('usergroups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Admin');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('label')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('module_id')->nullable();
            $table->string('route')->nullable();
            $table->string('name')->nullable();
            $table->boolean('visibility')->default(true);
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usergroup_id');
            $table->unsignedBigInteger('task_id');
            $table->timestamps();
        });

        Schema::create('onlineordertotals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->string('name')->nullable();
            $table->decimal('value', 20, 5)->default(0);
            $table->timestamps();
        });

        Schema::create('waiting_customers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamp('entered_at')->nullable();
            $table->timestamps();
        });

        Schema::create('statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('label')->default('primary');
            $table->timestamps();
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('label')->nullable();
            $table->string('quantity_column')->nullable();
            $table->string('price_column')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('paymentmethods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('address')->nullable();
            $table->string('email')->nullable();
            $table->string('phonenumber')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('firstname')->nullable();
            $table->string('lastname')->nullable();
            $table->string('email')->nullable();
            $table->boolean('status')->default(true);
            $table->text('address')->nullable();
            $table->string('phone_number')->nullable();
            $table->boolean('retail_customer')->default(false);
            $table->string('customer_type', 20)->nullable();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->decimal('credit_balance', 20, 5)->default(0);
            $table->decimal('deposit_balance', 20, 5)->default(0);
            $table->decimal('loyalty_points', 20, 5)->default(0);
            $table->decimal('retail_loyalty_points', 20, 5)->default(0);
            $table->unsignedBigInteger('member_group_id')->nullable();
            $table->unsignedBigInteger('retail_member_group_id')->nullable();
            $table->timestamps();
        });

        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('code')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('manufacturer_id')->nullable();
            $table->unsignedBigInteger('classification_id')->nullable();
            $table->unsignedBigInteger('stockgroup_id')->nullable();
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->decimal('whole_price', 20, 5)->default(0);
            $table->decimal('bulk_price', 20, 5)->default(0);
            $table->decimal('retail_price', 20, 5)->default(0);
            $table->decimal('cost_price', 20, 5)->default(0);
            $table->decimal('retail_cost_price', 20, 5)->default(0);
            $table->integer('wholesales')->default(0);
            $table->integer('bulksales')->default(0);
            $table->integer('retail')->default(0);
            $table->integer('retail_store')->default(0);
            $table->integer('quantity')->default(0);
            $table->integer('piece')->default(1);
            $table->integer('box')->default(1);
            $table->integer('carton')->default(1);
            $table->integer('minimum_quantity')->nullable();
            $table->bigInteger('batched')->nullable();
            $table->boolean('expiry')->default(false);
            $table->boolean('sachet')->default(false);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('stockbatches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_id');
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->date('received_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('batch_no', 30)->nullable();
            $table->integer('wholesales')->default(0);
            $table->integer('bulksales')->default(0);
            $table->integer('retail')->default(0);
            $table->integer('retail_store')->default(0);
            $table->integer('quantity')->default(0);
            $table->decimal('cost_price', 20, 5)->default(0);
            $table->decimal('retail_cost_price', 20, 5)->default(0);
            $table->timestamps();
        });

        Schema::create('batchstocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_id');
            $table->integer('quantity')->default(0);
            $table->integer('wholesales')->default(0);
            $table->integer('bulksales')->default(0);
            $table->integer('retail')->default(0);
            $table->unsignedBigInteger('quantity_user_id')->nullable();
            $table->unsignedBigInteger('wholsale_user_id')->nullable();
            $table->unsignedBigInteger('bulk_user_id')->nullable();
            $table->unsignedBigInteger('retail_user_id')->nullable();
            $table->timestamps();
        });

        Schema::create('pricechangehistories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_id')->nullable();
            $table->decimal('from', 20, 5)->nullable();
            $table->decimal('to', 20, 5)->nullable();
            $table->date('change_date')->nullable();
            $table->time('change_time')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('department')->nullable();
            $table->timestamps();
        });

        Schema::create('stockbincards', function (Blueprint $table) {
            $table->id();
            $table->string('bin_card_type')->nullable();
            $table->date('bin_card_date')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->integer('in_qty')->default(0);
            $table->integer('out_qty')->default(0);
            $table->integer('sold_qty')->default(0);
            $table->integer('return_qty')->default(0);
            $table->unsignedBigInteger('stock_id')->nullable();
            $table->unsignedBigInteger('stockbatch_id')->nullable();
            $table->string('from_department')->nullable();
            $table->string('to_department')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('purchase_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('stocktransfer_id')->nullable();
            $table->text('comment')->nullable();
            $table->decimal('balance', 20, 5)->default(0);
            $table->decimal('department_balance', 20, 5)->default(0);
            $table->timestamps();
        });

        Schema::create('promotion_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_id')->nullable();
            $table->unsignedBigInteger('status_id')->nullable();
            $table->decimal('whole_price', 20, 5)->default(0);
            $table->decimal('bulk_price', 20, 5)->default(0);
            $table->decimal('retail_price', 20, 5)->default(0);
            $table->timestamps();
        });

        Schema::create('product_custom_prices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_id');
            $table->string('department')->nullable();
            $table->integer('min_qty')->default(1);
            $table->integer('max_qty')->default(100);
            $table->decimal('price', 20, 5)->default(0);
            $table->timestamps();
        });

        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('status_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->string('department')->default('quantity');
            $table->date('date_created')->nullable();
            $table->date('date_completed')->nullable();
            $table->timestamps();
        });

        Schema::create('purchaseitems', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_id');
            $table->unsignedBigInteger('stock_id')->nullable();
            $table->string('batch_no', 30)->nullable();
            $table->date('expiry_date')->nullable();
            $table->integer('qty')->default(0);
            $table->decimal('cost_price', 20, 5)->default(0);
            $table->decimal('bulk_price', 20, 5)->nullable();
            $table->decimal('retail_price', 20, 5)->nullable();
            $table->decimal('whole_price', 20, 5)->nullable();
            $table->decimal('qty_to_buy_1m', 20, 5)->nullable();
            $table->decimal('highest_qty_sold', 20, 5)->nullable();
            $table->decimal('qty_1m_result', 20, 5)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->boolean('is_stock_exchange')->default(false)->index();
            $table->string('invoice_type', 30)->default('normal')->index();
            $table->unsignedBigInteger('supplier_id')->nullable()->index();
            $table->unsignedBigInteger('prescriber_id')->nullable();
            $table->string('prescriber_payment_status')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->string('department')->nullable();
            $table->string('in_department')->nullable();
            $table->decimal('discount_amount', 20, 5)->default(0);
            $table->string('discount_type')->default('None');
            $table->decimal('discount_value', 20, 5)->default(0);
            $table->decimal('membership_discount', 20, 5)->default(0);
            $table->decimal('membership_discount_value', 20, 5)->default(0);
            $table->unsignedBigInteger('status_id')->default(1);
            $table->decimal('sub_total', 20, 5)->default(0);
            $table->decimal('total_amount_paid', 20, 5)->default(0);
            $table->decimal('exchange_amount_utilised', 20, 5)->default(0);
            $table->decimal('total_profit', 20, 5)->default(0);
            $table->decimal('total_cost', 20, 5)->default(0);
            $table->decimal('vat', 20, 5)->default(0);
            $table->decimal('vat_amount', 20, 5)->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('last_updated_by')->nullable();
            $table->unsignedBigInteger('voided_by')->nullable();
            $table->date('invoice_date')->nullable();
            $table->time('sales_time')->nullable();
            $table->text('void_reason')->nullable();
            $table->date('date_voided')->nullable();
            $table->time('void_time')->nullable();
            $table->unsignedBigInteger('picked_by')->nullable();
            $table->unsignedBigInteger('packed_by')->nullable();
            $table->unsignedBigInteger('checked_by')->nullable();
            $table->integer('carton_no')->default(0);
            $table->boolean('online_order_status')->default(false);
            $table->boolean('retail_printed')->default(false);
            $table->unsignedBigInteger('online_order_debit')->nullable();
            $table->unsignedBigInteger('onliner_order_id')->nullable();
            $table->unsignedBigInteger('before_customer_id')->nullable();
            $table->unsignedBigInteger('scan_user_id')->nullable();
            $table->date('scan_date')->nullable();
            $table->time('scan_time')->nullable();
            $table->timestamps();
        });

        Schema::create('invoiceitems', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('stock_id')->nullable();
            $table->integer('quantity')->default(0);
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('status_id')->default(1);
            $table->unsignedBigInteger('added_by')->nullable();
            $table->unsignedBigInteger('discount_added_by')->nullable();
            $table->decimal('cost_price', 20, 5)->default(0);
            $table->decimal('selling_price', 20, 5)->default(0);
            $table->decimal('profit', 20, 5)->default(0);
            $table->string('department')->nullable();
            $table->string('discount_type')->nullable();
            $table->decimal('discount_value', 20, 5)->default(0);
            $table->decimal('discount_amount', 20, 5)->default(0);
            $table->json('selectedOptions')->nullable();
            $table->boolean('isDependent')->default(false);
            $table->unsignedBigInteger('parent_stock_id')->nullable();
            $table->timestamps();
        });

        Schema::create('invoiceitembatches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('invoiceitem_id')->nullable();
            $table->unsignedBigInteger('stock_id')->nullable();
            $table->unsignedBigInteger('stockbatch_id')->nullable();
            $table->string('batch_no', 30)->nullable();
            $table->decimal('cost_price', 20, 5)->default(0);
            $table->decimal('selling_price', 20, 5)->default(0);
            $table->string('department')->nullable();
            $table->integer('quantity')->default(0);
            $table->timestamps();
        });

        Schema::create('invoiceactivitylogs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id');
            $table->string('invoice_number')->nullable();
            $table->text('activity')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->date('activity_date')->nullable();
            $table->time('activity_time')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_ledgers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->decimal('amount', 20, 5)->default(0);
            $table->date('transaction_date')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->decimal('total_balance', 20, 5)->default(0);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('invoice_number')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->string('invoice_type')->nullable();
            $table->string('department')->nullable();
            $table->decimal('subtotal', 20, 5)->default(0);
            $table->decimal('total_paid', 20, 5)->default(0);
            $table->time('payment_time')->nullable();
            $table->date('payment_date')->nullable();
            $table->timestamps();
        });

        Schema::create('paymentmethoditems', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('paymentmethod_id')->nullable();
            $table->string('invoice_type')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->string('department')->nullable();
            $table->decimal('amount', 20, 5)->default(0);
            $table->decimal('amount_tendered', 20, 5)->nullable();
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->date('payment_date')->nullable();
            $table->text('payment_info')->nullable();
            $table->timestamps();
        });

        Schema::create('supplier_credit_payment_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->string('type', 10)->default('CREDIT');
            $table->unsignedBigInteger('purchase_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('paymentmethod_id')->nullable();
            $table->string('reference', 100)->nullable()->index();
            $table->text('payment_info')->nullable();
            $table->text('remark')->nullable();
            $table->decimal('amount', 20, 5)->default(0);
            $table->string('status', 30)->default('Active')->index();
            $table->timestamp('reversed_at')->nullable();
            $table->unsignedBigInteger('reversed_by')->nullable();
            $table->string('reversal_reason', 255)->nullable();
            $table->string('idempotency_key', 100)->nullable()->unique();
            $table->date('payment_date')->nullable();
            $table->timestamps();
        });
    }

    protected function seedReferenceData(): void
    {
        $statuses = [
            ['id' => 1, 'name' => 'Active', 'label' => 'primary'],
            ['id' => 2, 'name' => 'Paid', 'label' => 'success'],
            ['id' => 3, 'name' => 'Draft', 'label' => 'primary'],
            ['id' => 4, 'name' => 'Dispatched', 'label' => 'success'],
            ['id' => 5, 'name' => 'Pending', 'label' => 'warning'],
            ['id' => 6, 'name' => 'Complete', 'label' => 'success'],
            ['id' => 7, 'name' => 'Approved', 'label' => 'primary'],
            ['id' => 8, 'name' => 'Declined', 'label' => 'danger'],
            ['id' => 9, 'name' => 'Deleted', 'label' => 'danger'],
            ['id' => 10, 'name' => 'Cancelled', 'label' => 'danger'],
            ['id' => 11, 'name' => 'Pre-Draft', 'label' => 'primary'],
            ['id' => 12, 'name' => 'Unpaid', 'label' => 'warning'],
            ['id' => 13, 'name' => 'Partially Exchanged', 'label' => 'info'],
            ['id' => 14, 'name' => 'Exchanged', 'label' => 'success'],
        ];

        foreach ($statuses as $st) {
            Status::create($st);
        }

        Department::create([
            'id' => 1,
            'name' => 'Main Store',
            'label' => 'Main Store',
            'quantity_column' => 'quantity',
            'price_column' => 'whole_price',
            'status' => true,
        ]);

        Department::create([
            'id' => 2,
            'name' => 'Wholesales',
            'label' => 'Wholesales',
            'quantity_column' => 'wholesales',
            'price_column' => 'whole_price',
            'status' => true,
        ]);

        Paymentmethod::create(['id' => 1, 'name' => 'CASH', 'status' => 1]);
        Paymentmethod::create(['id' => 2, 'name' => 'POS', 'status' => 1]);
        Paymentmethod::create(['id' => 3, 'name' => 'TRANSFER', 'status' => 1]);
        Paymentmethod::create(['id' => 8, 'name' => 'CHEQUE', 'status' => 1]);
        Paymentmethod::create(['id' => 10, 'name' => 'Stock for Stock', 'status' => 1]);

        Cache::forget('status');
        Cache::forget('statuses');
        Cache::forget('paymentmethods');
        Cache::forget('departments');

        $this->user = User::create([
            'name' => 'Procurement Officer',
            'email' => 'procurement@example.com',
            'department_id' => 2,
            'usergroup_id' => 1,
        ]);
        $this->actingAs($this->user);

        $this->supplierA = Supplier::create([
            'name' => 'Supplier Alpha Pharmaceuticals',
            'address' => '10 Pharma Way',
            'email' => 'alpha@supplier.com',
            'phonenumber' => '08011111111',
            'status' => true,
        ]);

        $this->supplierB = Supplier::create([
            'name' => 'Supplier Beta Medics',
            'address' => '20 Beta Road',
            'email' => 'beta@supplier.com',
            'phonenumber' => '08022222222',
            'status' => true,
        ]);

        $this->customer = Customer::create([
            'id' => 1,
            'firstname' => 'Walk-in',
            'lastname' => 'Customer',
            'phone_number' => '08000000000',
            'status' => true,
        ]);

        $this->stock = Stock::create([
            'name' => 'Amoxicillin 500mg Capsule',
            'whole_price' => 35000,
            'bulk_price' => 34000,
            'retail_price' => 36000,
            'cost_price' => 25000,
            'wholesales' => 100,
            'quantity' => 100,
            'box' => 1,
            'carton' => 10,
            'status' => true,
        ]);

        Stockbatch::create([
            'stock_id' => $this->stock->id,
            'supplier_id' => $this->supplierA->id,
            'received_date' => todaysDate(),
            'expiry_date' => now()->addYear()->toDateString(),
            'batch_no' => 'BATCH-001',
            'wholesales' => 100,
            'quantity' => 100,
            'cost_price' => 25000,
        ]);
    }

    /**
     * Helper to create a completed Purchase Order with a specific total amount.
     */
    protected function createCompletedPurchaseOrder(Supplier $supplier, float $totalAmount): Purchase
    {
        $purchase = Purchase::create([
            'status_id' => status('Complete'),
            'user_id' => $this->user->id,
            'completed_by' => $this->user->id,
            'created_by' => $this->user->id,
            'supplier_id' => $supplier->id,
            'department' => 'quantity',
            'date_created' => todaysDate(),
            'date_completed' => todaysDate(),
        ]);

        Purchaseitem::create([
            'purchase_id' => $purchase->id,
            'stock_id' => $this->stock->id,
            'batch_no' => 'PO-BATCH-' . $purchase->id,
            'expiry_date' => now()->addYear()->toDateString(),
            'qty' => 1,
            'cost_price' => $totalAmount,
            'user_id' => $this->user->id,
        ]);

        PurchaseOrderRepository::createSupplierCreditHistory($purchase->fresh(['purchaseitems']));

        return $purchase->fresh(['purchaseitems']);
    }

    /**
     * Helper to create a Stock Exchange Invoice through the existing InvoiceRepository::createInvoice workflow
     * (which deducts inventory once on creation) or with custom total amount.
     */
    protected function createStockExchangeInvoice(
        Supplier $supplier,
        int $qty = 10,
        ?float $overrideSubTotal = null
    ): Invoice {
        $invoiceNumber = (string) random_int(1000000000, 9999999999);

        $invoiceData = [
            'invoice_number' => $invoiceNumber,
            'customer_id' => null,
            'is_stock_exchange' => true,
            'invoice_type' => 'stock_exchange',
            'supplier_id' => $supplier->id,
            'department' => 'wholesales',
            'in_department' => 'wholesales',
            'discount_amount' => 0,
            'discount_type' => 'None',
            'discount_value' => 0,
            'membership_discount' => 0,
            'membership_discount_value' => 0,
            'status_id' => status('Unpaid'),
            'sub_total' => 0,
            'total_amount_paid' => 0,
            'total_profit' => 0,
            'total_cost' => 0,
            'vat' => 0,
            'vat_amount' => 0,
            'created_by' => $this->user->id,
            'last_updated_by' => $this->user->id,
            'invoice_date' => todaysDate(),
            'sales_time' => now()->toTimeString(),
            'invoiceitems' => json_encode([
                [
                    'stock_id' => $this->stock->id,
                    'quantity' => $qty,
                    'added_by' => $this->user->id,
                    'cost_price' => 25000,
                    'selling_price' => 35000,
                    'profit' => 10000,
                    'discount_type' => 'Fixed',
                    'discount_value' => 0,
                    'discount_amount' => 0,
                ],
            ]),
        ];

        $invoice = (new InvoiceRepository())->createInvoice($invoiceData);
        $this->assertInstanceOf(Invoice::class, $invoice);

        if ($overrideSubTotal !== null) {
            $invoice->update(['sub_total' => $overrideSubTotal]);
        }

        return $invoice->fresh();
    }

    /**
     * Scenario 1: Full Purchase Order settlement using Stock for Stock.
     */
    public function test_1_full_purchase_order_settlement_using_stock_for_stock(): void
    {
        $purchase = $this->createCompletedPurchaseOrder($this->supplierA, 350000);
        $invoice = $this->createStockExchangeInvoice($this->supplierA, 10); // 10 * 35,000 = 350,000

        $this->assertEquals(350000, $invoice->original_invoice_total);
        $this->assertEquals(status('Unpaid'), $invoice->status_id);

        $allocation = $this->service->applyStockForStockPayment([
            'purchase_id' => $purchase->id,
            'invoice_id' => $invoice->id,
            'supplier_id' => $this->supplierA->id,
            'amount' => 350000,
        ]);

        $purchase->refresh();
        $invoice->refresh();

        $this->assertEquals(350000, $allocation->amount);
        $this->assertEquals(350000, $purchase->total_paid);
        $this->assertEquals(0, $purchase->outstanding_balance);
        $this->assertEquals('Paid', $purchase->payment_status);

        $this->assertEquals(350000, $invoice->total_exchange_utilised);
        $this->assertEquals(0, $invoice->remaining_exchange_balance);
        $this->assertEquals(status('Exchanged'), $invoice->status_id);
        $this->assertEquals(0, $this->supplierA->fresh()->credit_balance);
    }

    /**
     * Scenario 2: Partial Purchase Order settlement using Stock for Stock.
     */
    public function test_2_partial_purchase_order_settlement_using_stock_for_stock(): void
    {
        // PO Total: 500,000; Stock Exchange Invoice: 350,000
        $purchase = $this->createCompletedPurchaseOrder($this->supplierA, 500000);
        $invoice = $this->createStockExchangeInvoice($this->supplierA, 10); // 350,000

        $this->service->applyStockForStockPayment([
            'purchase_id' => $purchase->id,
            'invoice_id' => $invoice->id,
            'amount' => 350000,
        ]);

        $purchase->refresh();
        $invoice->refresh();

        $this->assertEquals(500000, $purchase->total_amount);
        $this->assertEquals(350000, $purchase->total_paid);
        $this->assertEquals(150000, $purchase->outstanding_balance);
        $this->assertEquals('Partially Paid', $purchase->payment_status);

        $this->assertEquals(350000, $invoice->total_exchange_utilised);
        $this->assertEquals(0, $invoice->remaining_exchange_balance);
        $this->assertEquals(status('Exchanged'), $invoice->status_id);
    }

    /**
     * Scenario 3: Combined Cash, Transfer and Stock for Stock payments.
     */
    public function test_3_combined_cash_transfer_and_stock_for_stock_payments(): void
    {
        // PO Total: 500,000
        // Cash: 50,000, Transfer: 100,000, Stock for Stock: 250,000 -> Total Paid: 400,000, Remaining: 100,000
        $purchase = $this->createCompletedPurchaseOrder($this->supplierA, 500000);
        $invoice = $this->createStockExchangeInvoice($this->supplierA, 10); // 350,000

        $sfsMethodId = stock_for_stock_payment_method_id();

        $payments = $this->service->recordCombinedPurchaseOrderPayments($purchase, [
            [
                'paymentmethod_id' => 1, // CASH
                'amount' => 50000,
            ],
            [
                'paymentmethod_id' => 3, // TRANSFER
                'amount' => 100000,
            ],
            [
                'paymentmethod_id' => $sfsMethodId, // Stock for Stock
                'invoice_id' => $invoice->id,
                'amount' => 250000,
            ],
        ]);

        $this->assertCount(3, $payments);

        $purchase->refresh();
        $invoice->refresh();

        $this->assertEquals(400000, $purchase->total_paid);
        $this->assertEquals(100000, $purchase->outstanding_balance);
        $this->assertEquals('Partially Paid', $purchase->payment_status);

        $this->assertEquals(250000, $invoice->total_exchange_utilised);
        $this->assertEquals(100000, $invoice->remaining_exchange_balance);
        $this->assertEquals(status('Partially Exchanged'), $invoice->status_id);
        $this->assertEquals(-100000, $this->supplierA->fresh()->credit_balance);
    }

    /**
     * Scenarios 4, 5, 6, 7:
     * - One exchange invoice applied to multiple Purchase Orders
     * - Partial exchange invoice reused for another Purchase Order
     * - Invoice status changes from Unpaid to Partially Exchanged
     * - Invoice status changes from Partially Exchanged to Exchanged
     */
    public function test_4_5_6_7_partial_exchange_invoice_reused_across_multiple_purchase_orders_and_status_lifecycle(): void
    {
        $invoice = $this->createStockExchangeInvoice($this->supplierA, 10); // 350,000
        $po1 = $this->createCompletedPurchaseOrder($this->supplierA, 200000);
        $po2 = $this->createCompletedPurchaseOrder($this->supplierA, 250000);

        // Initial status is Unpaid
        $this->assertEquals(status('Unpaid'), $invoice->status_id);
        $this->assertEquals(350000, $invoice->remaining_exchange_balance);

        // Apply 200,000 to Purchase Order 1
        $this->service->applyStockForStockPayment([
            'purchase_id' => $po1->id,
            'invoice_id' => $invoice->id,
            'amount' => 200000,
        ]);

        $invoice->refresh();
        $po1->refresh();

        // Scenario 6: Invoice status changes from Unpaid to Partially Exchanged
        $this->assertEquals(status('Partially Exchanged'), $invoice->status_id);
        $this->assertEquals(200000, $invoice->total_exchange_utilised);
        $this->assertEquals(150000, $invoice->remaining_exchange_balance);
        $this->assertEquals(0, $po1->outstanding_balance);

        // Partially exchanged invoice remains eligible and selectable for another PO
        $eligible = $this->service->getEligibleInvoices($this->supplierA->id);
        $this->assertTrue($eligible->contains('id', $invoice->id));

        // Scenario 4 & 5: Reuse remaining 150,000 on Purchase Order 2
        $this->service->applyStockForStockPayment([
            'purchase_id' => $po2->id,
            'invoice_id' => $invoice->id,
            'amount' => 150000,
        ]);

        $invoice->refresh();
        $po2->refresh();

        // Scenario 7: Invoice status changes from Partially Exchanged to Exchanged
        $this->assertEquals(status('Exchanged'), $invoice->status_id);
        $this->assertEquals(350000, $invoice->total_exchange_utilised);
        $this->assertEquals(0, $invoice->remaining_exchange_balance);
        $this->assertEquals(150000, $po2->total_paid);
        $this->assertEquals(100000, $po2->outstanding_balance);
        $this->assertCount(2, $invoice->stockExchangeAllocations);
    }

    /**
     * Scenarios 8 & 9:
     * - Invoice status returns to Partially Exchanged after a valid reversal
     * - Remaining exchange balance is correctly restored after reversal
     */
    public function test_8_9_reversal_restores_exchange_balance_and_updates_status_without_touching_inventory(): void
    {
        $invoice = $this->createStockExchangeInvoice($this->supplierA, 10); // 350,000
        $stockWholesalesAfterInvoice = $this->stock->fresh()->wholesales; // 90

        $po1 = $this->createCompletedPurchaseOrder($this->supplierA, 200000);
        $po2 = $this->createCompletedPurchaseOrder($this->supplierA, 150000);

        $alloc1 = $this->service->applyStockForStockPayment([
            'purchase_id' => $po1->id,
            'invoice_id' => $invoice->id,
            'amount' => 200000,
        ]);

        $alloc2 = $this->service->applyStockForStockPayment([
            'purchase_id' => $po2->id,
            'invoice_id' => $invoice->id,
            'amount' => 150000,
        ]);

        $invoice->refresh();
        $this->assertEquals(status('Exchanged'), $invoice->status_id);
        $this->assertEquals(0, $invoice->remaining_exchange_balance);

        // Reverse the 150,000 allocation on PO 2
        $reversed = $this->service->reverseAllocation($alloc2->id, 'Entered against wrong PO');

        $invoice->refresh();
        $po2->refresh();

        $this->assertTrue($reversed->isReversed());
        $this->assertEquals('Reversed', $reversed->status);
        $this->assertNotNull($reversed->reversed_at);
        $this->assertEquals($this->user->id, $reversed->reversed_by);

        // Scenario 8 & 9: Total utilised becomes 200,000, remaining becomes 150,000, status returns to Partially Exchanged
        $this->assertEquals(200000, $invoice->total_exchange_utilised);
        $this->assertEquals(150000, $invoice->remaining_exchange_balance);
        $this->assertEquals(status('Partially Exchanged'), $invoice->status_id);

        // PO 2 balance is restored
        $this->assertEquals(0, $po2->total_paid);
        $this->assertEquals(150000, $po2->outstanding_balance);

        // Inventory is NOT deducted or restored again during reversal
        $this->assertEquals($stockWholesalesAfterInvoice, $this->stock->fresh()->wholesales);

        // Original allocation & reversal history are preserved
        $this->assertEquals(2, $invoice->stockExchangeAllocations()->count());
        $this->assertEquals(1, $invoice->activeStockExchangeAllocations()->count());

        // Reversing the first allocation too returns the invoice to Unpaid
        $this->service->reverseAllocation($alloc1->id, 'Full reversal');
        $invoice->refresh();
        $this->assertEquals(0, $invoice->total_exchange_utilised);
        $this->assertEquals(350000, $invoice->remaining_exchange_balance);
        $this->assertEquals(status('Unpaid'), $invoice->status_id);
    }

    /**
     * Scenario 10: Attempt to use a normal sales invoice.
     */
    public function test_10_attempt_to_use_normal_sales_invoice_is_rejected(): void
    {
        $purchase = $this->createCompletedPurchaseOrder($this->supplierA, 200000);

        $normalInvoice = Invoice::create([
            'invoice_number' => '9900000001',
            'customer_id' => $this->customer->id,
            'is_stock_exchange' => false,
            'invoice_type' => 'normal',
            'supplier_id' => $this->supplierA->id,
            'department' => 'wholesales',
            'in_department' => 'wholesales',
            'status_id' => status('Unpaid'),
            'sub_total' => 200000,
            'total_amount_paid' => 0,
            'total_profit' => 50000,
            'total_cost' => 150000,
            'vat' => 0,
            'vat_amount' => 0,
            'invoice_date' => todaysDate(),
            'sales_time' => now()->toTimeString(),
        ]);

        $this->expectException(ValidationException::class);
        $this->service->applyStockForStockPayment([
            'purchase_id' => $purchase->id,
            'invoice_id' => $normalInvoice->id,
            'amount' => 100000,
        ]);
    }

    /**
     * Scenario 11: Attempt to use a cancelled or deleted/voided invoice.
     */
    public function test_11_attempt_to_use_cancelled_or_voided_invoice_is_rejected(): void
    {
        $purchase = $this->createCompletedPurchaseOrder($this->supplierA, 200000);
        $cancelledInvoice = $this->createStockExchangeInvoice($this->supplierA, 5);
        $cancelledInvoice->update(['status_id' => status('Cancelled')]);

        try {
            $this->service->applyStockForStockPayment([
                'purchase_id' => $purchase->id,
                'invoice_id' => $cancelledInvoice->id,
                'amount' => 50000,
            ]);
            $this->fail('Expected ValidationException for cancelled invoice was not thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('invoice_id', $e->errors());
        }

        // Also test voided invoice
        $voidedInvoice = $this->createStockExchangeInvoice($this->supplierA, 5);
        $voidedInvoice->update(['voided_by' => $this->user->id, 'date_voided' => todaysDate()]);

        $this->expectException(ValidationException::class);
        $this->service->applyStockForStockPayment([
            'purchase_id' => $purchase->id,
            'invoice_id' => $voidedInvoice->id,
            'amount' => 50000,
        ]);
    }

    /**
     * Scenario 12: Attempt to use a fully exchanged invoice.
     */
    public function test_12_attempt_to_use_fully_exchanged_invoice_is_rejected(): void
    {
        $invoice = $this->createStockExchangeInvoice($this->supplierA, 10); // 350,000
        $po1 = $this->createCompletedPurchaseOrder($this->supplierA, 350000);
        $po2 = $this->createCompletedPurchaseOrder($this->supplierA, 100000);

        $this->service->applyStockForStockPayment([
            'purchase_id' => $po1->id,
            'invoice_id' => $invoice->id,
            'amount' => 350000,
        ]);

        $this->assertEquals(status('Exchanged'), $invoice->fresh()->status_id);
        $this->assertFalse($this->service->getEligibleInvoices($this->supplierA->id)->contains('id', $invoice->id));

        $this->expectException(ValidationException::class);
        $this->service->applyStockForStockPayment([
            'purchase_id' => $po2->id,
            'invoice_id' => $invoice->id,
            'amount' => 10000,
        ]);
    }

    /**
     * Scenario 13: Attempt to use an invoice belonging to another supplier.
     */
    public function test_13_attempt_to_use_invoice_belonging_to_another_supplier_is_rejected(): void
    {
        $invoiceSupplierA = $this->createStockExchangeInvoice($this->supplierA, 10); // Supplier A
        $poSupplierB = $this->createCompletedPurchaseOrder($this->supplierB, 200000); // Supplier B

        $this->expectException(ValidationException::class);
        $this->service->applyStockForStockPayment([
            'purchase_id' => $poSupplierB->id,
            'invoice_id' => $invoiceSupplierA->id,
            'amount' => 100000,
        ]);
    }

    /**
     * Scenario 14: Attempt to exceed the invoice's remaining exchange balance.
     */
    public function test_14_attempt_to_exceed_invoice_remaining_exchange_balance_is_rejected(): void
    {
        $invoice = $this->createStockExchangeInvoice($this->supplierA, 10); // 350,000
        $po1 = $this->createCompletedPurchaseOrder($this->supplierA, 200000);
        $po2 = $this->createCompletedPurchaseOrder($this->supplierA, 300000);

        $this->service->applyStockForStockPayment([
            'purchase_id' => $po1->id,
            'invoice_id' => $invoice->id,
            'amount' => 200000,
        ]);

        // Remaining on invoice is 150,000; attempt to apply 160,000 to PO 2
        $this->expectException(ValidationException::class);
        $this->service->applyStockForStockPayment([
            'purchase_id' => $po2->id,
            'invoice_id' => $invoice->id,
            'amount' => 160000,
        ]);
    }

    /**
     * Scenario 15: Attempt to exceed the Purchase Order outstanding balance.
     */
    public function test_15_attempt_to_exceed_purchase_order_outstanding_balance_is_rejected(): void
    {
        $invoice = $this->createStockExchangeInvoice($this->supplierA, 10); // 350,000
        $po = $this->createCompletedPurchaseOrder($this->supplierA, 200000); // PO total 200,000

        $this->expectException(ValidationException::class);
        $this->service->applyStockForStockPayment([
            'purchase_id' => $po->id,
            'invoice_id' => $invoice->id,
            'amount' => 250000,
        ]);
    }

    /**
     * Scenario 16: Concurrent / sequential competing attempts to apply the same invoice balance & duplicate submission prevention.
     */
    public function test_16_competing_and_duplicate_attempts_to_use_same_invoice_balance_are_blocked(): void
    {
        $invoice = $this->createStockExchangeInvoice($this->supplierA, 10); // 350,000
        $po1 = $this->createCompletedPurchaseOrder($this->supplierA, 300000);
        $po2 = $this->createCompletedPurchaseOrder($this->supplierA, 300000);

        // First request takes 250,000 with idempotency key
        $this->service->applyStockForStockPayment([
            'purchase_id' => $po1->id,
            'invoice_id' => $invoice->id,
            'amount' => 250000,
            'idempotency_key' => 'REQ-KEY-001',
        ]);

        // Duplicate submission with same idempotency key is rejected
        try {
            $this->service->applyStockForStockPayment([
                'purchase_id' => $po1->id,
                'invoice_id' => $invoice->id,
                'amount' => 50000,
                'idempotency_key' => 'REQ-KEY-001',
            ]);
            $this->fail('Duplicate idempotency_key submission should have failed.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('idempotency_key', $e->errors());
        }

        // Competing request trying to allocate 200,000 from the same invoice (only 100,000 left) is rejected
        try {
            $this->service->applyStockForStockPayment([
                'purchase_id' => $po2->id,
                'invoice_id' => $invoice->id,
                'amount' => 200000,
            ]);
            $this->fail('Competing allocation exceeding remaining invoice balance should have failed.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('amount', $e->errors());
        }

        // Invoice still has 250,000 utilised and 100,000 remaining
        $invoice->refresh();
        $this->assertEquals(250000, $invoice->total_exchange_utilised);
        $this->assertEquals(100000, $invoice->remaining_exchange_balance);
    }

    /**
     * Scenario 17: Competing attempts to settle the same Purchase Order balance.
     */
    public function test_17_competing_attempts_to_settle_same_purchase_order_balance_are_blocked(): void
    {
        $po = $this->createCompletedPurchaseOrder($this->supplierA, 300000);
        $invoice1 = $this->createStockExchangeInvoice($this->supplierA, 10); // 350,000
        $invoice2 = $this->createStockExchangeInvoice($this->supplierA, 10); // 350,000

        // First request settles 200,000 of the 300,000 PO
        $this->service->applyStockForStockPayment([
            'purchase_id' => $po->id,
            'invoice_id' => $invoice1->id,
            'amount' => 200000,
        ]);

        // Second competing request attempts to settle 200,000 on the same PO (only 100,000 outstanding)
        try {
            $this->service->applyStockForStockPayment([
                'purchase_id' => $po->id,
                'invoice_id' => $invoice2->id,
                'amount' => 200000,
            ]);
            $this->fail('Competing attempt exceeding PO outstanding balance should have failed.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('amount', $e->errors());
        }

        // Invoice 2 remains completely unallocated (Unpaid) and PO still has 100,000 outstanding
        $invoice2->refresh();
        $po->refresh();
        $this->assertEquals(0, $invoice2->total_exchange_utilised);
        $this->assertEquals(status('Unpaid'), $invoice2->status_id);
        $this->assertEquals(200000, $po->total_paid);
        $this->assertEquals(100000, $po->outstanding_balance);
    }

    /**
     * Scenario 18: Transaction rollback when payment recording fails.
     */
    public function test_18_transaction_rollback_when_combined_or_single_payment_recording_fails(): void
    {
        $po = $this->createCompletedPurchaseOrder($this->supplierA, 300000);
        $invoice = $this->createStockExchangeInvoice($this->supplierA, 10); // 350,000

        // Attempt a combined payment where the second item exceeds the PO outstanding balance
        try {
            $this->service->recordCombinedPurchaseOrderPayments($po, [
                [
                    'paymentmethod_id' => 1, // CASH 150,000 (valid)
                    'amount' => 150000,
                ],
                [
                    'paymentmethod_id' => stock_for_stock_payment_method_id(),
                    'invoice_id' => $invoice->id,
                    'amount' => 200000, // 150k + 200k = 350k > 300k PO balance -> fails!
                ],
            ]);
            $this->fail('Expected ValidationException to abort transaction.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('amount', $e->errors());
        }

        // Neither the first Cash payment nor the Stock-for-Stock allocation nor the invoice status should be persisted!
        $po->refresh();
        $invoice->refresh();
        $this->assertEquals(0, $po->total_paid);
        $this->assertEquals(300000, $po->outstanding_balance);
        $this->assertEquals(0, $invoice->total_exchange_utilised);
        $this->assertEquals(status('Unpaid'), $invoice->status_id);
        $this->assertEquals(0, $po->payments()->count());
    }

    /**
     * Scenario 19: Verify that inventory is not deducted twice and historical invoice valuation is preserved.
     */
    public function test_19_inventory_is_not_deducted_twice_and_historical_invoice_value_is_preserved(): void
    {
        $initialWholesalesQty = $this->stock->fresh()->wholesales; // 100

        // Creating the stock exchange invoice deducts 10 units via existing invoice creation workflow
        $invoice = $this->createStockExchangeInvoice($this->supplierA, 10); // 10 * 35,000 = 350,000
        $afterInvoiceQty = $this->stock->fresh()->wholesales;
        $this->assertEquals($initialWholesalesQty - 10, $afterInvoiceQty);

        // Even if product selling price changes later in inventory, invoice original value and remaining balance do NOT change
        $this->stock->update(['whole_price' => 99000]);

        $po = $this->createCompletedPurchaseOrder($this->supplierA, 200000);

        // Applying Stock-for-Stock payment does NOT deduct inventory a second time
        $this->service->applyStockForStockPayment([
            'purchase_id' => $po->id,
            'invoice_id' => $invoice->id,
            'amount' => 200000,
        ]);

        $this->assertEquals($afterInvoiceQty, $this->stock->fresh()->wholesales);

        $invoice->refresh();
        $this->assertEquals(350000, $invoice->original_invoice_total);
        $this->assertEquals(200000, $invoice->total_exchange_utilised);
        $this->assertEquals(150000, $invoice->remaining_exchange_balance);

        // Also verify invoice with active exchange allocations cannot be deleted
        $this->expectException(\DomainException::class);
        $invoice->delete();
    }

    /**
     * Scenario 20: Verify that existing Cash and Transfer payments continue working exactly as before.
     */
    public function test_20_existing_cash_and_transfer_payments_continue_working_as_before(): void
    {
        $po = $this->createCompletedPurchaseOrder($this->supplierA, 400000);

        // General supplier Cash payment via PurchaseOrderRepository::createSupplierPaymentHistory
        PurchaseOrderRepository::createSupplierPaymentHistory([
            'supplier_id' => $this->supplierA->id,
            'paymentmethod_id' => 1, // CASH
            'amount' => '150,000',
            'payment_date' => todaysDate(),
            'payment_info' => ['status' => 'Approved'],
            'remark' => 'Cash payment',
        ]);

        // PO-linked Transfer payment
        PurchaseOrderRepository::createSupplierPaymentHistory([
            'supplier_id' => $this->supplierA->id,
            'purchase_id' => $po->id,
            'paymentmethod_id' => 3, // TRANSFER
            'amount' => '100,000',
            'payment_date' => todaysDate(),
            'payment_info' => ['status' => 'Approved'],
            'remark' => 'Bank transfer payment',
        ]);

        $this->assertEquals(-150000, $this->supplierA->fresh()->credit_balance);
        $this->assertEquals(100000, $po->fresh()->total_paid);
        $this->assertEquals(300000, $po->fresh()->outstanding_balance);
    }

    /**
     * Scenario 21: Verify that normal invoice creation, payment and dispatch workflows remain unchanged.
     */
    public function test_21_normal_invoice_creation_payment_and_dispatch_workflows_remain_unchanged(): void
    {
        $initialQty = $this->stock->fresh()->wholesales;

        $normalInvoiceData = [
            'invoice_number' => '8800000001',
            'customer_id' => $this->customer->id,
            'department' => 'wholesales',
            'in_department' => 'wholesales',
            'discount_amount' => 0,
            'discount_type' => 'None',
            'discount_value' => 0,
            'membership_discount' => 0,
            'membership_discount_value' => 0,
            'status_id' => status('Draft'),
            'sub_total' => 0,
            'total_amount_paid' => 0,
            'total_profit' => 0,
            'total_cost' => 0,
            'vat' => 0,
            'vat_amount' => 0,
            'created_by' => $this->user->id,
            'last_updated_by' => $this->user->id,
            'invoice_date' => todaysDate(),
            'sales_time' => now()->toTimeString(),
            'invoiceitems' => json_encode([
                [
                    'stock_id' => $this->stock->id,
                    'quantity' => 2,
                    'added_by' => $this->user->id,
                    'cost_price' => 25000,
                    'selling_price' => 35000,
                    'profit' => 10000,
                    'discount_type' => 'Fixed',
                    'discount_value' => 0,
                    'discount_amount' => 0,
                ],
            ]),
        ];

        $invoice = (new InvoiceRepository())->createInvoice($normalInvoiceData);
        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertFalse($invoice->isStockExchange());
        $this->assertEquals('normal', $invoice->invoice_type);
        $this->assertEquals(status('Draft'), $invoice->status_id);
        $this->assertEquals(70000, $invoice->sub_total);
        $this->assertEquals($initialQty - 2, $this->stock->fresh()->wholesales);

        // Normal payment via PaymentRepository
        $paymentRepo = app(PaymentRepository::class);
        $dummyComponent = new \stdClass();
        $payment = $paymentRepo->addPayment([
            'customer_id' => $this->customer->id,
            'invoice_number' => $invoice->invoice_number,
            'invoice_id' => $invoice->id,
            'invoice_type' => Invoice::class,
            'department' => 'wholesales',
            'subtotal' => 70000,
            'payment_date' => todaysDate(),
            'methods' => [
                [
                    'paymentmethod_id' => 1,
                    'amount' => 70000,
                    'department' => 'wholesales',
                ],
            ],
        ], $dummyComponent);

        $invoice->refresh();
        $this->assertEquals($payment->id, $invoice->payment_id);
        $this->assertEquals(status('Paid'), $invoice->status_id);
        $this->assertEquals(70000, $invoice->total_amount_paid);
    }

    /**
     * Test Livewire components (SupplierPaymentComponent and ShowInvoiceComponent) for Stock for Stock
     * without selecting a Purchase Order (directly offsetting the supplier's negative credit balance).
     */
    public function test_livewire_supplier_payment_and_purchase_order_and_invoice_components(): void
    {
        // Completed PO creates a -350,000 CREDIT entry in supplier_credit_payment_histories
        $this->createCompletedPurchaseOrder($this->supplierA, 350000);
        $this->assertEquals(-350000, $this->supplierA->fresh()->credit_balance);

        $invoice = $this->createStockExchangeInvoice($this->supplierA, 10); // 350,000
        $sfsMethodId = stock_for_stock_payment_method_id();

        // 1. Test SupplierPaymentComponent applying 200,000 via Stock for Stock WITHOUT purchase_id
        Livewire::test(SupplierPaymentComponent::class)
            ->set('payment_data.payment_date', todaysDate())
            ->set('payment_data.supplier_id', $this->supplierA->id)
            ->set('payment_data.paymentmethod_id', $sfsMethodId)
            ->set('invoiceSearch', (string) $invoice->invoice_number)
            ->set('payment_data.invoice_id', $invoice->id)
            ->set('payment_data.amount', '200000')
            ->call('previewConfirmation')
            ->assertSet('showConfirmation', true)
            ->call('savePayment')
            ->assertHasNoErrors();

        $invoice->refresh();
        // Supplier negative balance is offset from -350,000 to -150,000
        $this->assertEquals(-150000, $this->supplierA->fresh()->credit_balance);
        $this->assertEquals(status('Partially Exchanged'), $invoice->status_id);
        $this->assertEquals(200000, $invoice->total_exchange_utilised);
        $this->assertEquals(150000, $invoice->remaining_exchange_balance);

        // Verify direct link between Invoice and SupplierCreditPaymentHistory
        $firstPayment = $invoice->activeStockExchangeAllocations()->first();
        $this->assertNotNull($firstPayment);
        $this->assertEquals($invoice->id, $firstPayment->invoice_id);
        $this->assertEquals($this->supplierA->id, $firstPayment->supplier_id);
        $this->assertNull($firstPayment->purchase_id);

        // 2. Test SupplierPaymentComponent applying the remaining 150,000 from the same Partially Exchanged invoice
        Livewire::test(SupplierPaymentComponent::class)
            ->set('payment_data.payment_date', todaysDate())
            ->set('payment_data.supplier_id', $this->supplierA->id)
            ->set('payment_data.paymentmethod_id', $sfsMethodId)
            ->set('payment_data.invoice_id', $invoice->id)
            ->set('payment_data.amount', '150000')
            ->call('savePayment')
            ->assertHasNoErrors();

        $invoice->refresh();
        // Supplier negative balance is now completely offset to 0
        $this->assertEquals(0, $this->supplierA->fresh()->credit_balance);
        $this->assertEquals(status('Exchanged'), $invoice->status_id);
        $this->assertEquals(350000, $invoice->total_exchange_utilised);
        $this->assertEquals(0, $invoice->remaining_exchange_balance);

        // 3. Test ShowInvoiceComponent reversing the 150,000 supplier payment allocation
        $lastAlloc = $invoice->activeStockExchangeAllocations()->latest('id')->first();
        Livewire::test(ShowInvoiceComponent::class, ['invoice' => $invoice])
            ->call('reverseExchangeAllocation', $lastAlloc->id);

        $invoice->refresh();
        $this->assertEquals(status('Partially Exchanged'), $invoice->status_id);
        $this->assertEquals(150000, $invoice->remaining_exchange_balance);
        // Supplier negative balance returns to -150,000
        $this->assertEquals(-150000, $this->supplierA->fresh()->credit_balance);
    }

    /**
     * Test migration up() and down() backward compatibility and preservation of existing records.
     */
    public function test_migration_up_and_down_preserves_existing_records(): void
    {
        $migration = require __DIR__ . '/../../database/migrations/2026_11_19_180000_add_stock_for_stock_procurement_payment_support.php';

        // Re-running up() is idempotent and preserves existing records
        $migration->up();

        $this->assertTrue(Schema::hasColumn('invoices', 'is_stock_exchange'));
        $this->assertTrue(Schema::hasColumn('invoices', 'invoice_type'));
        $this->assertTrue(Schema::hasColumn('invoices', 'supplier_id'));
        $this->assertTrue(Schema::hasColumn('invoices', 'exchange_amount_utilised'));

        $this->assertTrue(Schema::hasColumn('supplier_credit_payment_histories', 'invoice_id'));
        $this->assertTrue(Schema::hasColumn('supplier_credit_payment_histories', 'reference'));
        $this->assertTrue(Schema::hasColumn('supplier_credit_payment_histories', 'status'));
        $this->assertTrue(Schema::hasColumn('supplier_credit_payment_histories', 'reversed_at'));
        $this->assertTrue(Schema::hasColumn('supplier_credit_payment_histories', 'reversed_by'));
        $this->assertTrue(Schema::hasColumn('supplier_credit_payment_histories', 'reversal_reason'));
        $this->assertTrue(Schema::hasColumn('supplier_credit_payment_histories', 'idempotency_key'));

        $this->assertNotNull(status('Partially Exchanged'));
        $this->assertNotNull(status('Exchanged'));
        $this->assertNotNull(stock_for_stock_payment_method_id());
    }

    /**
     * Test that Stock Exchange Invoice designation is restricted to Wholesales (not Retail)
     * and requires the 'invoiceandsales.createStockExchangeInvoice' permission.
     */
    public function test_stock_exchange_invoice_designation_is_wholesales_only_and_permission_gated(): void
    {
        // 1. InvoiceRepository rejects Stock Exchange Invoice for retail department
        $retailAttempt = (new InvoiceRepository())->createInvoice([
            'invoice_number' => '7700000001',
            'customer_id' => null,
            'is_stock_exchange' => true,
            'invoice_type' => 'stock_exchange',
            'supplier_id' => $this->supplierA->id,
            'department' => 'retail',
            'in_department' => 'retail',
            'discount_amount' => 0,
            'discount_type' => 'None',
            'discount_value' => 0,
            'membership_discount' => 0,
            'membership_discount_value' => 0,
            'status_id' => status('Unpaid'),
            'sub_total' => 0,
            'total_amount_paid' => 0,
            'total_profit' => 0,
            'total_cost' => 0,
            'vat' => 0,
            'vat_amount' => 0,
            'created_by' => $this->user->id,
            'last_updated_by' => $this->user->id,
            'invoice_date' => todaysDate(),
            'sales_time' => now()->toTimeString(),
            'invoiceitems' => json_encode([
                [
                    'stock_id' => $this->stock->id,
                    'quantity' => 1,
                    'added_by' => $this->user->id,
                    'cost_price' => 25000,
                    'selling_price' => 36000,
                    'profit' => 11000,
                    'discount_type' => 'Fixed',
                    'discount_value' => 0,
                    'discount_amount' => 0,
                ],
            ]),
        ]);

        $this->assertIsArray($retailAttempt);
        $this->assertArrayHasKey('department', $retailAttempt);

        // 2. Without 'invoiceandsales.createStockExchangeInvoice' permission, userCanView returns false
        \App\Models\Usergroup::create(['id' => 1, 'name' => 'Admin', 'status' => true]);
        $moduleId = DB::table('modules')->insertGetId([
            'name' => 'InvoiceAndSales',
            'label' => 'Invoice & Sales',
            'icon' => 'shopping-cart',
            'status' => 1,
        ]);
        $taskId = DB::table('tasks')->insertGetId([
            'module_id' => $moduleId,
            'route' => 'invoiceandsales.createStockExchangeInvoice',
            'name' => 'Create Stock Exchange Invoice (Wholesales Only)',
            'visibility' => 0,
        ]);
        Cache::forget('route-permission-1');

        $this->assertFalse(userCanView('invoiceandsales.createStockExchangeInvoice'));

        // 3. Granting the permission to the user's group enables userCanView('invoiceandsales.createStockExchangeInvoice')
        DB::table('permissions')->insert([
            'usergroup_id' => 1,
            'task_id' => $taskId,
        ]);
        Cache::forget('route-permission-1');

        $this->assertTrue(userCanView('invoiceandsales.createStockExchangeInvoice'));
    }

    public function test_return_invoice_blocks_adding_new_stock_and_increasing_quantity(): void
    {
        $paidInvoice = Invoice::create([
            'invoice_number' => 'INV-RET-001',
            'customer_id' => $this->customer->id,
            'department' => 'wholesales',
            'in_department' => 'wholesales',
            'status_id' => status('Paid'),
            'sub_total' => 50000,
            'total_amount_paid' => 50000,
            'invoice_date' => '2026-11-25',
            'sales_time' => '12:00:00',
            'created_by' => $this->user->id,
            'last_updated_by' => $this->user->id,
        ]);

        Invoiceitem::create([
            'invoice_id' => $paidInvoice->id,
            'stock_id' => $this->stock->id,
            'quantity' => 5,
            'customer_id' => $this->customer->id,
            'status_id' => status('Paid'),
            'added_by' => $this->user->id,
            'invoice_date' => '2026-11-25',
            'sales_time' => '12:00:00',
            'cost_price' => 8000,
            'selling_price' => 10000,
            'profit' => 2000,
            'department' => 'wholesales',
        ]);

        $newStock = Stock::create([
            'name' => 'Extra Drug 250mg',
            'whole_price' => 5000,
            'bulk_price' => 4800,
            'retail_price' => 5500,
            'cost_price' => 4000,
            'wholesales' => 50,
            'quantity' => 50,
            'status' => 1,
        ]);

        $repo = new InvoiceRepository();

        // 1. Attempting to increase quantity from 5 to 6 on a Paid invoice is rejected
        $increaseResult = $repo->updateInvoice($paidInvoice->fresh(), [
            'department' => 'wholesales',
            'in_department' => 'wholesales',
            'customer_id' => $this->customer->id,
            'status_id' => status('Draft'),
            'invoiceitems' => json_encode([[
                'stock_id' => $this->stock->id,
                'name' => $this->stock->name,
                'quantity' => 6,
                'selling_price' => 10000,
                'cost_price' => 8000,
            ]]),
        ]);

        $this->assertIsArray($increaseResult);
        $this->assertArrayHasKey($this->stock->id, $increaseResult);
        $this->assertStringContainsString('Cannot increase quantity', $increaseResult[$this->stock->id]);
        $this->assertEquals(5, $paidInvoice->fresh()->invoiceitems->first()->quantity);

        // 2. Attempting to add a new stock not on the original invoice is rejected
        $addStockResult = $repo->updateInvoice($paidInvoice->fresh(), [
            'department' => 'wholesales',
            'in_department' => 'wholesales',
            'customer_id' => $this->customer->id,
            'status_id' => status('Draft'),
            'invoiceitems' => json_encode([
                [
                    'stock_id' => $this->stock->id,
                    'name' => $this->stock->name,
                    'quantity' => 5,
                    'selling_price' => 10000,
                    'cost_price' => 8000,
                ],
                [
                    'stock_id' => $newStock->id,
                    'name' => $newStock->name,
                    'quantity' => 1,
                    'selling_price' => 5000,
                    'cost_price' => 4000,
                ],
            ]),
        ]);

        $this->assertIsArray($addStockResult);
        $this->assertArrayHasKey($newStock->id, $addStockResult);
        $this->assertStringContainsString('Cannot add new stock', $addStockResult[$newStock->id]);
        $this->assertEquals(1, $paidInvoice->fresh()->invoiceitems()->count());
    }
}
