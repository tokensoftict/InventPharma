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
            $table->string('retail_color')->nullable()->after('color');
            $table->string('retail_bg_color')->nullable()->after('bg_color');
            $table->decimal('retail_min_sales_amount', 15, 2)->default(0)->after('min_sales_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('member_groups', function (Blueprint $table) {
            $table->dropColumn(['retail_color', 'retail_bg_color', 'retail_min_sales_amount']);
        });
    }
};
