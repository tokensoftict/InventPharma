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
        Schema::table('customers', function (Blueprint $table) {
            $table->decimal('retail_loyalty_points', 15, 2)->default(0)->after('loyalty_points');
        });

        Schema::table('loyalty_transactions', function (Blueprint $table) {
            $table->string('loyalty_type')->default('other')->after('points');
            $table->index(['loyalty_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('retail_loyalty_points');
        });

        Schema::table('loyalty_transactions', function (Blueprint $table) {
            $table->dropColumn('loyalty_type');
        });
    }
};
