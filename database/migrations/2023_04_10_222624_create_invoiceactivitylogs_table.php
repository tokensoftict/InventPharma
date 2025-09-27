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
        Schema::create('invoiceactivitylogs', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId("invoice_id")->constrained()->cascadeOnDelete();
            $table->string("invoice_number")->nullable();
            $table->text('activity')->nullable();
            $table->foreignId("user_id")->nullable()->constrained()->nullOnDelete();
            $table->date("activity_date");
            $table->time("activity_time");
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
        Schema::dropIfExists('invoiceactivitylogs');
        Schema::enableForeignKeyConstraints();
    }
};
