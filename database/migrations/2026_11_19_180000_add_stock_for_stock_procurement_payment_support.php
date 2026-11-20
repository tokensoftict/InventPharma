<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('statuses')) {
            $newStatuses = [
                ['name' => 'Unpaid', 'label' => 'warning'],
                ['name' => 'Partially Exchanged', 'label' => 'info'],
                ['name' => 'Exchanged', 'label' => 'success'],
            ];

            foreach ($newStatuses as $status) {
                DB::table('statuses')->updateOrInsert(['name' => $status['name']], $status);
            }

            Cache::forget('status');
            Cache::forget('statuses');
        }

        if (Schema::hasTable('paymentmethods')) {
            $exists = DB::table('paymentmethods')
                ->whereIn(DB::raw('UPPER(name)'), ['STOCK FOR STOCK', 'STOCK-FOR-STOCK', 'STOCK_FOR_STOCK'])
                ->exists();

            if (!$exists) {
                DB::table('paymentmethods')->insert([
                    'name' => 'STOCK FOR STOCK',
                    'status' => 1,
                ]);
            }

            Cache::forget('paymentmethods');
        }

        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                if (!Schema::hasColumn('invoices', 'is_stock_exchange')) {
                    $table->boolean('is_stock_exchange')->default(false)->after('customer_id')->index();
                }

                if (!Schema::hasColumn('invoices', 'invoice_type')) {
                    $table->string('invoice_type', 30)->default('normal')->after('is_stock_exchange')->index();
                }

                if (!Schema::hasColumn('invoices', 'supplier_id')) {
                    $table->foreignId('supplier_id')->nullable()->after('invoice_type')->constrained('suppliers')->nullOnDelete();
                }

                if (!Schema::hasColumn('invoices', 'exchange_amount_utilised')) {
                    $table->decimal('exchange_amount_utilised', 20, 5)->default(0)->after('total_amount_paid');
                }
            });
        }

        if (Schema::hasTable('supplier_credit_payment_histories')) {
            Schema::table('supplier_credit_payment_histories', function (Blueprint $table) {
                if (!Schema::hasColumn('supplier_credit_payment_histories', 'invoice_id')) {
                    $table->foreignId('invoice_id')->nullable()->after('purchase_id')->constrained('invoices')->nullOnDelete();
                }

                if (!Schema::hasColumn('supplier_credit_payment_histories', 'reference')) {
                    $table->string('reference', 100)->nullable()->after('paymentmethod_id')->index();
                }

                if (!Schema::hasColumn('supplier_credit_payment_histories', 'status')) {
                    $table->string('status', 30)->default('Active')->after('amount')->index();
                }

                if (!Schema::hasColumn('supplier_credit_payment_histories', 'reversed_at')) {
                    $table->timestamp('reversed_at')->nullable()->after('status');
                }

                if (!Schema::hasColumn('supplier_credit_payment_histories', 'reversed_by')) {
                    $table->foreignId('reversed_by')->nullable()->after('reversed_at')->constrained('users')->nullOnDelete();
                }

                if (!Schema::hasColumn('supplier_credit_payment_histories', 'reversal_reason')) {
                    $table->string('reversal_reason', 255)->nullable()->after('reversed_by');
                }

                if (!Schema::hasColumn('supplier_credit_payment_histories', 'idempotency_key')) {
                    $table->string('idempotency_key', 100)->nullable()->unique()->after('reversal_reason');
                }
            });
        }

        if (Schema::hasTable('modules') && Schema::hasTable('tasks')) {
            $invoiceModule = DB::table('modules')->where('name', 'InvoiceAndSales')->first();
            if ($invoiceModule && !DB::table('tasks')->where('route', 'invoiceandsales.createStockExchangeInvoice')->exists()) {
                $maxOrder = (int) DB::table('tasks')->where('module_id', $invoiceModule->id)->max('order');
                $taskData = [
                    'module_id' => $invoiceModule->id,
                    'route' => 'invoiceandsales.createStockExchangeInvoice',
                    'name' => 'Create Stock Exchange Invoice (Wholesales Only)',
                    'visibility' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                if (Schema::hasColumn('tasks', 'description')) {
                    $taskData['description'] = 'Create Stock Exchange Invoice (Wholesales Only)';
                }
                if (Schema::hasColumn('tasks', 'status')) {
                    $taskData['status'] = 1;
                }
                if (Schema::hasColumn('tasks', 'order')) {
                    $taskData['order'] = $maxOrder + 1;
                }
                if (Schema::hasColumn('tasks', 'parent_task_id')) {
                    $taskData['parent_task_id'] = 0;
                }
                $taskId = DB::table('tasks')->insertGetId($taskData);

                if (Schema::hasTable('permissions') && !DB::table('permissions')->where(['usergroup_id' => 1, 'task_id' => $taskId])->exists()) {
                    DB::table('permissions')->insert([
                        'usergroup_id' => 1,
                        'task_id' => $taskId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    Cache::forget('route-permission-1');
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('supplier_credit_payment_histories')) {
            Schema::table('supplier_credit_payment_histories', function (Blueprint $table) {
                if (Schema::hasColumn('supplier_credit_payment_histories', 'invoice_id')) {
                    $table->dropConstrainedForeignId('invoice_id');
                }
                if (Schema::hasColumn('supplier_credit_payment_histories', 'reversed_by')) {
                    $table->dropConstrainedForeignId('reversed_by');
                }
                $dropCols = array_filter(
                    ['reference', 'status', 'reversed_at', 'reversal_reason', 'idempotency_key'],
                    fn ($col) => Schema::hasColumn('supplier_credit_payment_histories', $col)
                );
                if (!empty($dropCols)) {
                    $table->dropColumn($dropCols);
                }
            });
        }

        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                if (Schema::hasColumn('invoices', 'supplier_id')) {
                    $table->dropConstrainedForeignId('supplier_id');
                }
                $dropCols = array_filter(
                    ['is_stock_exchange', 'invoice_type', 'exchange_amount_utilised'],
                    fn ($col) => Schema::hasColumn('invoices', $col)
                );
                if (!empty($dropCols)) {
                    $table->dropColumn($dropCols);
                }
            });
        }

        if (Schema::hasTable('paymentmethods')) {
            DB::table('paymentmethods')
                ->whereIn(DB::raw('UPPER(name)'), ['STOCK FOR STOCK', 'STOCK-FOR-STOCK', 'STOCK_FOR_STOCK'])
                ->delete();
            Cache::forget('paymentmethods');
        }
    }
};
