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
        Schema::table('stockgroups', function (Blueprint $table) {
            $table->decimal('highest_qty_sold', 8, 2)->nullable()->after('name');
            $table->decimal('highest_qty_sold_retail', 8, 2)->nullable()->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stockgroups', function (Blueprint $table) {
            //
        });
    }
};
