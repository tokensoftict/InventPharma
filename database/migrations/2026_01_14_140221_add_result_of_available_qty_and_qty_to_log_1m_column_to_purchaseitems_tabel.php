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
            $table->decimal("qty_1m_result")->nullable()->after('qty_to_buy_1m');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchaseitems', function (Blueprint $table) {
            $table->dropColumn("qty_1m_result");
        });
    }
};
