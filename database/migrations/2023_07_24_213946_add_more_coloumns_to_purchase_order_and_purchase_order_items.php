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
        Schema::table('purchases', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable()->after('completed_by');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('purchaseitems', function (Blueprint $table) {
            $table->string('batch_no', '30')->nullable()->after('stock_id')->index();
        });

        Schema::table('stockbatches', function (Blueprint $table) {
            $table->string('batch_no', '30')->nullable()->after('expiry_date')->index();
        });

        Schema::table('invoiceitembatches', function (Blueprint $table) {
            $table->string('batch_no', '30')->nullable()->after('stockbatch_id')->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign("purchases_created_by_foreign");
            $table->dropColumn('created_by');
        });

        Schema::table('purchaseitems', function (Blueprint $table) {
            $table->dropColumn("batch_no");
        });

        Schema::table('stockbatches', function (Blueprint $table) {
            $table->dropColumn("batch_no");
        });

        Schema::table('invoiceitembatches', function (Blueprint $table) {
            $table->dropColumn("batch_no");
        });
    }
};
