<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureApplicationIsActive;
use App\Livewire\PurchaseOrder\Supplier\Payment\SupplierPaymentComponent;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Supplier;
use App\Models\SupplierCreditPaymentHistory;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class SupplierPaymentFieldsTest extends TestCase
{
    protected User $user;
    protected Supplier $supplier;

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

        $this->withoutMiddleware(EnsureApplicationIsActive::class);

        config([
            'database.connections.sp_fields_test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => false,
            ],
            'database.default' => 'sp_fields_test',
            'cache.default' => 'array',
        ]);

        DB::purge('sp_fields_test');
        Cache::flush();

        Schema::create('modules', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('label');
            $t->boolean('status')->default(true);
            $t->boolean('visibility')->default(true);
            $t->integer('order')->default(0);
            $t->string('icon')->nullable();
            $t->timestamps();
        });

        Schema::create('tasks', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('module_id');
            $t->unsignedBigInteger('parent_task_id')->default(0);
            $t->string('route');
            $t->string('name');
            $t->string('description')->nullable();
            $t->boolean('status')->default(true);
            $t->boolean('visibility')->default(true);
            $t->integer('order')->default(0);
            $t->timestamps();
        });

        Schema::create('usergroups', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->boolean('status')->default(true);
            $t->timestamps();
        });

        Schema::create('permissions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('usergroup_id');
            $t->unsignedBigInteger('task_id');
            $t->timestamps();
        });

        Schema::create('purchases', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('status_id')->default(1);
            $t->unsignedBigInteger('supplier_id')->nullable();
            $t->string('department')->nullable();
            $t->date('date_created')->nullable();
            $t->timestamps();
        });

        Schema::create('purchase_returns', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('status_id')->default(1);
            $t->unsignedBigInteger('supplier_id')->nullable();
            $t->string('department')->nullable();
            $t->date('date_created')->nullable();
            $t->timestamps();
        });

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('password')->default('secret');
            $t->unsignedBigInteger('usergroup_id')->default(1);
            $t->unsignedBigInteger('department_id')->default(1);
            $t->boolean('status')->default(true);
            $t->timestamps();
        });

        Schema::create('departments', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('label')->nullable();
            $t->string('quantity_column')->nullable();
            $t->boolean('status')->default(true);
            $t->timestamps();
        });

        Schema::create('suppliers', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('phonenumber')->nullable();
            $t->boolean('status')->default(true);
            $t->timestamps();
        });

        Schema::create('paymentmethods', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->boolean('status')->default(true);
            $t->timestamps();
        });

        Schema::create('statuses', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('label')->nullable();
            $t->timestamps();
        });

        Schema::create('invoices', function (Blueprint $t) {
            $t->id();
            $t->string('invoice_number')->nullable();
            $t->unsignedBigInteger('supplier_id')->nullable();
            $t->unsignedBigInteger('status_id')->nullable();
            $t->boolean('is_stock_exchange')->default(false);
            $t->decimal('sub_total', 20, 5)->default(0);
            $t->decimal('discount_amount', 20, 5)->default(0);
            $t->timestamps();
        });

        Schema::create('banks', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->boolean('status')->default(true);
            $t->timestamps();
        });

        Schema::create('supplier_credit_payment_histories', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedBigInteger('supplier_id');
            $t->string('type')->nullable();
            $t->unsignedBigInteger('purchase_id')->nullable();
            $t->unsignedBigInteger('purchase_return_id')->nullable();
            $t->unsignedBigInteger('invoice_id')->nullable();
            $t->unsignedBigInteger('paymentmethod_id')->nullable();
            $t->string('reference')->nullable();
            $t->decimal('amount', 20, 5)->default(0);
            $t->string('status')->nullable();
            $t->timestamp('reversed_at')->nullable();
            $t->date('payment_date')->nullable();
            $t->text('payment_info')->nullable();
            $t->text('remark')->nullable();
            $t->timestamps();
        });

        DB::table('departments')->insert([
            ['id' => 1, 'name' => 'Main Store', 'label' => 'Main Store', 'quantity_column' => 'quantity', 'status' => 1],
        ]);

        DB::table('paymentmethods')->insert([
            ['id' => 1, 'name' => 'CASH', 'status' => 1],
            ['id' => 2, 'name' => 'POS', 'status' => 1],
            ['id' => 3, 'name' => 'TRANSFER', 'status' => 1],
            ['id' => 8, 'name' => 'CHEQUE', 'status' => 1],
        ]);

        DB::table('banks')->insert([
            ['id' => 1, 'name' => 'Zenith Bank', 'status' => 1],
            ['id' => 2, 'name' => 'Guaranty Trust Bank', 'status' => 1],
        ]);

        $this->user = User::forceCreate([
            'name' => 'Accountant',
            'email' => 'acc@test.local',
            'password' => bcrypt('password'),
            'usergroup_id' => 1,
            'department_id' => 1,
            'status' => 1,
        ]);

        $this->supplier = Supplier::forceCreate(['name' => 'Emzor', 'status' => 1]);
    }

    public function test_transfer_payment_requires_and_saves_bank_details(): void
    {
        $this->actingAs($this->user);

        Livewire::test(SupplierPaymentComponent::class)
            ->set('payment_data.supplier_id', $this->supplier->id)
            ->set('payment_data.paymentmethod_id', 3)
            ->set('payment_data.amount', '50000')
            ->assertSee('Bank Name')
            ->assertSee('Zenith Bank')
            ->assertSee('Account Number')
            ->assertSee('Account Name')
            ->assertDontSee('Cash Received By')
            ->call('savePayment')
            ->assertHasErrors([
                'payment_data.payment_info.bank_name',
                'payment_data.payment_info.account_number',
                'payment_data.payment_info.account_name',
            ])
            ->set('payment_data.payment_info.bank_name', 'Zenith Bank')
            ->set('payment_data.payment_info.account_number', '1012345678')
            ->set('payment_data.payment_info.account_name', 'Emzor Pharma Ltd')
            ->call('savePayment')
            ->assertHasNoErrors();

        $saved = SupplierCreditPaymentHistory::latest('id')->first();
        $this->assertNotNull($saved);
        $this->assertSame('Zenith Bank', $saved->payment_info['bank_name']);
        $this->assertSame('1012345678', $saved->payment_info['account_number']);
        $this->assertSame('Emzor Pharma Ltd', $saved->payment_info['account_name']);
    }

    public function test_cash_payment_requires_and_saves_cash_received_by(): void
    {
        $this->actingAs($this->user);

        Livewire::test(SupplierPaymentComponent::class)
            ->set('payment_data.supplier_id', $this->supplier->id)
            ->set('payment_data.paymentmethod_id', 1)
            ->set('payment_data.amount', '25000')
            ->assertSee('Cash Received By')
            ->assertDontSee('Transfer Details')
            ->call('savePayment')
            ->assertHasErrors(['payment_data.payment_info.cash_received_by'])
            ->set('payment_data.payment_info.cash_received_by', 'John Musa')
            ->call('savePayment')
            ->assertHasNoErrors();

        $saved = SupplierCreditPaymentHistory::latest('id')->first();
        $this->assertNotNull($saved);
        $this->assertSame('John Musa', $saved->payment_info['cash_received_by']);
        $this->assertNull($saved->payment_info['bank_name'] ?? null);
    }

    public function test_supplier_account_statement_combines_supplies_payments_and_returns(): void
    {
        $modId = DB::table('modules')->insertGetId([
            'name' => 'PurchaseReport',
            'label' => 'Purchase Report(s)',
            'status' => 1,
            'visibility' => 1,
            'order' => 1,
        ]);
        $groupId = DB::table('usergroups')->insertGetId(['name' => 'Finance', 'status' => 1]);
        $taskId = DB::table('tasks')->insertGetId([
            'module_id' => $modId,
            'route' => 'reports.purchase.balance_sheet',
            'name' => 'Supplier Balance Sheet',
            'description' => 'Supplier Balance Sheet',
            'status' => 1,
            'visibility' => 0,
            'order' => 1,
        ]);
        DB::table('permissions')->insert(['usergroup_id' => $groupId, 'task_id' => $taskId]);
        $this->user->update(['usergroup_id' => $groupId]);

        $deniedGroup = DB::table('usergroups')->insertGetId(['name' => 'NoReport', 'status' => 1]);
        $deniedUser = User::forceCreate([
            'name' => 'Basic',
            'email' => 'basic@test.local',
            'password' => bcrypt('password'),
            'usergroup_id' => $deniedGroup,
            'department_id' => 1,
            'status' => 1,
        ]);

        // Denied user gets 403
        $this->actingAs($deniedUser)
            ->get(route('reports.purchase.supplier_account_statement'))
            ->assertForbidden();

        // Opening entry before range: -20,000
        SupplierCreditPaymentHistory::create([
            'user_id' => $this->user->id,
            'supplier_id' => $this->supplier->id,
            'type' => 'CREDIT',
            'amount' => -20000,
            'payment_date' => '2026-10-15',
            'remark' => 'Prior balance',
        ]);

        $po = Purchase::forceCreate([
            'status_id' => 1,
            'supplier_id' => $this->supplier->id,
            'department' => 'quantity',
            'date_created' => '2026-11-05',
        ]);
        $pr = PurchaseReturn::forceCreate([
            'status_id' => 1,
            'supplier_id' => $this->supplier->id,
            'department' => 'quantity',
            'date_created' => '2026-11-12',
        ]);

        // In-range entries:
        // 1. Supply -100,000
        SupplierCreditPaymentHistory::create([
            'user_id' => $this->user->id,
            'supplier_id' => $this->supplier->id,
            'type' => 'CREDIT',
            'purchase_id' => $po->id,
            'amount' => -100000,
            'payment_date' => '2026-11-05',
        ]);
        // 2. Transfer payment +40,000
        SupplierCreditPaymentHistory::create([
            'user_id' => $this->user->id,
            'supplier_id' => $this->supplier->id,
            'type' => 'PAYMENT',
            'paymentmethod_id' => 3,
            'amount' => 40000,
            'payment_date' => '2026-11-08',
            'payment_info' => [
                'status' => 'Approved',
                'bank_name' => 'Zenith Bank',
                'account_number' => '1012345678',
                'account_name' => 'Emzor Pharma Ltd',
            ],
            'remark' => 'Part transfer',
        ]);
        // 3. Cash payment +15,000
        SupplierCreditPaymentHistory::create([
            'user_id' => $this->user->id,
            'supplier_id' => $this->supplier->id,
            'type' => 'PAYMENT',
            'paymentmethod_id' => 1,
            'amount' => 15000,
            'payment_date' => '2026-11-10',
            'payment_info' => [
                'status' => 'Approved',
                'cash_received_by' => 'John Musa',
            ],
            'remark' => 'Cash settlement',
        ]);
        // 4. Purchase Return +10,000
        SupplierCreditPaymentHistory::create([
            'user_id' => $this->user->id,
            'supplier_id' => $this->supplier->id,
            'type' => 'RETURN',
            'purchase_return_id' => $pr->id,
            'reference' => 'PRT-' . $pr->id,
            'amount' => 10000,
            'payment_date' => '2026-11-12',
            'remark' => 'Expired items returned',
        ]);
        // 5. Pending cheque (must be excluded!)
        SupplierCreditPaymentHistory::create([
            'user_id' => $this->user->id,
            'supplier_id' => $this->supplier->id,
            'type' => 'PAYMENT',
            'paymentmethod_id' => 8,
            'amount' => 99999,
            'payment_date' => '2026-11-14',
            'payment_info' => ['status' => 'Pending'],
        ]);

        // Closing balance = -20,000 - 100,000 + 40,000 + 15,000 + 10,000 = -55,000.00
        $res = $this->actingAs($this->user)
            ->post(route('reports.purchase.supplier_account_statement'), [
                'filter' => [
                    'from' => '2026-11-01',
                    'to' => '2026-11-30',
                    'supplier_id' => $this->supplier->id,
                ],
            ]);

        $res->assertOk();
        $res->assertSee('Supplier Account Statement');
        $res->assertSee('Supplied Total');
        $res->assertSee('Account Name (Bank Name)');
        $res->assertSee('Cash Receiver');
        $res->assertSee('Total Worth of Stocks Return');
        $res->assertSee('Emzor Pharma Ltd (Zenith Bank)');
        $res->assertSee('1012345678');
        $res->assertSee('John Musa');
        $res->assertSee('Purchase Order #' . $po->id);
        $res->assertSee('Purchase Return #PRT-' . $pr->id);
        $res->assertSee('100,000.00');
        $res->assertSee('55,000.00');
        $res->assertSee('10,000.00');
        $res->assertSee('-55,000.00');
        $res->assertDontSee('99,999.00');
    }
}
