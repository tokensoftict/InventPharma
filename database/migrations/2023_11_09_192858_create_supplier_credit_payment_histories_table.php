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
        Schema::create('supplier_credit_payment_histories', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId("user_id")->nullable()->constrained()->nullOnDelete();
            $table->foreignId("supplier_id")->nullable()->constrained()->cascadeOnDelete();
            $table->string('type', 10)->default('CREDIT');
            $table->foreignId("purchase_id")->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId("paymentmethod_id")->nullable()->constrained()->cascadeOnDelete();
            $table->string('payment_info')->nullable();
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
        Schema::dropIfExists('supplier_credit_payment_histories');
    }
};
