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
        Schema::create('failed_kafka_messages', function (Blueprint $table) {
            $table->id();
            $table->string('event', 100);
            $table->string("key", 100);
            $table->json('payload');
            $table->bigInteger('timestamp');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('failed_kafka_messages');
    }
};
