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
        Schema::table('stocks', function (Blueprint $table) {
            $table->decimal('highest_qty_sold', 8, 2)->nullable()->after('code');
            $table->decimal('highest_qty_sold_retail', 8, 2)->nullable()->after('code');
        });

        Schema::table('nearoutofstocks', function (Blueprint $table) {
            $table->decimal('qty_to_buy_1m', 8, 2)->nullable()->after('purchaseitem_id');
        });

        Schema::table('retailnearoutofstock', function (Blueprint $table) {
            $table->decimal('qty_to_buy_1m', 8, 2)->nullable()->after('purchaseitem_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropColumn('highest_qty_sold');
        });

        Schema::table('nearoutofstocks', function (Blueprint $table) {
            $table->dropColumn('qty_to_buy_1m');
        });

        Schema::table('retailnearoutofstock', function (Blueprint $table) {
            $table->dropColumn('qty_to_buy_1m');
        });
    }
};
