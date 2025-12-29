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
        Schema::create('stock_option_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_option_id')->constrained()->cascadeOnDelete();
            $table->foreignId('option_field_value_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stock_id')->constrained();
            $table->foreignId("option_id")->constrained();
            $table->boolean('status')->default(true);

            $table->boolean("retail_status")->default(false);
            $table->decimal("retail_price", 15, 2)->nullable();
            $table->string("retail_price_prefix", 2)->nullable();

            $table->boolean("wholesales_status")->default(false);
            $table->decimal("wholesales_price", 15, 2)->nullable();
            $table->string("wholesales_price_prefix", 2)->nullable();

            $table->boolean("required")->default(false);
            $table->unsignedSmallInteger("quantity")->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_option_values');
    }
};
