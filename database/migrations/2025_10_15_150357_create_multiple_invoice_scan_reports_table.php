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
        Schema::create('multiple_invoice_scan_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId("user_id");
            $table->foreignId("invoice_id");
            $table->string("invoice_number", 100)->index();
            $table->date("scan_date")->nullable()->index();
            $table->time("scan_time")->nullable()->index();
            $table->unsignedBigInteger("no_of_items")->default(2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('multiple_invoice_scan_reports');
    }
};
