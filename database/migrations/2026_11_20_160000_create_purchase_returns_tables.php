<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('purchase_returns')) {
            Schema::create('purchase_returns', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->foreignId('status_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
                // optional reference to the original purchase order being returned against
                $table->foreignId('purchase_id')->nullable()->constrained()->nullOnDelete();
                $table->string('department', 30)->default('quantity');
                $table->date('date_created')->nullable();
                $table->date('date_completed')->nullable();
                $table->text('remark')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('completed_by')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
                $table->foreign('completed_by')->references('id')->on('users')->nullOnDelete();
                $table->index(['supplier_id', 'status_id']);
            });
        }

        if (!Schema::hasTable('purchase_return_items')) {
            Schema::create('purchase_return_items', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->foreignId('purchase_return_id')->constrained('purchase_returns')->cascadeOnDelete();
                $table->foreignId('stock_id')->nullable()->constrained()->nullOnDelete();
                $table->string('batch_no', 30)->nullable()->index();
                $table->date('expiry_date')->nullable();
                $table->integer('qty')->default(0);
                $table->decimal('cost_price', 20, 5)->default(0);
                $table->string('reason', 255)->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            });
        }

        // Link supplier ledger entries to the purchase return that produced them
        if (Schema::hasTable('supplier_credit_payment_histories')
            && !Schema::hasColumn('supplier_credit_payment_histories', 'purchase_return_id')) {
            Schema::table('supplier_credit_payment_histories', function (Blueprint $table) {
                $table->foreignId('purchase_return_id')->nullable()->after('invoice_id')
                    ->constrained('purchase_returns')->nullOnDelete();
            });
        }

        // Register the module + tasks (permissions) without touching existing ones; grant to admin group 1.
        if (Schema::hasTable('modules') && Schema::hasTable('tasks')) {
            $module = DB::table('modules')->where('name', 'PurchaseReturns')->first();
            if (!$module) {
                $moduleId = DB::table('modules')->insertGetId([
                    'name' => 'PurchaseReturns',
                    'label' => 'Purchase Returns',
                    'description' => 'PurchaseReturns Module',
                    'status' => '1',
                    'visibility' => '1',
                    'order' => 0,
                    'icon' => 'corner-up-left',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $moduleId = $module->id;
            }

            $tasks = [
                ['purchasereturn.index', 'Draft Returns List', 1],
                ['purchasereturn.approved', 'Completed Returns List', 1],
                ['purchasereturn.create', 'Create New Purchase Return', 1],
                ['purchasereturn.edit', 'Edit Purchase Return', 0],
                ['purchasereturn.show', 'Show Purchase Return Information', 0],
                ['purchasereturn.destroy', 'Delete Purchase Return', 0],
                ['purchasereturn.complete', 'Complete Purchase Return', 0],
            ];

            $order = (int) DB::table('tasks')->where('module_id', $moduleId)->max('order');
            foreach ($tasks as [$route, $name, $visible]) {
                $existing = DB::table('tasks')->where('route', $route)->first();
                if ($existing) {
                    $taskId = $existing->id;
                } else {
                    $row = [
                        'module_id' => $moduleId,
                        'route' => $route,
                        'name' => $name,
                        'visibility' => $visible,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    if (Schema::hasColumn('tasks', 'description')) $row['description'] = $name;
                    if (Schema::hasColumn('tasks', 'status')) $row['status'] = 1;
                    if (Schema::hasColumn('tasks', 'order')) $row['order'] = ++$order;
                    if (Schema::hasColumn('tasks', 'parent_task_id')) $row['parent_task_id'] = 0;
                    $taskId = DB::table('tasks')->insertGetId($row);
                }

                if (Schema::hasTable('permissions')
                    && !DB::table('permissions')->where(['usergroup_id' => 1, 'task_id' => $taskId])->exists()) {
                    DB::table('permissions')->insert([
                        'usergroup_id' => 1,
                        'task_id' => $taskId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
            Cache::forget('route-permission-1');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('supplier_credit_payment_histories')
            && Schema::hasColumn('supplier_credit_payment_histories', 'purchase_return_id')) {
            Schema::table('supplier_credit_payment_histories', function (Blueprint $table) {
                $table->dropConstrainedForeignId('purchase_return_id');
            });
        }

        Schema::dropIfExists('purchase_return_items');
        Schema::dropIfExists('purchase_returns');

        if (Schema::hasTable('tasks')) {
            $ids = DB::table('tasks')->where('route', 'like', 'purchasereturn.%')->pluck('id');
            if (Schema::hasTable('permissions')) {
                DB::table('permissions')->whereIn('task_id', $ids)->delete();
            }
            DB::table('tasks')->whereIn('id', $ids)->delete();
        }
        if (Schema::hasTable('modules')) {
            DB::table('modules')->where('name', 'PurchaseReturns')->delete();
        }
        Cache::forget('route-permission-1');
    }
};
