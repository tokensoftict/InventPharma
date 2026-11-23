<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureApplicationIsActive;
use App\Livewire\PurchaseReturn\PurchaseReturnComponent;
use App\Models\Purchase;
use App\Models\Purchaseitem;
use App\Models\PurchaseReturn;
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

class PurchaseReturnFastTest extends TestCase
{
    protected User $user;
    protected Supplier $supplierA;
    protected Supplier $supplierB;
    protected Stock $stock;
    protected Stockbatch $batch;

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
            'database.connections.pr_fast_test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => false,
            ],
            'database.default' => 'pr_fast_test',
            'cache.default' => 'array',
            'app.sync_with_online' => 0,
            'app.PURCHASE_DEPARTMENT' => false,
        ]);

        DB::purge('pr_fast_test');
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

        Schema::create('stocks', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('barcode')->nullable();
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
            $t->unsignedBigInteger('batched')->nullable();
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

        Schema::create('batchstocks', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('stock_id');
            $t->decimal('quantity', 20, 5)->default(0);
            $t->unsignedBigInteger('quantity_user_id')->nullable();
            $t->decimal('wholesales', 20, 5)->default(0);
            $t->unsignedBigInteger('wholesales_user_id')->nullable();
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

        Schema::create('purchases', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('status_id');
            $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedBigInteger('supplier_id')->nullable();
            $t->string('department')->nullable();
            $t->date('date_created')->nullable();
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
            $t->unsignedBigInteger('user_id')->nullable();
            $t->timestamps();
        });

        Schema::create('purchase_returns', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('status_id');
            $t->unsignedBigInteger('supplier_id');
            $t->unsignedBigInteger('purchase_id')->nullable();
            $t->string('department');
            $t->date('date_created')->nullable();
            $t->date('date_completed')->nullable();
            $t->text('remark')->nullable();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->unsignedBigInteger('completed_by')->nullable();
            $t->timestamps();
        });

        Schema::create('purchase_return_items', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('purchase_return_id');
            $t->unsignedBigInteger('stock_id');
            $t->string('batch_no')->nullable();
            $t->date('expiry_date')->nullable();
            $t->integer('qty');
            $t->decimal('cost_price', 20, 5)->default(0);
            $t->string('reason')->nullable();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->timestamps();
        });

        Schema::create('supplier_credit_payment_histories', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedBigInteger('supplier_id');
            $t->string('type')->nullable();
            $t->unsignedBigInteger('purchase_id')->nullable();
            $t->unsignedBigInteger('purchase_return_id')->nullable();
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
        foreach (['Draft', 'Complete', 'Pending'] as $name) {
            DB::table('statuses')->insert(['name' => $name, 'label' => $name]);
        }

        DB::table('departments')->insert([
            ['id' => 1, 'name' => 'Main Store', 'label' => 'Main Store', 'quantity_column' => 'quantity', 'status' => 1],
            ['id' => 4, 'name' => 'Retail', 'label' => 'Retail', 'quantity_column' => 'retail', 'status' => 1],
        ]);

        $mod = DB::table('modules')->insertGetId([
            'name' => 'PurchaseReturns',
            'label' => 'Purchase Returns',
            'status' => 1,
            'visibility' => 1,
            'order' => 1,
        ]);

        $group = DB::table('usergroups')->insertGetId(['name' => 'ReturnsStaff', 'status' => 1]);
        foreach (['purchasereturn.create', 'purchasereturn.edit', 'purchasereturn.show', 'purchasereturn.complete', 'purchasereturn.index'] as $r) {
            $tid = DB::table('tasks')->insertGetId([
                'module_id' => $mod,
                'route' => $r,
                'name' => $r,
                'status' => 1,
                'visibility' => in_array($r, ['purchasereturn.create', 'purchasereturn.index'], true) ? 1 : 0,
                'order' => 1,
            ]);
            DB::table('permissions')->insert(['usergroup_id' => $group, 'task_id' => $tid]);
        }

        $this->user = User::forceCreate([
            'name' => 'Return Officer',
            'email' => 'return@test.local',
            'password' => bcrypt('password'),
            'usergroup_id' => $group,
            'department_id' => 1,
            'status' => 1,
        ]);

        $this->supplierA = Supplier::forceCreate(['name' => 'Fidson Healthcare', 'phonenumber' => '08011112222', 'status' => 1]);
        $this->supplierB = Supplier::forceCreate(['name' => 'May & Baker', 'status' => 1]);

        $this->stock = Stock::forceCreate([
            'name' => 'Ciprofloxacin 500mg',
            'barcode' => 'CIPRO-500',
            'box' => 1,
            'cost_price' => 900,
            'quantity' => 20,
            'status' => 1,
        ]);

        $this->batch = Stockbatch::forceCreate([
            'stock_id' => $this->stock->id,
            'supplier_id' => $this->supplierA->id,
            'quantity' => 20,
            'cost_price' => 950,
            'batch_no' => 'CIP-2026',
            'expiry_date' => '2027-04-30',
        ]);
    }

    public function test_create_search_linked_po_draft_edit_complete_and_print_flow(): void
    {
        Bus::fake();
        $this->actingAs($this->user);

        // 1. Single-screen create page renders immediately (no Go gate)
        $this->get(route('purchasereturn.create'))
            ->assertOk()
            ->assertSee('Fast Return Entry');

        // 2. Search returns dept_qty + auto-filled batch & expiry
        $search = $this->getJson(route('purchasereturn.api.search', ['q' => 'CIPRO-500', 'column' => 'quantity']))
            ->assertOk()
            ->json();

        $this->assertCount(1, $search);
        $this->assertTrue($search[0]['exact_barcode']);
        $this->assertEquals(20, $search[0]['dept_qty']);
        $this->assertEquals(950.0, $search[0]['cost_price']);
        $this->assertSame('CIP-2026', $search[0]['batch_no']);
        $this->assertSame('2027-04-30', $search[0]['expiry_date']);

        // 3. Supplier POs endpoint filters strictly to selected supplier
        $poA = Purchase::forceCreate([
            'status_id' => status('Complete'),
            'supplier_id' => $this->supplierA->id,
            'department' => 'quantity',
            'date_created' => '2026-11-10',
            'user_id' => $this->user->id,
        ]);
        Purchaseitem::forceCreate([
            'purchase_id' => $poA->id,
            'stock_id' => $this->stock->id,
            'qty' => 10,
            'cost_price' => 950,
            'batch_no' => 'CIP-2026',
            'expiry_date' => '2027-04-30',
            'user_id' => $this->user->id,
        ]);
        Purchase::forceCreate([
            'status_id' => status('Complete'),
            'supplier_id' => $this->supplierB->id,
            'department' => 'quantity',
            'date_created' => '2026-11-12',
            'user_id' => $this->user->id,
        ]);

        $pos = $this->getJson(route('purchasereturn.api.supplier_pos', ['supplier_id' => $this->supplierA->id]))
            ->assertOk()
            ->json();
        $this->assertCount(1, $pos);
        $this->assertSame($poA->id, $pos[0]['id']);

        $poItems = $this->getJson(route('purchasereturn.api.load_po', ['purchase' => $poA->id, 'column' => 'quantity']))
            ->assertOk()
            ->json();
        $this->assertCount(1, $poItems);
        $this->assertEquals(10, $poItems[0]['po_qty']);

        // 4. Draft a purchase return via atomic payload
        $draftRes = Livewire::test(PurchaseReturnComponent::class, [
            'purchaseReturn' => new PurchaseReturn(),
            'suppliers' => suppliers(true),
            'departments' => departments(true),
        ])->call('draftReturn', [
            'supplier_id' => (string) $this->supplierA->id,
            'department' => 'quantity',
            'date_created' => '2026-11-23',
            'purchase_id' => (string) $poA->id,
            'remark' => 'Near expiry batch',
            'items' => [[
                'stock_id' => $this->stock->id,
                'qty' => 4,
                'cost_price' => 950,
                'expiry_date' => '2027-04-30',
                'batch_no' => 'CIP-2026',
                'reason' => 'Expired',
            ]],
        ])->effects['returns'][0] ?? null;

        $this->assertTrue($draftRes['status'] ?? false, 'Draft failed: ' . json_encode($draftRes));
        $return = PurchaseReturn::findOrFail($draftRes['return_id']);
        $this->assertSame('Near expiry batch', $return->remark);
        $this->assertEquals(20, (int) $this->batch->fresh()->quantity); // no deduction at draft

        // 5. Edit page loads with saved department selected & updates in place
        $this->get(route('purchasereturn.edit', $return->id))
            ->assertOk()
            ->assertSee('Edit Purchase Return #PRT-' . $return->id)
            ->assertSee('Update Draft');

        // 6. Complete deducts stock & logs RETURN ledger entry
        $compRes = Livewire::test(PurchaseReturnComponent::class, [
            'purchaseReturn' => $return->fresh('items'),
            'suppliers' => suppliers(true),
            'departments' => departments(true),
        ])->call('completeReturn', [
            'supplier_id' => (string) $this->supplierA->id,
            'department' => 'quantity',
            'date_created' => '2026-11-23',
            'purchase_id' => (string) $poA->id,
            'remark' => 'Near expiry batch',
            'items' => [[
                'stock_id' => $this->stock->id,
                'qty' => 5,
                'cost_price' => 950,
                'expiry_date' => '2027-04-30',
                'batch_no' => 'CIP-2026',
                'reason' => 'Expired',
            ]],
        ])->effects['returns'][0] ?? null;

        $this->assertTrue($compRes['status'] ?? false, 'Complete failed: ' . json_encode($compRes));
        $this->assertEquals(15, (int) $this->batch->fresh()->quantity);
        $this->assertEquals(
            4750.0,
            (float) SupplierCreditPaymentHistory::where('purchase_return_id', $return->id)->where('type', 'RETURN')->value('amount')
        );

        // 7. Compact print page renders cleanly
        $this->get(route('purchasereturn.print', $return->id))
            ->assertOk()
            ->assertSee('Purchase Return Note')
            ->assertSee('#PRT-' . $return->id)
            ->assertSee('4,750.00');
    }
}
