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
        Schema::table('purchaseitems', function (Blueprint $table) {
            $table->decimal('highest_qty_sold')->nullable()->after('whole_price');
            $table->decimal('qty_to_buy_1m')->nullable()->after('highest_qty_sold');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchaseitems', function (Blueprint $table) {
            $table->dropColumn(['highest_qty_sold', 'qty_to_buy_1m']);
        });
    }
};
