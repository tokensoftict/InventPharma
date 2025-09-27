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
        Schema::create('waiting_customers_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('waiting_customer_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger("waiting")->nullable();
            $table->unsignedBigInteger("picking")->nullable();
            $table->unsignedBigInteger("complete_picking")->nullable();
            $table->unsignedBigInteger("packing")->nullable();
            $table->unsignedBigInteger("packed")->nullable();
            $table->unsignedBigInteger("complete")->nullable();
            $table->unsignedBigInteger("dispatched")->nullable();
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
        Schema::dropIfExists('waiting_customers_histories');
    }
};
