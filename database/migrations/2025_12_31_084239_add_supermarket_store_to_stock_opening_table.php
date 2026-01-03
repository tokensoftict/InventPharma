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
        Schema::table('stockopenings', function (Blueprint $table) {
            $table->integer('retail_store')->default(0)->index()->after('retail');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stockopenings', function (Blueprint $table) {
            $table->dropColumn('retail_store');
        });
    }
};
