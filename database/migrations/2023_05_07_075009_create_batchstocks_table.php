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
        Schema::create('batchstocks', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId("stock_id")->constrained()->cascadeOnDelete();
            $table->integer("quantity")->nullable();
            $table->integer("wholesales")->nullable();
            $table->integer("bulksales")->nullable();
            $table->integer("retail")->nullable();
            $table->unsignedBigInteger("quantity_user_id")->nullable();
            $table->unsignedBigInteger("bulk_user_id")->nullable();
            $table->unsignedBigInteger("wholsale_user_id")->nullable();
            $table->unsignedBigInteger("retail_user_id")->nullable();
            $table->foreign('wholsale_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('bulk_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('retail_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('quantity_user_id')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('batchstocks');
    }
};
