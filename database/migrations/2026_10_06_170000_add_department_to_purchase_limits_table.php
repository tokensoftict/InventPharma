<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('purchase_limits') && !Schema::hasColumn('purchase_limits', 'department')) {
            Schema::table('purchase_limits', function (Blueprint $table) {
                $table->string('department', 50)->default('wholesales')->after('name');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('purchase_limits') && Schema::hasColumn('purchase_limits', 'department')) {
            Schema::table('purchase_limits', function (Blueprint $table) {
                $table->dropColumn('department');
            });
        }
    }
};
