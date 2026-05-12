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
        Schema::table('member_groups', function (Blueprint $table) {
            $table->decimal('member_discount', 5, 2)->default(0)->after('retail_min_sales_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('member_groups', function (Blueprint $table) {
            $table->dropColumn('member_discount');
        });
    }
};
