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
        Schema::create('creditpaymentlogs', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string("credit_number",255)->index();
            $table->foreignId("payment_id")->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId("user_id")->nullable()->constrained()->nullOnDelete();
            $table->foreignId("paymentmethod_id")->nullable()->constrained()->nullOnDelete();
            $table->foreignId("customer_id")->nullable()->constrained()->nullOnDelete();
            $table->foreignId("paymentmethoditem_id")->nullable()->constrained()->cascadeOnDelete();
            $table->nullableMorphs("invoicelog");
            $table->decimal("amount",20,5);
            $table->date("payment_date");
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
        Schema::dropIfExists('creditpaymentlogs');
        Schema::enableForeignKeyConstraints();
    }
};
