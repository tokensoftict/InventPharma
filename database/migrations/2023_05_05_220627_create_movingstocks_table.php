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
        Schema::create('movingstocks', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('stock_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('stockgroup_id')->nullable()->constrained()->cascadeOnDelete();
            $table->decimal('retail_qty')->nullable();
            $table->decimal('no_qty_sold')->nullable();
            $table->decimal('daily_qty_sold')->nullable();
            $table->decimal('average_inventory')->nullable();
            $table->decimal('turn_over_rate')->nullable();
            $table->bigInteger('group_os_id')->nullable();
            $table->integer('is_grouped')->default(0);
            $table->decimal('turn_over_rate2')->nullable();
            $table->integer('lastpurchase_days')->nullable();
            $table->decimal('moving_stocks_constant2',10,5)->nullable();
            $table->string("name")->nullable();
            $table->integer("box")->nullable();
            $table->decimal("threshold",8,2)->nullable();
            $table->integer("cartoon")->nullable();
            $table->string("supplier_name")->nullable();
            $table->string("av_cost_price")->nullable();
            $table->string("av_rt_cost_price")->nullable();
            $table->string("rt_qty")->nullable();
            $table->string("all_qty")->nullable();
            $table->string("tt_av_cost_price")->nullable();
            $table->string("tt_av_rt_cost_price")->nullable();
            $table->date('last_supply_date')->nullable();
            $table->integer('last_supply_quantity')->default(0)->nullable();
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
        Schema::dropIfExists('movingstocks');
    }
};
