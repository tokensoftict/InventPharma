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
        Schema::create('opay_payment_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id')->nullable()->index();
            $table->unsignedBigInteger('creditpaymentlog_id')->nullable()->index();
            $table->unsignedBigInteger('payment_id')->nullable()->index();
            $table->unsignedBigInteger('paymentmethoditem_id')->nullable()->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();

            $table->string('order_no', 64)->nullable()->index();
            $table->string('order_number', 64)->nullable()->index();
            $table->string('transaction_number', 64)->nullable()->index();
            $table->string('terminal_sn', 64)->nullable();
            $table->string('cashier_method', 32)->nullable();
            $table->string('card_type', 32)->nullable();
            $table->string('card_number', 32)->nullable();

            $table->decimal('amount', 20, 2)->default(0);
            $table->unsignedBigInteger('amount_kobo')->default(0);

            $table->string('status', 32)->nullable()->index();
            $table->string('response_code', 16)->nullable();
            $table->string('response_message')->nullable();
            $table->json('raw_payload')->nullable();

            $table->timestamps();

            $table->foreign('invoice_id')->references('id')->on('invoices')->nullOnDelete();
            $table->foreign('creditpaymentlog_id')->references('id')->on('creditpaymentlogs')->nullOnDelete();
            $table->foreign('payment_id')->references('id')->on('payments')->nullOnDelete();
            $table->foreign('paymentmethoditem_id')->references('id')->on('paymentmethoditems')->nullOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opay_payment_logs');
    }
};

