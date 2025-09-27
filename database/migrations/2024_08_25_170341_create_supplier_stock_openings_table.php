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
        Schema::create('supplier_stock_openings', function (Blueprint $table) {
            $table->id();
            $table->foreignId("supplier_id")->constrained("suppliers")->cascadeOnDelete();
            $table->decimal("total_opening_cost_price",20, 5);
            $table->decimal("total_opening_retail_cost_price",20, 5);
            $table->decimal("total_supplier_outstanding", 20, 5);
            $table->bigInteger("total_opening_quantity_retail");
            $table->bigInteger("total_opening_quantity");
            $table->date("last_supplier_date");
            $table->date("date_added");
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
        Schema::dropIfExists('supplier_stock_openings');
    }
};
