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
        Schema::table('stocks', function (Blueprint $table) {
            $table->unsignedBigInteger("wholesales")->change();
            $table->unsignedBigInteger("bulksales")->change();
            $table->unsignedBigInteger("retail")->change();
            $table->unsignedBigInteger("quantity")->change();
        });


        Schema::table('stockbatches', function (Blueprint $table) {
            $table->unsignedBigInteger("wholesales")->change();
            $table->unsignedBigInteger("bulksales")->change();
            $table->unsignedBigInteger("retail")->change();
            $table->unsignedBigInteger("quantity")->change();
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
        Schema::table('stocks', function (Blueprint $table) {
            Schema::table('stocks', function (Blueprint $table) {
                $table->bigInteger("wholesales")->change();
                $table->bigInteger("bulksales")->change();
                $table->bigInteger("retail")->change();
                $table->bigInteger("quantity")->change();
            });


            Schema::table('stockbatches', function (Blueprint $table) {
                $table->bigInteger("wholesales")->change();
                $table->bigInteger("bulksales")->change();
                $table->bigInteger("retail")->change();
                $table->bigInteger("quantity")->change();
            });
        });
    }
};
