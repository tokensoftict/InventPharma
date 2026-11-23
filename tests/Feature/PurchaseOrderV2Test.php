<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureApplicationIsActive;
use App\Livewire\PurchaseOrder\PurchaseOrderV2Component;
use App\Models\Purchase;
use App\Models\Purchaseitem;
use App\Models\Stock;
use App\Models\Stockbatch;
use App\Models\Supplier;
use App\Models\SupplierCreditPaymentHistory;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class PurchaseOrderV2Test extends TestCase
{
    protected User $allowedUser;
    protected User $deniedUser;
    protected Supplier $supplier;
    protected Stock $stockA;
    protected Stock $stockB;

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
            'database.connections.pov2_test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => false,
            ],
            'database.default' => 'pov2_test',
            'cache.default' => 'array',
            'app.sync_with_online' => 0,
            'app.PURCHASE_DEPARTMENT' => false,
        ]);

        DB::purge('pov2_test');
        Cache::flush();

        $this->createSchema();
        $this->seedFixtures();
    }

    private function createSchema(): void
    {
        Schema::create('modules', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('label');
            $t->string('description')->nullable();
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
            $t->string('icon')->nullable();
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

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('username')->nullable();
            $t->string('email')->nullable();
            $t->string('password')->default('secret');
            $t->unsignedBigInteger('usergroup_id')->default(1);
            $t->unsignedBigInteger('department_id')->default(1);
            $t->boolean('status')->default(true);
            $t->timestamps();
        });

        Schema::create('statuses', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('label')->nullable();
            $t->timestamps();
        });

        Schema::create('departments', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('label');
            $t->string('quantity_column');
            $t->string('price_column')->nullable();
            $t->string('type')->default('Sales');
            $t->boolean('status')->default(true);
            $t->timestamps();
        });

        Schema::create('suppliers', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->boolean('status')->default(true);
            $t->timestamps();
        });

        Schema::create('stocks', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('barcode')->nullable();
            $t->unsignedBigInteger('stockgroup_id')->nullable();
            $t->integer('box')->default(1);
            $t->string('location')->nullable();
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
            $t->decimal('highest_qty_sold', 20, 5)->default(0);
            $t->decimal('highest_qty_sold_retail', 20, 5)->default(0);
            $t->boolean('status')->default(true);
            $t->timestamps();
        });

        Schema::create('stockbarcodes', function (Blueprint $t) {
            $t->id();
            $t->string('barcode');
            $t->unsignedBigInteger('stock_id');
            $t->unsignedBigInteger('user_id')->nullable();
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
            $t->unsignedBigInteger('supplier_id')->nullable();
            $t->string('batch_no')->nullable();
            $t->timestamps();
        });

        Schema::create('nearoutofstocks', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('stock_id')->nullable();
            $t->unsignedBigInteger('stockgroup_id')->nullable();
            $t->unsignedBigInteger('supplier_id')->nullable();
            $t->integer('qty_to_buy')->default(0);
            $t->decimal('qty_to_buy_1m', 20, 5)->default(0);
            $t->timestamps();
        });

        Schema::create('retailnearoutofstock', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('stock_id')->nullable();
            $t->unsignedBigInteger('stockgroup_id')->nullable();
            $t->unsignedBigInteger('supplier_id')->nullable();
            $t->integer('qty_to_buy')->default(0);
            $t->decimal('qty_to_buy_1m', 20, 5)->default(0);
            $t->timestamps();
        });

        Schema::create('purchases', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('status_id');
            $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->unsignedBigInteger('completed_by')->nullable();
            $t->unsignedBigInteger('supplier_id')->nullable();
            $t->string('department')->nullable();
            $t->date('date_created')->nullable();
            $t->date('date_completed')->nullable();
            $t->string('batch_no')->nullable();
            $t->timestamps();
        });

        Schema::create('purchaseitems', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('purchase_id');
            $t->unsignedBigInteger('stock_id');
            $t->date('expiry_date')->nullable();
            $t->string('batch_no')->nullable();
            $t->integer('qty')->default(0);
            $t->decimal('cost_price', 20, 5)->default(0);
            $t->decimal('bulk_price', 20, 5)->nullable();
            $t->decimal('whole_price', 20, 5)->nullable();
            $t->decimal('retail_price', 20, 5)->nullable();
            $t->decimal('highest_qty_sold', 20, 5)->nullable();
            $t->decimal('qty_to_buy_1m', 20, 5)->nullable();
            $t->decimal('qty_1m_result', 20, 5)->nullable();
            $t->unsignedBigInteger('user_id')->nullable();
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

        Schema::create('pricechangehistories', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('stock_id')->nullable();
            $t->decimal('from', 20, 5)->nullable();
            $t->decimal('to', 20, 5)->nullable();
            $t->dateTime('change_date')->nullable();
            $t->string('change_time')->nullable();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('department')->nullable();
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
            $t->date('payment_date')->nullable();
            $t->text('payment_info')->nullable();
            $t->text('remark')->nullable();
            $t->timestamps();
        });
    }

    private function seedFixtures(): void
    {
        foreach (['Draft', 'Pre-Draft', 'Complete', 'Pending'] as $name) {
            DB::table('statuses')->insert(['name' => $name, 'label' => $name]);
        }

        DB::table('departments')->insert([
            ['id' => 1, 'name' => 'Main Store', 'label' => 'Main Store', 'quantity_column' => 'quantity', 'status' => 1],
            ['id' => 4, 'name' => 'Retail', 'label' => 'Retail', 'quantity_column' => 'retail', 'status' => 1],
        ]);

        $moduleId = DB::table('modules')->insertGetId([
            'name' => 'PurchaseOrder',
            'label' => 'Purchase Orders',
            'status' => 1,
            'visibility' => 1,
            'order' => 1,
            'icon' => 'briefcase',
        ]);

        $taskRoutes = [
            'purchase.create' => 'Create New Purchase',
            'purchase.edit' => 'Edit Purchase',
            'purchase.show' => 'Show Purchase Information',
            'purchase.complete' => 'Complete Purchase Order',
            'purchase.pre_draft' => 'Create Pre-Draft Purchase',
            'purchase.index' => 'Draft List',
        ];
        $taskIds = [];
        foreach ($taskRoutes as $route => $label) {
            $taskIds[$route] = DB::table('tasks')->insertGetId([
                'module_id' => $moduleId,
                'route' => $route,
                'name' => $label,
                'status' => 1,
                'visibility' => $route === 'purchase.create' ? 1 : 0,
                'order' => 1,
            ]);
        }

        // Group 2: non-admin buyer with v1 purchase permissions
        $buyerGroup = DB::table('usergroups')->insertGetId(['name' => 'Buyers', 'status' => 1]);
        foreach ($taskIds as $tid) {
            DB::table('permissions')->insert(['usergroup_id' => $buyerGroup, 'task_id' => $tid]);
        }

        // Group 3: cashier with NO purchase permissions
        $cashierGroup = DB::table('usergroups')->insertGetId(['name' => 'Cashiers', 'status' => 1]);

        $this->allowedUser = User::forceCreate([
            'name' => 'Buyer User',
            'email' => 'buyer@test.local',
            'password' => bcrypt('password'),
            'usergroup_id' => $buyerGroup,
            'department_id' => 1,
            'status' => 1,
        ]);

        $this->deniedUser = User::forceCreate([
            'name' => 'Cashier User',
            'email' => 'cashier@test.local',
            'password' => bcrypt('password'),
            'usergroup_id' => $cashierGroup,
            'department_id' => 1,
            'status' => 1,
        ]);

        $this->supplier = Supplier::forceCreate(['name' => 'Emzor Distributors', 'status' => 1]);

        $this->stockA = Stock::forceCreate([
            'name' => 'Paracetamol 500mg Tablet',
            'barcode' => '8901234567890',
            'box' => 1,
            'location' => 'A-1',
            'cost_price' => 450,
            'bulk_price' => 500,
            'whole_price' => 520,
            'retail_price' => 600,
            'quantity' => 5,
            'status' => 1,
        ]);

        $this->stockB = Stock::forceCreate([
            'name' => 'Amoxicillin 500mg Capsule',
            'barcode' => '8909999999999',
            'box' => 1,
            'location' => 'B-2',
            'cost_price' => 1200,
            'bulk_price' => 1350,
            'whole_price' => 1400,
            'retail_price' => 1500,
            'quantity' => 2,
            'status' => 1,
        ]);

        DB::table('stockbarcodes')->insert([
            'barcode' => 'ALT-AMOX-001',
            'stock_id' => $this->stockB->id,
            'user_id' => $this->allowedUser->id,
        ]);

        Stockbatch::forceCreate([
            'stock_id' => $this->stockA->id,
            'supplier_id' => $this->supplier->id,
            'quantity' => 5,
            'cost_price' => 475,
            'batch_no' => 'B-OLD',
            'expiry_date' => '2027-06-30',
        ]);

        DB::table('nearoutofstocks')->insert([
            'stock_id' => $this->stockA->id,
            'supplier_id' => $this->supplier->id,
            'qty_to_buy' => 20,
            'qty_to_buy_1m' => 30,
        ]);
    }

    public function test_user_with_v1_access_can_access_v2_and_sees_beta_menu_item(): void
    {
        $this->actingAs($this->allowedUser);

        $response = $this->get(route('purchase.v2.create'));
        $response->assertOk();
        $response->assertSee('Create Purchase Order (Beta)');

        $menuHtml = getUserMenu();
        $this->assertStringContainsString('Create Purchase Order (Beta)', $menuHtml);
        $this->assertStringContainsString(route('purchase.v2.create'), $menuHtml);
    }

    public function test_user_without_v1_access_is_forbidden_from_v2(): void
    {
        $this->actingAs($this->deniedUser);

        $this->get(route('purchase.v2.create'))->assertForbidden();
        $this->getJson(route('purchase.v2.search', ['q' => 'Para']))->assertForbidden();
    }

    public function test_search_products_supports_name_and_exact_barcodes(): void
    {
        $this->actingAs($this->allowedUser);

        // Name search
        $res = $this->getJson(route('purchase.v2.search', ['q' => 'Para 500', 'column' => 'quantity']));
        $res->assertOk();
        $data = $res->json();
        $this->assertCount(1, $data);
        $this->assertSame($this->stockA->id, $data[0]['id']);
        $this->assertEquals(475.0, $data[0]['cost_price']); // latest batch cost
        $this->assertEquals(30.0, $data[0]['qty_to_buy_1m']);
        $this->assertEquals(25, $data[0]['suggested_qty']); // 30 - 5

        // Secondary barcode exact match
        $resBar = $this->getJson(route('purchase.v2.search', ['q' => 'ALT-AMOX-001', 'column' => 'quantity']));
        $resBar->assertOk();
        $barData = $resBar->json();
        $this->assertCount(1, $barData);
        $this->assertSame($this->stockB->id, $barData[0]['id']);
        $this->assertTrue($barData[0]['exact_barcode']);
    }

    public function test_load_supplier_low_stock_and_clone_previous_po(): void
    {
        $this->actingAs($this->allowedUser);

        // Low stock loader
        $lowRes = $this->getJson(route('purchase.v2.low_stock', [
            'supplier_id' => $this->supplier->id,
            'column' => 'quantity',
        ]));
        $lowRes->assertOk();
        $low = $lowRes->json();
        $this->assertCount(1, $low);
        $this->assertSame($this->stockA->id, $low[0]['id']);
        $this->assertEquals(25, $low[0]['suggested_qty']);

        // Create a past PO and clone it
        $pastPo = Purchase::forceCreate([
            'status_id' => status('Complete'),
            'supplier_id' => $this->supplier->id,
            'department' => 'quantity',
            'date_created' => '2026-11-01',
            'user_id' => $this->allowedUser->id,
        ]);
        Purchaseitem::forceCreate([
            'purchase_id' => $pastPo->id,
            'stock_id' => $this->stockA->id,
            'qty' => 12,
            'cost_price' => 450,
            'user_id' => $this->allowedUser->id,
        ]);

        $recent = $this->getJson(route('purchase.v2.recent_pos', ['supplier_id' => $this->supplier->id]));
        $recent->assertOk();
        $this->assertSame($pastPo->id, $recent->json()[0]['id']);

        $cloned = $this->getJson(route('purchase.v2.clone_po', ['purchase' => $pastPo->id, 'column' => 'quantity']));
        $cloned->assertOk();
        $this->assertSame($this->stockA->id, $cloned->json()[0]['id']);
        $this->assertEquals(12, $cloned->json()[0]['previous_qty']);
        $this->assertEquals(475.0, $cloned->json()[0]['cost_price']);
    }

    public function test_v2_component_draft_predraft_and_complete_workflow(): void
    {
        Bus::fake();
        $this->actingAs($this->allowedUser);

        $component = Livewire::test(PurchaseOrderV2Component::class, [
            'purchase' => new Purchase(),
            'suppliers' => suppliers(true),
            'departments' => departments(true),
            'pageStatus' => status('Draft'),
        ]);

        // Pre-draft succeeds even without batch_no / expiry_date
        $preDraftRes = $component->call('preDraftPurchase', [
            'supplier_id' => (string) $this->supplier->id,
            'department' => 'quantity',
            'date_created' => '2026-11-23',
            'purchaseitems' => [[
                'stock_id' => $this->stockA->id,
                'name' => $this->stockA->name,
                'qty' => 10,
                'cost_price' => 480,
                'bulk_price' => 500,
                'whole_price' => 520,
                'retail_price' => 600,
                'highest_qty_sold' => 0,
                'qty_to_buy_1m' => 30,
                'qty_1m_result' => 25,
                'expiry_date' => '',
                'batch_no' => '',
            ]],
        ])->effects['returns'][0] ?? null;

        $this->assertTrue($preDraftRes['status'] ?? false);
        $this->assertEquals(1, Purchase::where('status_id', status('Pre-Draft'))->count());

        // Draft rejects missing batch/expiry
        $component2 = Livewire::test(PurchaseOrderV2Component::class, [
            'purchase' => new Purchase(),
            'suppliers' => suppliers(true),
            'departments' => departments(true),
            'pageStatus' => status('Draft'),
        ]);

        $badDraft = $component2->call('draftPurchase', [
            'supplier_id' => (string) $this->supplier->id,
            'department' => 'quantity',
            'date_created' => '2026-11-23',
            'purchaseitems' => [[
                'stock_id' => $this->stockA->id,
                'name' => $this->stockA->name,
                'qty' => 10,
                'cost_price' => 480,
                'bulk_price' => 500,
                'whole_price' => 520,
                'retail_price' => 600,
                'highest_qty_sold' => 0,
                'qty_to_buy_1m' => 30,
                'qty_1m_result' => 25,
                'expiry_date' => '',
                'batch_no' => '',
            ]],
        ])->effects['returns'][0] ?? null;

        $this->assertFalse($badDraft['status'] ?? true);

        // Complete creates batch + supplier credit ledger entry
        $completeRes = $component2->call('completePurchase', [
            'supplier_id' => (string) $this->supplier->id,
            'department' => 'quantity',
            'date_created' => '2026-11-23',
            'purchaseitems' => [[
                'stock_id' => $this->stockA->id,
                'name' => $this->stockA->name,
                'qty' => 10,
                'cost_price' => 480,
                'bulk_price' => 510,
                'whole_price' => 530,
                'retail_price' => 620,
                'highest_qty_sold' => 0,
                'qty_to_buy_1m' => 30,
                'qty_1m_result' => 25,
                'expiry_date' => '2028-08-31',
                'batch_no' => 'BATCH-99',
            ]],
        ])->effects['returns'][0] ?? null;

        $this->assertTrue($completeRes['status'] ?? false, 'Complete failed: ' . json_encode($completeRes));
        $completedPo = Purchase::find($completeRes['purchase_id']);
        $this->assertSame(status('Complete'), $completedPo->status_id);
        $this->assertEquals(1, Stockbatch::where('batch_no', 'BATCH-99')->where('quantity', 10)->count());
        $this->assertEquals(
            -4800.0,
            (float) SupplierCreditPaymentHistory::where('purchase_id', $completedPo->id)->value('amount')
        );
    }

    public function test_v2_edit_page_loads_and_updates_existing_draft_without_duplicating(): void
    {
        $draft = Purchase::forceCreate([
            'status_id' => status('Draft'),
            'supplier_id' => $this->supplier->id,
            'department' => 'quantity',
            'date_created' => '2026-11-20',
            'user_id' => $this->allowedUser->id,
        ]);

        Purchaseitem::forceCreate([
            'purchase_id' => $draft->id,
            'stock_id' => $this->stockA->id,
            'qty' => 5,
            'cost_price' => 450,
            'expiry_date' => '2028-06-30',
            'batch_no' => 'OLD-B1',
            'user_id' => $this->allowedUser->id,
        ]);

        // Denied user cannot edit
        $this->actingAs($this->deniedUser)
            ->get(route('purchase.v2.edit', $draft->id))
            ->assertForbidden();

        // Allowed user opens V2 edit page
        $this->actingAs($this->allowedUser)
            ->get(route('purchase.v2.edit', $draft->id))
            ->assertOk()
            ->assertSee('Edit Purchase Order #' . $draft->id . ' (Beta)')
            ->assertSee('Update Draft')
            ->assertSee('Classic Edit');

        // Updating via V2 component updates the existing Purchase ID in place
        $res = Livewire::test(PurchaseOrderV2Component::class, [
            'purchase' => $draft->fresh('purchaseitems'),
            'suppliers' => suppliers(true),
            'departments' => departments(true),
            'pageStatus' => $draft->status_id,
        ])->call('draftPurchase', [
            'supplier_id' => (string) $this->supplier->id,
            'department' => 'quantity',
            'date_created' => '2026-11-20',
            'purchaseitems' => [[
                'stock_id' => $this->stockA->id,
                'name' => $this->stockA->name,
                'qty' => 15,
                'cost_price' => 460,
                'bulk_price' => 500,
                'whole_price' => 520,
                'retail_price' => 600,
                'highest_qty_sold' => 0,
                'qty_to_buy_1m' => 30,
                'qty_1m_result' => 25,
                'expiry_date' => '2029-01-31',
                'batch_no' => 'NEW-B2',
            ]],
        ])->effects['returns'][0] ?? null;

        $this->assertTrue($res['status'] ?? false);
        $this->assertSame($draft->id, $res['purchase_id']);
        $this->assertEquals(1, Purchase::count());
        $this->assertEquals(15, $draft->fresh()->purchaseitems->first()->qty);
        $this->assertSame('NEW-B2', $draft->fresh()->purchaseitems->first()->batch_no);
        $this->assertSame('2029-01-31', $draft->fresh()->purchaseitems->first()->expiry_date);

        // Regression: calculateTotalAmount() / totalPo() must sum purchaseitems (not collide with Purchaseitem::getTotalAttribute)
        $this->assertEquals(6900.0, $draft->fresh()->calculateTotalAmount());
        $this->assertEquals(6900.0, (new \App\Repositories\PurchaseOrderRepository())->totalPo($draft->fresh()));

        // Compact print route inherits purchase.show permission and displays exact total
        $this->actingAs($this->deniedUser)
            ->get(route('purchase.print', $draft->id))
            ->assertForbidden();

        $this->actingAs($this->allowedUser)
            ->get(route('purchase.print', $draft->id))
            ->assertOk()
            ->assertSee('Purchase Order')
            ->assertSee('6,900.00');

        // Completed purchase redirects to show page
        $draft->update(['status_id' => status('Complete')]);
        $this->actingAs($this->allowedUser)
            ->get(route('purchase.v2.edit', $draft->id))
            ->assertRedirect(route('purchase.show', $draft->id));
    }
}
