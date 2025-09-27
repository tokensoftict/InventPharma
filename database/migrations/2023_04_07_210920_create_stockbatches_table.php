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
        Schema::create('stockbatches', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->date("received_date")->nullable();
            $table->date("expiry_date")->nullable();
            $table->bigInteger("wholesales")->default(0)->index();
            $table->bigInteger("bulksales")->default(0)->index();
            $table->bigInteger("retail")->default(0)->index();
            $table->bigInteger("quantity")->default(0)->index();
            $table->decimal("cost_price",8,2)->nullable();
            $table->decimal("retail_cost_price",8,2)->nullable();
            $table->foreignId("stock_id")->nullable()->constrained()->nullOnDelete();
            $table->foreignId("supplier_id")->nullable()->constrained()->nullOnDelete();
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
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('stockbatches');
        Schema::enableForeignKeyConstraints();

    }
};
