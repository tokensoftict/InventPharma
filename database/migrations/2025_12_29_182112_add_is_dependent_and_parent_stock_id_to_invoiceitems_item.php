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
        Schema::table('invoiceitems', function (Blueprint $table) {
            $table->boolean('isDependent')->default(false)->after('discount_added_by');
            $table->unsignedBigInteger('parent_stock_id')->nullable()->after('isDependent');
            $table->json('dependent_products')->nullable()->after('parent_stock_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoiceitems', function (Blueprint $table) {
            $table->dropColumn(['isDependent', 'parent_stock_id', 'dependent_products']);
        });
    }
};
