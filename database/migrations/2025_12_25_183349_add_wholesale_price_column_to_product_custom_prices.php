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
        Schema::table('product_custom_prices', function (Blueprint $table) {
            $table->string('department', 20)->after('price')->default('retail');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_custom_prices', function (Blueprint $table) {
            $table->dropColumn('department');
        });
    }
};
