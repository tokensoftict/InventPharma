<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();
        Schema::table('nearoutofstocks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('stock_id');
            $table->dropColumn('stockgroup_id');
        });
        Schema::table('nearoutofstocks', function (Blueprint $table) {
            $table->foreignId('stock_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->foreignId("stockgroup_id")->nullable()->after('stock_id')->constrained()->cascadeOnDelete();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }
};
