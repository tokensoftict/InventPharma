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
            $table->decimal("whole_price")->nullable()->index()->after('cost_price');
            $table->decimal("bulk_price")->nullable()->index()->after('cost_price');
            $table->decimal("retail_price")->nullable()->index()->after('cost_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchaseitems', function (Blueprint $table) {
            $table->dropColumn(['whole_price', 'bulk_price', 'retail_price']);
        });
    }
};
