<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dependent_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_stock_id');
            $table->foreignId('stock_id')->constrained();
            $table->integer("parent")->default("1");
            $table->integer("child")->default("1");
            $table->timestamps();

            $table->foreign('parent_stock_id')->references('id')->on('stocks')->cascadeOnDelete();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dependent_products');
    }
};
