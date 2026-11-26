<?php

namespace Tests\Feature;

use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Invoiceitem;
use App\Models\Invoiceitembatch;
use App\Models\InvoiceReturn;
use App\Models\Payment;
use App\Models\Paymentmethoditem;
use App\Models\Stock;
use App\Models\Stockbatch;
use App\Models\User;
use App\Repositories\InvoiceRepository;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InvoiceReturnRefundTrackingTest extends TestCase
{
    protected User $cashierA;
    protected User $cashierB;
    protected Customer $customer;
    protected Stock $stockA;
    protected Stock $stockB;
    protected Stockbatch $batchA;
    protected Stockbatch $batchB;
    protected BankAccount $bankAccount1;
    protected BankAccount $bankAccount2;

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
            'database.connections.inv_ret_test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => false,
            ],
            'database.default' => 'inv_ret_test',
            'cache.default' => 'array',
            'app.sync_with_online' => 0,
        ]);

        DB::purge('inv_ret_test');
        Cache::flush();

        $this->createSchema();
        $this->seedFixtures();
    }

    private function createSchema(): void
    {
        Schema::create('statuses', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('label')->nullable();
            $t->timestamps();
        });

        Schema::create('departments', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('label')->nullable();
            $t->string('quantity_column')->nullable();
            $t->string('price_column')->nullable();
            $t->boolean('status')->default(true);
            $t->timestamps();
        });

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('password')->default('secret');
            $t->unsignedBigInteger('usergroup_id')->default(1);
            $t->unsignedBigInteger('department_id')->default(2);
            $t->boolean('status')->default(true);
            $t->timestamps();
        });

        Schema::create('customers', function (Blueprint $t) {
            $t->id();
            $t->string('firstname')->nullable();
            $t->string('lastname')->nullable();
            $t->decimal('credit_balance', 20, 5)->default(0);
            $t->decimal('deposit_balance', 20, 5)->default(0);
            $t->boolean('status')->default(true);
            $t->timestamps();
        });

        Schema::create('banks', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->boolean('status')->default(true);
            $t->timestamps();
        });

        Schema::create('paymentmethods', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->boolean('status')->default(true);
            $t->timestamps();
        });

        Schema::create('bank_accounts', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('bank_id')->nullable();
            $t->string('account_number')->nullable();
            $t->string('account_name')->nullable();
            $t->boolean('status')->default(true);
            $t->timestamps();
        });

        Schema::create('stocks', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->integer('box')->default(1);
            $t->integer('carton')->default(1);
            $t->decimal('cost_price', 20, 5)->default(0);
            $t->decimal('retail_cost_price', 20, 5)->default(0);
            $t->decimal('bulk_price', 20, 5)->default(0);
            $t->decimal('whole_price', 20, 5)->default(0);
            $t->decimal('retail_price', 20, 5)->default(0);
            $t->decimal('quantity', 20, 5)->default(0);
            $t->decimal('wholesales', 20, 5)->default(0);
            $t->decimal('bulksales', 20, 5)->default(0);
            $t->decimal('retail', 20, 5)->default(0);
            $t->decimal('retail_store', 20, 5)->default(0);
            $t->unsignedBigInteger('batched')->nullable();
            $t->boolean('status')->default(true);
            $t->timestamps();
        });

        Schema::create('stockbatches', function (Blueprint $t) {
            $t->id();
            $t->date('received_date')->nullable();
            $t->date('expiry_date')->nullable();
            $t->decimal('quantity', 20, 5)->default(0);
            $t->decimal('wholesales', 20, 5)->default(0);
            $t->decimal('bulksales', 20, 5)->default(0);
            $t->decimal('retail', 20, 5)->default(0);
            $t->decimal('retail_store', 20, 5)->default(0);
            $t->decimal('cost_price', 20, 5)->nullable();
            $t->decimal('retail_cost_price', 20, 5)->nullable();
            $t->unsignedBigInteger('stock_id');
            $t->string('batch_no')->nullable();
            $t->timestamps();
        });

        Schema::create('batchstocks', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('stock_id');
            $t->decimal('quantity', 20, 5)->default(0);
            $t->unsignedBigInteger('quantity_user_id')->nullable();
            $t->decimal('wholesales', 20, 5)->default(0);
            $t->unsignedBigInteger('wholesales_user_id')->nullable();
            $t->unsignedBigInteger('wholsale_user_id')->nullable();
            $t->decimal('bulksales', 20, 5)->default(0);
            $t->unsignedBigInteger('bulksales_user_id')->nullable();
            $t->decimal('retail', 20, 5)->default(0);
            $t->unsignedBigInteger('retail_user_id')->nullable();
            $t->decimal('retail_store', 20, 5)->default(0);
            $t->unsignedBigInteger('retail_store_user_id')->nullable();
            $t->timestamps();
        });

        Schema::create('promotion_items', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('stock_id')->nullable();
            $t->unsignedBigInteger('status_id')->nullable();
            $t->date('from_date')->nullable();
            $t->date('end_date')->nullable();
            $t->timestamps();
        });

        Schema::create('product_custom_prices', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('stock_id');
            $t->integer('min_qty')->default(0);
            $t->integer('max_qty')->default(0);
            $t->decimal('price', 20, 5)->default(0);
            $t->string('department')->nullable();
            $t->timestamps();
        });

        Schema::create('dependent_products', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('stock_id');
            $t->unsignedBigInteger('dependent_stock_id');
            $t->integer('parent')->default(1);
            $t->integer('child')->default(1);
            $t->timestamps();
        });

        Schema::create('purchase_limits', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->string('department')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('invoices', function (Blueprint $t) {
            $t->id();
            $t->string('invoice_number');
            $t->unsignedBigInteger('customer_id')->nullable();
            $t->unsignedBigInteger('payment_id')->nullable();
            $t->string('department')->nullable();
            $t->string('in_department')->nullable();
            $t->boolean('is_stock_exchange')->default(false);
            $t->string('invoice_type')->default('normal');
            $t->unsignedBigInteger('supplier_id')->nullable();
            $t->decimal('discount_amount', 20, 5)->default(0);
            $t->string('discount_type')->default('Fixed');
            $t->decimal('discount_value', 20, 5)->default(0);
            $t->decimal('membership_discount', 20, 5)->default(0);
            $t->decimal('membership_discount_value', 20, 5)->default(0);
            $t->unsignedBigInteger('status_id');
            $t->decimal('sub_total', 20, 5)->default(0);
            $t->decimal('total_amount_paid', 20, 5)->default(0);
            $t->decimal('exchange_amount_utilised', 20, 5)->default(0);
            $t->decimal('total_profit', 20, 5)->default(0);
            $t->decimal('total_cost', 20, 5)->default(0);
            $t->decimal('vat', 20, 5)->default(0);
            $t->decimal('vat_amount', 20, 5)->default(0);
            $t->unsignedBigInteger('created_by')->nullable();
            $t->unsignedBigInteger('last_updated_by')->nullable();
            $t->unsignedBigInteger('voided_by')->nullable();
            $t->date('invoice_date')->nullable();
            $t->time('sales_time')->nullable();
            $t->string('void_reason')->nullable();
            $t->date('date_voided')->nullable();
            $t->dateTime('void_time')->nullable();
            $t->boolean('retail_printed')->default(false);
            $t->timestamps();
        });

        Schema::create('invoiceitems', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('invoice_id');
            $t->unsignedBigInteger('stock_id');
            $t->unsignedInteger('quantity');
            $t->unsignedBigInteger('customer_id')->nullable();
            $t->unsignedBigInteger('status_id')->nullable();
            $t->unsignedBigInteger('added_by')->nullable();
            $t->unsignedBigInteger('discount_added_by')->nullable();
            $t->date('invoice_date')->nullable();
            $t->time('sales_time')->nullable();
            $t->decimal('cost_price', 20, 5)->default(0);
            $t->decimal('selling_price', 20, 5)->default(0);
            $t->decimal('profit', 20, 5)->default(0);
            $t->string('department')->nullable();
            $t->string('discount_type')->nullable();
            $t->decimal('discount_value', 20, 5)->default(0);
            $t->decimal('discount_amount', 20, 5)->default(0);
            $t->text('selectedOptions')->nullable();
            $t->boolean('isDependent')->default(false);
            $t->unsignedBigInteger('parent_stock_id')->nullable();
            $t->timestamps();
        });

        Schema::create('invoiceitembatches', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('invoice_id');
            $t->unsignedBigInteger('invoiceitem_id')->nullable();
            $t->unsignedBigInteger('stock_id');
            $t->unsignedBigInteger('stockbatch_id');
            $t->decimal('cost_price', 20, 5)->default(0);
            $t->decimal('selling_price', 20, 5)->default(0);
            $t->string('department')->nullable();
            $t->string('batch_no')->nullable();
            $t->integer('quantity')->default(0);
            $t->timestamps();
        });

        Schema::create('invoiceprinthistories', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('invoice_id');
            $t->timestamps();
        });

        Schema::create('invoiceactivitylogs', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('invoice_id')->nullable();
            $t->string('invoice_number')->nullable();
            $t->text('activity')->nullable();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->date('activity_date')->nullable();
            $t->time('activity_time')->nullable();
            $t->timestamps();
        });

        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedBigInteger('customer_id')->nullable();
            $t->string('invoice_number')->nullable();
            $t->unsignedBigInteger('invoice_id')->nullable();
            $t->string('invoice_type')->nullable();
            $t->string('department')->nullable();
            $t->decimal('subtotal', 20, 5)->default(0);
            $t->decimal('total_paid', 20, 5)->default(0);
            $t->time('payment_time')->nullable();
            $t->date('payment_date')->nullable();
            $t->timestamps();
        });

        Schema::create('paymentmethoditems', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('payment_id')->nullable();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedBigInteger('customer_id')->nullable();
            $t->unsignedBigInteger('paymentmethod_id')->nullable();
            $t->unsignedBigInteger('invoice_id')->nullable();
            $t->string('department')->nullable();
            $t->string('invoice_type')->nullable();
            $t->date('payment_date')->nullable();
            $t->decimal('amount', 20, 5)->default(0);
            $t->text('payment_info')->nullable();
            $t->unsignedBigInteger('bank_account_id')->nullable();
            $t->timestamps();
        });

        Schema::create('creditpaymentlogs', function (Blueprint $t) {
            $t->id();
            $t->string('credit_number')->nullable();
            $t->unsignedBigInteger('payment_id')->nullable();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedBigInteger('paymentmethod_id')->nullable();
            $t->unsignedBigInteger('customer_id')->nullable();
            $t->unsignedBigInteger('paymentmethoditem_id')->nullable();
            $t->string('invoicelog_type')->nullable();
            $t->unsignedBigInteger('invoicelog_id')->nullable();
            $t->unsignedBigInteger('credit_number_id')->nullable();
            $t->decimal('amount', 20, 5)->default(0);
            $t->date('payment_date')->nullable();
            $t->timestamps();
        });

        $descMigration = include __DIR__ . '/../../database/migrations/2026_11_25_180000_add_description_to_creditpaymentlogs_table.php';
        $descMigration->up();

        Schema::create('supplier_credit_payment_histories', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('supplier_id')->nullable();
            $t->unsignedBigInteger('invoice_id')->nullable();
            $t->string('type')->nullable();
            $t->string('status')->nullable();
            $t->timestamp('reversed_at')->nullable();
            $t->decimal('amount', 20, 5)->default(0);
            $t->timestamps();
        });

        // Run the formal migration for invoice_returns
        $migration = include __DIR__ . '/../../database/migrations/2026_11_25_150000_create_invoice_returns_table.php';
        $migration->up();
    }

    private function seedFixtures(): void
    {
        foreach (['Draft', 'Paid', 'Complete', 'Dispatched', 'Deleted', 'Cancelled', 'Declined'] as $name) {
            DB::table('statuses')->insert(['name' => $name, 'label' => $name]);
        }

        DB::table('departments')->insert([
            ['id' => 2, 'name' => 'Wholesales', 'label' => 'Wholesales', 'quantity_column' => 'wholesales', 'price_column' => 'whole_price', 'status' => 1],
        ]);

        DB::table('paymentmethods')->insert([
            ['id' => 1, 'name' => 'CASH', 'status' => 1],
            ['id' => 2, 'name' => 'POS', 'status' => 1],
            ['id' => 3, 'name' => 'TRANSFER', 'status' => 1],
        ]);

        $this->cashierA = User::forceCreate(['name' => 'Cashier Alice', 'email' => 'alice@test.local', 'usergroup_id' => 1, 'department_id' => 2, 'status' => 1]);
        $this->cashierB = User::forceCreate(['name' => 'Cashier Bob', 'email' => 'bob@test.local', 'usergroup_id' => 1, 'department_id' => 2, 'status' => 1]);
        $this->customer = Customer::forceCreate(['firstname' => 'Chidi', 'lastname' => 'Okafor', 'status' => 1]);

        $bank1 = Bank::forceCreate(['name' => 'Zenith Bank', 'status' => 1]);
        $bank2 = Bank::forceCreate(['name' => 'GTBank', 'status' => 1]);

        $this->bankAccount1 = BankAccount::forceCreate([
            'bank_id' => $bank1->id,
            'account_name' => 'PS General Drug Centre 1',
            'account_number' => '1011112222',
            'status' => 1,
        ]);
        $this->bankAccount2 = BankAccount::forceCreate([
            'bank_id' => $bank2->id,
            'account_name' => 'PS General Drug Centre 2',
            'account_number' => '0123456789',
            'status' => 1,
        ]);

        $this->stockA = Stock::forceCreate([
            'name' => 'Augmentin 625mg',
            'box' => 1,
            'carton' => 10,
            'cost_price' => 8000,
            'whole_price' => 10000,
            'wholesales' => 50,
            'status' => 1,
        ]);
        $this->batchA = Stockbatch::forceCreate([
            'stock_id' => $this->stockA->id,
            'wholesales' => 50,
            'cost_price' => 8000,
            'batch_no' => 'AUG-01',
            'expiry_date' => '2028-12-31',
        ]);

        $this->stockB = Stock::forceCreate([
            'name' => 'Vitamin C 1000mg',
            'box' => 1,
            'carton' => 20,
            'cost_price' => 2000,
            'whole_price' => 3000,
            'wholesales' => 100,
            'status' => 1,
        ]);
        $this->batchB = Stockbatch::forceCreate([
            'stock_id' => $this->stockB->id,
            'wholesales' => 100,
            'cost_price' => 2000,
            'batch_no' => 'VIT-01',
            'expiry_date' => '2028-12-31',
        ]);
    }

    private function createPaidInvoice(string $invoiceNumber, int $qtyA = 5, int $qtyB = 2, ?int $initialStatusId = null): Invoice
    {
        $subTotal = ($qtyA * 10000) + ($qtyB * 3000);
        $statusId = $initialStatusId ?? (int) status('Paid');
        $payment = Payment::forceCreate([
            'user_id' => $this->cashierA->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => $invoiceNumber,
            'invoice_type' => Invoice::class,
            'department' => 'wholesales',
            'subtotal' => $subTotal,
            'total_paid' => $subTotal,
            'payment_date' => todaysDate(),
        ]);

        $invoice = Invoice::forceCreate([
            'invoice_number' => $invoiceNumber,
            'customer_id' => $this->customer->id,
            'payment_id' => $payment->id,
            'department' => 'wholesales',
            'in_department' => 'wholesales',
            'status_id' => $statusId,
            'sub_total' => $subTotal,
            'total_amount_paid' => $subTotal,
            'total_cost' => ($qtyA * 8000) + ($qtyB * 2000),
            'total_profit' => ($qtyA * 2000) + ($qtyB * 1000),
            'invoice_date' => todaysDate(),
            'sales_time' => '10:00:00',
            'created_by' => $this->cashierA->id,
            'last_updated_by' => $this->cashierA->id,
        ]);

        $payment->update(['invoice_id' => $invoice->id]);

        Paymentmethoditem::forceCreate([
            'payment_id' => $payment->id,
            'user_id' => $this->cashierA->id,
            'customer_id' => $this->customer->id,
            'paymentmethod_id' => 3,
            'invoice_type' => Invoice::class,
            'invoice_id' => $invoice->id,
            'department' => 'wholesales',
            'payment_date' => todaysDate(),
            'amount' => $subTotal,
            'bank_account_id' => $this->bankAccount2->id,
            'payment_info' => json_encode(['bank_account_id' => $this->bankAccount2->id]),
        ]);

        $itemA = Invoiceitem::forceCreate([
            'invoice_id' => $invoice->id,
            'stock_id' => $this->stockA->id,
            'quantity' => $qtyA,
            'customer_id' => $this->customer->id,
            'status_id' => status('Paid'),
            'added_by' => $this->cashierA->id,
            'invoice_date' => todaysDate(),
            'sales_time' => '10:00:00',
            'cost_price' => 8000,
            'selling_price' => 10000,
            'profit' => 2000,
            'department' => 'wholesales',
        ]);
        Invoiceitembatch::forceCreate([
            'invoice_id' => $invoice->id,
            'invoiceitem_id' => $itemA->id,
            'stock_id' => $this->stockA->id,
            'stockbatch_id' => $this->batchA->id,
            'cost_price' => 8000,
            'selling_price' => 10000,
            'department' => 'wholesales',
            'quantity' => $qtyA,
        ]);

        $itemB = Invoiceitem::forceCreate([
            'invoice_id' => $invoice->id,
            'stock_id' => $this->stockB->id,
            'quantity' => $qtyB,
            'customer_id' => $this->customer->id,
            'status_id' => status('Paid'),
            'added_by' => $this->cashierA->id,
            'invoice_date' => todaysDate(),
            'sales_time' => '10:00:00',
            'cost_price' => 2000,
            'selling_price' => 3000,
            'profit' => 1000,
            'department' => 'wholesales',
        ]);
        Invoiceitembatch::forceCreate([
            'invoice_id' => $invoice->id,
            'invoiceitem_id' => $itemB->id,
            'stock_id' => $this->stockB->id,
            'stockbatch_id' => $this->batchB->id,
            'cost_price' => 2000,
            'selling_price' => 3000,
            'department' => 'wholesales',
            'quantity' => $qtyB,
        ]);

        return $invoice->fresh(['invoiceitems', 'invoiceitembatches']);
    }

    public function test_backend_validation_enforces_reason_other_explanation_refund_choice_and_bank_account(): void
    {
        Bus::fake();
        $this->actingAs($this->cashierA);
        $invoice = $this->createPaidInvoice('INV-VAL-01', 5, 2); // Original = 56,000
        $repo = new InvoiceRepository();

        $basePayload = [
            'department' => 'wholesales',
            'in_department' => 'wholesales',
            'customer_id' => $this->customer->id,
            'status_id' => status('Draft'),
            'discount_type' => 'None',
            'discount_value' => 0,
            'discount_amount' => 0,
            'invoiceitems' => json_encode([
                ['stock_id' => $this->stockA->id, 'name' => $this->stockA->name, 'quantity' => 3, 'selling_price' => 10000, 'cost_price' => 8000, 'discount_type' => 'Fixed', 'discount_value' => 0, 'discount_amount' => 0],
                ['stock_id' => $this->stockB->id, 'name' => $this->stockB->name, 'quantity' => 2, 'selling_price' => 3000, 'cost_price' => 2000, 'discount_type' => 'Fixed', 'discount_value' => 0, 'discount_amount' => 0],
            ]),
        ];

        // 1. Missing return_reason
        $res1 = $repo->updateInvoice($invoice->fresh(), $basePayload);
        $this->assertIsArray($res1);
        $this->assertArrayHasKey('return_reason', $res1);

        // 2. Other reason without explanation
        $res2 = $repo->updateInvoice($invoice->fresh(), array_merge($basePayload, [
            'return_info' => [
                'return_reason' => InvoiceReturn::REASON_OTHER,
                'other_reason_explanation' => '   ',
                'will_refund' => 'no',
            ],
        ]));
        $this->assertIsArray($res2);
        $this->assertArrayHasKey('other_reason_explanation', $res2);

        // 3. Missing will_refund
        $res3 = $repo->updateInvoice($invoice->fresh(), array_merge($basePayload, [
            'return_info' => [
                'return_reason' => InvoiceReturn::REASON_CUSTOMER_RETURNED,
                'will_refund' => '',
            ],
        ]));
        $this->assertIsArray($res3);
        $this->assertArrayHasKey('will_refund', $res3);

        // 4. will_refund = yes without paymentmethod_id (or unsupported POS id=2)
        $res4 = $repo->updateInvoice($invoice->fresh(), array_merge($basePayload, [
            'return_info' => [
                'return_reason' => InvoiceReturn::REASON_CUSTOMER_RETURNED,
                'will_refund' => 'yes',
                'paymentmethod_id' => 2, // POS is not allowed for customer refunds
            ],
        ]));
        $this->assertIsArray($res4);
        $this->assertArrayHasKey('refund_payment_method', $res4);

        // 5. Transfer (paymentmethod_id = 3) without bank_account_id
        $res5 = $repo->updateInvoice($invoice->fresh(), array_merge($basePayload, [
            'return_info' => [
                'return_reason' => InvoiceReturn::REASON_CUSTOMER_RETURNED,
                'will_refund' => 'yes',
                'paymentmethod_id' => 3,
                'bank_account_id' => null,
            ],
        ]));
        $this->assertIsArray($res5);
        $this->assertArrayHasKey('bank_account_id', $res5);

        $this->assertEquals(0, InvoiceReturn::count());
    }

    public function test_return_difference_respects_existing_invoice_discount_when_quantity_is_reduced(): void
    {
        Bus::fake();
        $this->actingAs($this->cashierA);
        $invoice = $this->createPaidInvoice('INV-RET-DISC', 5, 2); // sub_total 56,000
        $invoice->update(['discount_type' => 'Fixed', 'discount_value' => 1000, 'discount_amount' => 1000, 'total_amount_paid' => 55000]);
        $invoice = $invoice->fresh(['invoiceitems', 'invoiceitembatches']);
        $this->assertEquals(55000.0, $invoice->calculateOriginalTotal());

        // The return screen must receive the stored discount value, otherwise it recalculates without discount
        $component = new \App\Livewire\InvoiceAndSales\InvoiceFormComponent();
        $data = InvoiceRepository::invoice($invoice, $component);
        $this->assertEquals(1000.0, (float) $data['discount_value']);
        $this->assertSame('Fixed', $data['discount_type']);

        $updated = (new InvoiceRepository())->updateInvoice($invoice, [
            'department' => 'wholesales',
            'in_department' => 'wholesales',
            'customer_id' => $this->customer->id,
            'status_id' => status('Draft'),
            'discount_type' => $data['discount_type'],
            'discount_value' => $data['discount_value'],
            'discount_amount' => $data['discount_amount'],
            'invoiceitems' => json_encode([
                ['stock_id' => $this->stockA->id, 'name' => $this->stockA->name, 'quantity' => 3, 'selling_price' => 10000, 'cost_price' => 8000, 'discount_type' => 'Fixed', 'discount_value' => 0, 'discount_amount' => 0],
                ['stock_id' => $this->stockB->id, 'name' => $this->stockB->name, 'quantity' => 2, 'selling_price' => 3000, 'cost_price' => 2000, 'discount_type' => 'Fixed', 'discount_value' => 0, 'discount_amount' => 0],
            ]),
            'return_info' => [
                'return_reason' => InvoiceReturn::REASON_CUSTOMER_RETURNED,
                'will_refund' => 'no',
                'idempotency_key' => 'idem-disc-001',
            ],
        ]);
        $this->assertInstanceOf(Invoice::class, $updated);

        $ret = InvoiceReturn::where('invoice_id', $invoice->id)->firstOrFail();
        $this->assertEquals(55000.0, $ret->original_invoice_amount);
        $this->assertEquals(35000.0, $ret->current_invoice_amount); // 36,000 - 1,000 discount
        $this->assertEquals(20000.0, $ret->return_difference);
    }

    public function test_return_keeps_original_discount_even_when_client_payload_has_none_and_wrong_department(): void
    {
        Bus::fake();
        $this->actingAs($this->cashierA);
        $invoice = $this->createPaidInvoice('INV-RET-DISC2', 5, 2); // sub_total 56,000
        // Percentage discount of 10% => 5,600 discount => original 50,400
        $invoice->update(['discount_type' => 'Percentage', 'discount_value' => 10, 'discount_amount' => 5600, 'total_amount_paid' => 50400]);
        $invoice = $invoice->fresh(['invoiceitems', 'invoiceitembatches']);
        $this->assertEquals(50400.0, $invoice->calculateOriginalTotal());

        $updated = (new InvoiceRepository())->updateInvoice($invoice, [
            'department' => 'wholesales',
            'in_department' => 'retail', // wrong department sent by the client must not matter
            'customer_id' => $this->customer->id,
            'status_id' => status('Draft'),
            'discount_type' => 'None',
            'discount_value' => 0,
            'discount_amount' => 0,
            'invoiceitems' => json_encode([
                ['stock_id' => $this->stockA->id, 'name' => $this->stockA->name, 'quantity' => 3, 'selling_price' => 10000, 'cost_price' => 8000, 'discount_type' => 'Fixed', 'discount_value' => 0, 'discount_amount' => 0],
                ['stock_id' => $this->stockB->id, 'name' => $this->stockB->name, 'quantity' => 2, 'selling_price' => 3000, 'cost_price' => 2000, 'discount_type' => 'Fixed', 'discount_value' => 0, 'discount_amount' => 0],
            ]),
            'return_info' => [
                'return_reason' => InvoiceReturn::REASON_CUSTOMER_RETURNED,
                'will_refund' => 'no',
                'idempotency_key' => 'idem-disc-002',
            ],
        ]);
        $this->assertInstanceOf(Invoice::class, $updated);
        $this->assertSame('wholesales', $updated->in_department);

        $ret = InvoiceReturn::where('invoice_id', $invoice->id)->firstOrFail();
        $this->assertEquals(50400.0, $ret->original_invoice_amount);
        $this->assertEquals(32400.0, $ret->current_invoice_amount); // 36,000 less 10%
        $this->assertEquals(18000.0, $ret->return_difference);
    }

    public function test_return_ignores_custom_price_and_preserves_original_invoice_selling_price(): void
    {
        Bus::fake();
        $this->actingAs($this->cashierA);

        // Configure a custom bundle price on stockA (carton = 10, min_qty = 1 carton => 9,000 instead of whole_price 10,000)
        DB::table('product_custom_prices')->insert([
            'stock_id' => $this->stockA->id,
            'min_qty' => 1,
            'max_qty' => 10,
            'price' => 9000,
            'department' => 'wholesales',
        ]);

        // Invoice originally sold 10 units of stockA at the bundle selling_price of 9,000 (sub_total = 90,000)
        $invoice = $this->createPaidInvoice('INV-RET-CUSTOM-PRICE', 10, 0);
        $invoice->update(['sub_total' => 90000, 'total_amount_paid' => 90000]);
        $invoice->invoiceitems()->where('stock_id', $this->stockA->id)->update(['selling_price' => 9000, 'profit' => 1000]);
        $invoice->invoiceitembatches()->where('stock_id', $this->stockA->id)->update(['selling_price' => 9000]);
        $invoice->invoiceitems()->where('stock_id', $this->stockB->id)->delete();
        $invoice->invoiceitembatches()->where('stock_id', $this->stockB->id)->delete();
        $invoice = $invoice->fresh(['invoiceitems', 'invoiceitembatches']);

        // Customer returns 5 units (remaining = 5 units, which is < 1 carton).
        // Even though 5 units is below the custom bundle threshold (where normal price would be 10,000),
        // the return MUST ignore custom/tier pricing and keep the original selling_price of 9,000!
        $updated = (new InvoiceRepository())->updateInvoice($invoice, [
            'department' => 'wholesales',
            'in_department' => 'wholesales',
            'customer_id' => $this->customer->id,
            'status_id' => status('Paid'),
            'discount_type' => 'None',
            'discount_value' => 0,
            'discount_amount' => 0,
            'invoiceitems' => json_encode([
                ['stock_id' => $this->stockA->id, 'name' => $this->stockA->name, 'quantity' => 5, 'selling_price' => 9000, 'cost_price' => 8000, 'discount_type' => 'Fixed', 'discount_value' => 0, 'discount_amount' => 0],
            ]),
            'return_info' => [
                'return_reason' => InvoiceReturn::REASON_CUSTOMER_RETURNED,
                'will_refund' => 'yes',
                'paymentmethod_id' => 1,
                'idempotency_key' => 'idem-custom-price-001',
            ],
        ]);

        $this->assertInstanceOf(Invoice::class, $updated);
        $this->assertEquals(45000.0, (float) $updated->sub_total);
        $this->assertEquals(45000.0, (float) $updated->total_amount_paid);
        $this->assertEquals(9000.0, (float) $updated->invoiceitems()->first()->selling_price);

        $ret = InvoiceReturn::where('invoice_id', $invoice->id)->firstOrFail();
        $this->assertEquals(90000.0, $ret->original_invoice_amount);
        $this->assertEquals(45000.0, $ret->current_invoice_amount);
        $this->assertEquals(45000.0, $ret->return_difference);
        $this->assertEquals(45000.0, $ret->refund_amount);
    }

    public function test_return_preserves_item_level_discounts_on_invoiceitems_and_calculates_exact_difference(): void
    {
        Bus::fake();
        $this->actingAs($this->cashierA);

        // Invoice with 5 units of stockA (selling_price = 10,000, item discount_amount = 500/unit => net 9,500/unit => 47,500)
        // and 2 units of stockB (selling_price = 3,000, item discount_amount = 200/unit => net 2,800/unit => 5,600)
        // Original sub_total = 47,500 + 5,600 = 53,100
        $invoice = $this->createPaidInvoice('INV-RET-ITEM-DISC', 5, 2);
        $invoice->invoiceitems()->where('stock_id', $this->stockA->id)->update([
            'discount_type' => 'Fixed',
            'discount_value' => 500,
            'discount_amount' => 500,
            'discount_added_by' => $this->cashierA->id,
            'profit' => 1500,
        ]);
        $invoice->invoiceitems()->where('stock_id', $this->stockB->id)->update([
            'discount_type' => 'Fixed',
            'discount_value' => 200,
            'discount_amount' => 200,
            'discount_added_by' => $this->cashierA->id,
            'profit' => 800,
        ]);
        $invoice->update(['sub_total' => 53100, 'total_amount_paid' => 53100]);
        $invoice = $invoice->fresh(['invoiceitems', 'invoiceitembatches']);

        // Customer returns 2 units of stockA (3 remaining * 9,500 = 28,500; 2 units of stockB * 2,800 = 5,600 => new total = 34,100)
        // Return difference = 2 * 9,500 = 19,000
        $updated = (new InvoiceRepository())->updateInvoice($invoice, [
            'department' => 'wholesales',
            'in_department' => 'wholesales',
            'customer_id' => $this->customer->id,
            'status_id' => status('Paid'),
            'discount_type' => 'None',
            'discount_value' => 0,
            'discount_amount' => 0,
            'invoiceitems' => json_encode([
                ['stock_id' => $this->stockA->id, 'name' => $this->stockA->name, 'quantity' => 3, 'selling_price' => 10000, 'cost_price' => 8000, 'discount_type' => 'Fixed', 'discount_value' => 500, 'discount_amount' => 500],
                ['stock_id' => $this->stockB->id, 'name' => $this->stockB->name, 'quantity' => 2, 'selling_price' => 3000, 'cost_price' => 2000, 'discount_type' => 'Fixed', 'discount_value' => 200, 'discount_amount' => 200],
            ]),
            'return_info' => [
                'return_reason' => InvoiceReturn::REASON_CUSTOMER_RETURNED,
                'will_refund' => 'yes',
                'paymentmethod_id' => 1,
                'idempotency_key' => 'idem-item-disc-001',
            ],
        ]);

        $this->assertInstanceOf(Invoice::class, $updated);
        $this->assertEquals(34100.0, (float) $updated->sub_total);
        $this->assertEquals(34100.0, (float) $updated->total_amount_paid);

        $updatedItemA = $updated->invoiceitems()->where('stock_id', $this->stockA->id)->firstOrFail();
        $this->assertEquals(500.0, (float) $updatedItemA->discount_amount);
        $this->assertEquals(1500.0, (float) $updatedItemA->profit);

        $ret = InvoiceReturn::where('invoice_id', $invoice->id)->firstOrFail();
        $this->assertEquals(53100.0, $ret->original_invoice_amount);
        $this->assertEquals(34100.0, $ret->current_invoice_amount);
        $this->assertEquals(19000.0, $ret->return_difference);
        $this->assertEquals(19000.0, $ret->refund_amount);
    }

    public function test_return_with_delete_invoice_reason_returns_all_stock_deletes_payment_and_marks_invoice_deleted(): void
    {
        Bus::fake();
        $this->actingAs($this->cashierA);

        $initialStockAQty = (int) $this->stockA->fresh()->wholesales;
        $initialStockBQty = (int) $this->stockB->fresh()->wholesales;

        // Create a paid invoice for 56,000 (5 of stockA + 2 of stockB)
        $invoice = $this->createPaidInvoice('INV-RET-DELETE-01', 5, 2, (int) status('Paid'));
        $origPaymentId = $invoice->payment_id;

        // Cashier selects "Return invoice in order to delete it." without even reducing item quantities on the form
        $updated = (new InvoiceRepository())->updateInvoice($invoice, [
            'department' => 'wholesales',
            'in_department' => 'wholesales',
            'customer_id' => $this->customer->id,
            'status_id' => status('Paid'),
            'discount_type' => 'None',
            'discount_value' => 0,
            'discount_amount' => 0,
            'invoiceitems' => json_encode([
                ['stock_id' => $this->stockA->id, 'name' => $this->stockA->name, 'quantity' => 5, 'selling_price' => 10000, 'cost_price' => 8000, 'discount_type' => 'Fixed', 'discount_value' => 0, 'discount_amount' => 0],
                ['stock_id' => $this->stockB->id, 'name' => $this->stockB->name, 'quantity' => 2, 'selling_price' => 3000, 'cost_price' => 2000, 'discount_type' => 'Fixed', 'discount_value' => 0, 'discount_amount' => 0],
            ]),
            'return_info' => [
                'return_reason' => InvoiceReturn::REASON_DELETE_INVOICE,
                'will_refund' => 'yes',
                'paymentmethod_id' => 1, // CASH
                'idempotency_key' => 'idem-delete-inv-001',
            ],
        ]);

        $this->assertInstanceOf(Invoice::class, $updated);
        $this->assertSame((int) status('Deleted'), (int) $updated->status_id);
        $this->assertNull($updated->payment_id);
        $this->assertNull(Payment::find($origPaymentId));

        // All 5 units of stockA and 2 units of stockB returned to stock
        $this->assertSame($initialStockAQty + 5, (int) $this->stockA->fresh()->wholesales);
        $this->assertSame($initialStockBQty + 2, (int) $this->stockB->fresh()->wholesales);

        $ret = InvoiceReturn::where('invoice_id', $invoice->id)->firstOrFail();
        $this->assertSame(InvoiceReturn::REASON_DELETE_INVOICE, $ret->return_reason);
        $this->assertEquals(56000.0, $ret->original_invoice_amount);
        $this->assertEquals(0.0, $ret->current_invoice_amount);
        $this->assertEquals(56000.0, $ret->return_difference);
        $this->assertTrue($ret->is_customer_refunded);
        $this->assertEquals(56000.0, $ret->refund_amount);
        $this->assertSame(1, $ret->paymentmethod_id);
    }

    public function test_cash_transfer_and_no_refund_persistence_idempotency_and_reconciliation_queries(): void
    {
        Bus::fake();
        $repo = new InvoiceRepository();

        // CASE 1: Cashier A processes a CASH refund (Original 56,000 -> New 36,000 => Return Difference 20,000)
        $this->actingAs($this->cashierA);
        $inv1 = $this->createPaidInvoice('INV-RET-CASH', 5, 2, (int) status('Paid'));
        $origPaymentId1 = $inv1->payment_id;

        $payload1 = [
            'department' => 'wholesales',
            'in_department' => 'wholesales',
            'customer_id' => $this->customer->id,
            'status_id' => status('Draft'),
            'discount_type' => 'None',
            'discount_value' => 0,
            'discount_amount' => 0,
            'invoiceitems' => json_encode([
                ['stock_id' => $this->stockA->id, 'name' => $this->stockA->name, 'quantity' => 3, 'selling_price' => 10000, 'cost_price' => 8000, 'discount_type' => 'Fixed', 'discount_value' => 0, 'discount_amount' => 0],
                ['stock_id' => $this->stockB->id, 'name' => $this->stockB->name, 'quantity' => 2, 'selling_price' => 3000, 'cost_price' => 2000, 'discount_type' => 'Fixed', 'discount_value' => 0, 'discount_amount' => 0],
            ]),
            'return_info' => [
                'return_reason' => InvoiceReturn::REASON_CUSTOMER_RETURNED,
                'will_refund' => 'yes',
                'paymentmethod_id' => 1, // CASH in paymentmethods table
                'idempotency_key' => 'idem-cash-001',
            ],
        ];

        $updated1 = $repo->updateInvoice($inv1, $payload1);
        $this->assertInstanceOf(Invoice::class, $updated1);

        // Invoice status MUST remain the status before the invoice was returned ('Paid', NOT 'Draft')
        $this->assertSame((int) status('Paid'), (int) $updated1->status_id);
        $this->assertEquals(36000.0, (float) $updated1->total_amount_paid);
        // Payment is automatically re-added with original payment details and new invoice amount after return
        $this->assertNotNull($updated1->payment_id);
        $this->assertNotSame($origPaymentId1, $updated1->payment_id);
        $this->assertNull(Payment::find($origPaymentId1));
        $newPayment1 = Payment::with('paymentmethoditems')->findOrFail($updated1->payment_id);
        $this->assertEquals(36000.0, (float) $newPayment1->subtotal);
        $this->assertEquals(36000.0, (float) $newPayment1->total_paid);
        $this->assertSame($inv1->invoice_number, $newPayment1->invoice_number);
        $this->assertSame($this->customer->id, $newPayment1->customer_id);
        $this->assertCount(1, $newPayment1->paymentmethoditems);
        $this->assertSame(3, (int) $newPayment1->paymentmethoditems->first()->paymentmethod_id);
        $this->assertSame($this->bankAccount2->id, (int) $newPayment1->paymentmethoditems->first()->bank_account_id);
        $this->assertEquals(36000.0, (float) $newPayment1->paymentmethoditems->first()->amount);

        // Duplicate submission with same idempotency_key does not create a second refund record
        $dup1 = $repo->updateInvoice($inv1->fresh(), $payload1);
        $this->assertEquals(1, InvoiceReturn::where('idempotency_key', 'idem-cash-001')->count());

        $ret1 = InvoiceReturn::with('paymentmethod')->where('invoice_id', $inv1->id)->firstOrFail();
        $this->assertEquals(56000.0, $ret1->original_invoice_amount);
        $this->assertEquals(36000.0, $ret1->current_invoice_amount);
        $this->assertEquals(20000.0, $ret1->return_difference);
        $this->assertSame(InvoiceReturn::REASON_CUSTOMER_RETURNED, $ret1->return_reason);
        $this->assertTrue($ret1->is_customer_refunded);
        $this->assertSame('Refunded', $ret1->refund_status);
        $this->assertEquals(20000.0, $ret1->refund_amount);
        $this->assertSame(1, $ret1->paymentmethod_id);
        $this->assertSame('CASH', $ret1->paymentmethod->name);
        $this->assertSame('CASH', $ret1->refund_payment_method);
        $this->assertNull($ret1->bank_account_id);
        $this->assertSame($this->cashierA->id, $ret1->processed_by);

        // CASE 2: Cashier B processes a TRANSFER refund via BankAccount 1 with Other reason (Original 56,000 -> New 26,000 => Diff 30,000) on a 'Complete' invoice
        $this->actingAs($this->cashierB);
        $inv2 = $this->createPaidInvoice('INV-RET-TRANS-1', 5, 2, (int) status('Complete'));

        $updated2 = $repo->updateInvoice($inv2, [
            'department' => 'wholesales',
            'in_department' => 'wholesales',
            'customer_id' => $this->customer->id,
            'status_id' => status('Draft'),
            'discount_type' => 'None',
            'discount_value' => 0,
            'discount_amount' => 0,
            'invoiceitems' => json_encode([
                ['stock_id' => $this->stockA->id, 'name' => $this->stockA->name, 'quantity' => 2, 'selling_price' => 10000, 'cost_price' => 8000, 'discount_type' => 'Fixed', 'discount_value' => 0, 'discount_amount' => 0],
                ['stock_id' => $this->stockB->id, 'name' => $this->stockB->name, 'quantity' => 2, 'selling_price' => 3000, 'cost_price' => 2000, 'discount_type' => 'Fixed', 'discount_value' => 0, 'discount_amount' => 0],
            ]),
            'return_info' => [
                'return_reason' => InvoiceReturn::REASON_OTHER,
                'other_reason_explanation' => 'Damaged outer carton on 3 packs',
                'will_refund' => 'yes',
                'paymentmethod_id' => 3, // TRANSFER in paymentmethods table
                'bank_account_id' => $this->bankAccount1->id,
                'idempotency_key' => 'idem-trans-002',
            ],
        ]);
        $this->assertInstanceOf(Invoice::class, $updated2);
        $this->assertSame((int) status('Complete'), (int) $updated2->status_id);
        $this->assertEquals(26000.0, (float) $updated2->total_amount_paid);
        $newPayment2 = Payment::with('paymentmethoditems')->findOrFail($updated2->payment_id);
        $this->assertEquals(26000.0, (float) $newPayment2->total_paid);
        $this->assertEquals(26000.0, (float) $newPayment2->paymentmethoditems->first()->amount);

        $ret2 = InvoiceReturn::with('paymentmethod')->where('invoice_id', $inv2->id)->firstOrFail();
        $this->assertEquals(30000.0, $ret2->return_difference);
        $this->assertEquals(30000.0, $ret2->refund_amount);
        $this->assertSame(3, $ret2->paymentmethod_id);
        $this->assertSame('TRANSFER', $ret2->paymentmethod->name);
        $this->assertSame('TRANSFER', $ret2->refund_payment_method);
        $this->assertSame($this->bankAccount1->id, $ret2->bank_account_id);
        $this->assertSame('Damaged outer carton on 3 packs', $ret2->other_reason_explanation);
        $this->assertSame($this->cashierB->id, $ret2->processed_by);

        // CASE 3: Cashier A processes a non-refunded return (Cashier mistake, will_refund = no) on a 'Dispatched' invoice
        $this->actingAs($this->cashierA);
        $inv3 = $this->createPaidInvoice('INV-RET-NOREFUND', 5, 2, (int) status('Dispatched'));

        $updated3 = $repo->updateInvoice($inv3, [
            'department' => 'wholesales',
            'in_department' => 'wholesales',
            'customer_id' => $this->customer->id,
            'status_id' => status('Draft'),
            'discount_type' => 'None',
            'discount_value' => 0,
            'discount_amount' => 0,
            'invoiceitems' => json_encode([
                ['stock_id' => $this->stockA->id, 'name' => $this->stockA->name, 'quantity' => 4, 'selling_price' => 10000, 'cost_price' => 8000, 'discount_type' => 'Fixed', 'discount_value' => 0, 'discount_amount' => 0],
                ['stock_id' => $this->stockB->id, 'name' => $this->stockB->name, 'quantity' => 2, 'selling_price' => 3000, 'cost_price' => 2000, 'discount_type' => 'Fixed', 'discount_value' => 0, 'discount_amount' => 0],
            ]),
            'return_info' => [
                'return_reason' => InvoiceReturn::REASON_CASHIER_MISTAKE,
                'will_refund' => 'no',
                'idempotency_key' => 'idem-noref-003',
            ],
        ]);
        $this->assertInstanceOf(Invoice::class, $updated3);
        $this->assertSame((int) status('Dispatched'), (int) $updated3->status_id);
        $this->assertEquals(46000.0, (float) $updated3->total_amount_paid);
        $newPayment3 = Payment::with('paymentmethoditems')->findOrFail($updated3->payment_id);
        $this->assertEquals(46000.0, (float) $newPayment3->total_paid);
        $this->assertEquals(46000.0, (float) $newPayment3->paymentmethoditems->first()->amount);

        $ret3 = InvoiceReturn::where('invoice_id', $inv3->id)->firstOrFail();
        $this->assertEquals(10000.0, $ret3->return_difference);
        $this->assertFalse($ret3->is_customer_refunded);
        $this->assertSame('Not Refunded', $ret3->refund_status);
        $this->assertEquals(0.0, $ret3->refund_amount);
        $this->assertNull($ret3->paymentmethod_id);
        $this->assertNull($ret3->refund_payment_method);
        $this->assertNull($ret3->bank_account_id);

        // DAILY RECONCILIATION REPORT QUERIES VERIFICATION
        $today = todaysDate();

        // 1. All customer refunds in date range (excludes Not Refunded)
        $this->assertEquals(2, InvoiceReturn::forDateRange($today, $today)->refunded()->count());

        // 2 & 4. Total Cash refunds
        $this->assertEquals(20000.0, (float) InvoiceReturn::forDateRange($today, $today)->cashRefunds()->sum('refund_amount'));

        // 3 & 5. Total Transfer refunds per bank account
        $transferByBank = InvoiceReturn::forDateRange($today, $today)
            ->transferRefunds()
            ->groupBy('bank_account_id')
            ->selectRaw('bank_account_id, SUM(refund_amount) as total')
            ->pluck('total', 'bank_account_id');
        $this->assertEquals(30000.0, (float) ($transferByBank[$this->bankAccount1->id] ?? 0));

        // 6. Total refunds processed by each cashier/user
        $refundsByUser = InvoiceReturn::forDateRange($today, $today)
            ->refunded()
            ->groupBy('processed_by')
            ->selectRaw('processed_by, SUM(refund_amount) as total')
            ->pluck('total', 'processed_by');
        $this->assertEquals(20000.0, (float) ($refundsByUser[$this->cashierA->id] ?? 0));
        $this->assertEquals(30000.0, (float) ($refundsByUser[$this->cashierB->id] ?? 0));

        // 7. CUSTOMER BALANCE SHEET (Creditpaymentlog) VERIFICATION
        // Verify that returning an invoice twice merges both returns on the SAME invoice Creditpaymentlog row
        $creditLogInv1 = \App\Models\Creditpaymentlog::create([
            'credit_number' => 'CR-INV-001',
            'payment_id' => $updated1->payment_id,
            'user_id' => $this->cashierA->id,
            'paymentmethod_id' => 4,
            'customer_id' => $this->customer->id,
            'invoicelog_type' => Invoice::class,
            'invoicelog_id' => $inv1->id,
            'amount' => -36000.0,
            'payment_date' => $today,
        ]);

        // After 1st return (20,000.00 CASH), the invoice row shows the invoice number and "20,000.00 (CASH)"
        $this->assertTrue($creditLogInv1->isCreditRecord());
        $this->assertFalse($creditLogInv1->isPaymentRecord());
        $this->assertSame('INV-RET-CASH', $creditLogInv1->invoiceNumberLabel());
        $this->assertSame('', $creditLogInv1->modeOfPaymentLabel());
        $this->assertSame('20,000.00 (CASH)', $creditLogInv1->returnOrRefundLabel());

        // Perform a 2nd return on the same invoice (reduce qty from 3 -> 2, returning another 10,000.00 via TRANSFER)
        $updated1Second = $repo->updateInvoice($inv1->fresh(), [
            'department' => 'wholesales',
            'in_department' => 'wholesales',
            'customer_id' => $this->customer->id,
            'status_id' => status('Paid'),
            'discount_type' => 'None',
            'discount_value' => 0,
            'discount_amount' => 0,
            'invoiceitems' => json_encode([
                ['stock_id' => $this->stockA->id, 'name' => $this->stockA->name, 'quantity' => 2, 'selling_price' => 10000, 'cost_price' => 8000, 'discount_type' => 'Fixed', 'discount_value' => 0, 'discount_amount' => 0],
                ['stock_id' => $this->stockB->id, 'name' => $this->stockB->name, 'quantity' => 2, 'selling_price' => 3000, 'cost_price' => 2000, 'discount_type' => 'Fixed', 'discount_value' => 0, 'discount_amount' => 0],
            ]),
            'return_info' => [
                'return_reason' => InvoiceReturn::REASON_CUSTOMER_RETURNED,
                'will_refund' => 'yes',
                'paymentmethod_id' => 3, // TRANSFER
                'bank_account_id' => $this->bankAccount1->id,
                'idempotency_key' => 'idem-cash-001-second',
            ],
        ]);
        $this->assertInstanceOf(Invoice::class, $updated1Second);

        // Both returns on INV-RET-CASH are merged together in the "Return or refund" column on the SAME invoice row
        $creditLogInv1Fresh = $creditLogInv1->fresh();
        $this->assertSame('INV-RET-CASH', $creditLogInv1Fresh->invoiceNumberLabel());
        $this->assertSame('20,000.00 (CASH), 10,000.00 (TRANSFER)', $creditLogInv1Fresh->returnOrRefundLabel());

        // Verify a Credit Payment row with description has Invoice blank, Mode of payment populated, and Return or refund blank
        $creditPaymentRow = \App\Models\Creditpaymentlog::create([
            'credit_number' => 'CR-PAY-001',
            'user_id' => $this->cashierA->id,
            'paymentmethod_id' => 3,
            'customer_id' => $this->customer->id,
            'amount' => 15000.0,
            'payment_date' => $today,
            'description' => 'Part settlement via Zenith Bank transfer',
        ]);
        $creditPaymentRow->load('paymentmethod');
        $this->assertTrue($creditPaymentRow->isPaymentRecord());
        $this->assertSame('', $creditPaymentRow->invoiceNumberLabel());
        $this->assertSame('TRANSFER', $creditPaymentRow->modeOfPaymentLabel());
        $this->assertSame('Part settlement via Zenith Bank transfer', $creditPaymentRow->descriptionLabel());
        $this->assertSame('', $creditPaymentRow->returnOrRefundLabel());
    }
}
