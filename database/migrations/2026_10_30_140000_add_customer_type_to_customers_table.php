<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('customers', 'customer_type')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->string('customer_type', 20)->nullable()->default(null)->after('retail_customer')->index();
            });
        }

        // Populate customer_type based on customer purchase invoices in in_department:
        // 'bulksales' and 'wholesales' => wholesale
        // 'retail' => retail
        // both => both
        try {
            if (DB::getDriverName() === 'mysql') {
                DB::statement("
                    UPDATE customers c
                    JOIN (
                        SELECT 
                            customer_id,
                            CASE 
                                WHEN MAX(CASE WHEN in_department IN ('bulksales', 'wholesales') THEN 1 ELSE 0 END) = 1 
                                 AND MAX(CASE WHEN in_department = 'retail' THEN 1 ELSE 0 END) = 1 THEN 'both'
                                WHEN MAX(CASE WHEN in_department = 'retail' THEN 1 ELSE 0 END) = 1 THEN 'retail'
                                ELSE 'wholesale'
                            END AS computed_type
                        FROM invoices 
                        WHERE customer_id IS NOT NULL 
                        GROUP BY customer_id
                    ) inv ON c.id = inv.customer_id
                    SET c.customer_type = inv.computed_type
                ");
            } else {
                $rows = DB::table('invoices')
                    ->select('customer_id')
                    ->selectRaw("MAX(CASE WHEN in_department IN ('bulksales', 'wholesales') THEN 1 ELSE 0 END) as has_wholesale")
                    ->selectRaw("MAX(CASE WHEN in_department = 'retail' THEN 1 ELSE 0 END) as has_retail")
                    ->whereNotNull('customer_id')
                    ->groupBy('customer_id')
                    ->get();

                foreach ($rows as $row) {
                    $type = 'wholesale';
                    if ($row->has_wholesale && $row->has_retail) {
                        $type = 'both';
                    } elseif ($row->has_retail) {
                        $type = 'retail';
                    }
                    DB::table('customers')->where('id', $row->customer_id)->update(['customer_type' => $type]);
                }
            }

            // Fallback for customers without invoice history: use retail_customer flag
            DB::table('customers')->whereNull('customer_type')->where('retail_customer', 1)->update(['customer_type' => 'retail']);
            DB::table('customers')->whereNull('customer_type')->update(['customer_type' => 'wholesale']);
        } catch (\Throwable $e) {
            // Ignore in environments where invoices table may not exist
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('customers', 'customer_type')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropIndex(['customer_type']);
                $table->dropColumn('customer_type');
            });
        }
    }
};
