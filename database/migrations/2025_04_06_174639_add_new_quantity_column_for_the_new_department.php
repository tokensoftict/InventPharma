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
        Schema::table('stocks', function (Blueprint $table) {
            $table->bigInteger("retail_store")->after("quantity")->default(0)->index();
        });

        Schema::table('stockbatches', function (Blueprint $table) {
            $table->bigInteger("retail_store")->after("quantity")->default(0)->index();
        });

        Schema::table('batchstocks', function (Blueprint $table) {
            $table->integer("retail_store_user_id")->after("retail_user_id")->nullable();
            $table->bigInteger("retail_store")->nullable()->after("retail")->index();
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->string("department")->change();
        });
    }

    /**
     * 1339231528
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropColumn("retail_store");
        });

        Schema::table('stockbatches', function (Blueprint $table) {
            $table->dropColumn("retail_store");
        });

        Schema::table('batchstocks', function (Blueprint $table) {
            $table->dropColumn(["retail_store_user_id","retail_store"]);
        });
    }
};
