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
        Schema::create('option_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('option_field_id')->constrained()->cascadeOnDelete();
            $table->boolean('status')->default(true);
            $table->string("name", 150)->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('option_field_values');
    }
};
