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
        Schema::create('stockopenings', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId("stock_id")->constrained()->cascadeOnDelete();
            $table->decimal('average_retail_cost_price')->nullable();
            $table->decimal('average_cost_price')->nullable();
            $table->integer('wholesales')->nullable()->index();
            $table->integer('bulksales')->nullable()->index();
            $table->integer('retail')->nullable()->index();
            $table->integer('quantity')->nullable()->index();
            $table->foreignId("supplier_id")->nullable()->constrained()->nullOnDelete();
            $table->integer('total')->nullable();
            $table->date('date_added')->nullable();
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
        Schema::dropIfExists('stockopenings');
        Schema::enableForeignKeyConstraints();
    }
};
