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
        Schema::create('retailnearoutofstock', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('stock_id')->nullable()->constrained()->cascadeOnDelete();
            $table->biginteger("stockgroup_id")->nullable()->constrained()->cascadeOnDelete();
            $table->enum('threshold_type',['NORMAL','THRESHOLD']);
            $table->enum('os_type',['SINGLE','GROUP']);
            $table->integer('qty_to_buy');
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('threshold_value')->nullable();
            $table->decimal('current_qty')->nullable();
            $table->decimal('current_sold')->nullable();
            $table->bigInteger('group_os_id')->nullable();
            $table->boolean('is_grouped')->default(0);
            $table->integer('last_qty_purchased')->nullable();
            $table->date('last_purchase_date')->nullable();
            $table->foreignId('purchaseitem_id')->nullable()->constrained()->nullOnDelete();
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
        Schema::dropIfExists('retailnearoutofstock');
    }
};
