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
        if (Schema::hasTable('opay_webhooks')) {
            return;
        }

        Schema::create('opay_webhooks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mystore_webhook_log_id')->nullable()->unique();
            $table->unsignedBigInteger('opay_payment_log_id')->nullable()->index();
            $table->unsignedBigInteger('invoice_id')->nullable()->index();

            $table->string('transaction_id', 128)->nullable()->index();
            $table->string('head_merchant_id', 64)->nullable();
            $table->string('merchant_id', 64)->nullable()->index();
            $table->string('out_order_no', 128)->nullable()->index();
            $table->string('order_no', 128)->nullable()->index();
            $table->string('pay_no', 128)->nullable()->index();

            $table->string('status', 32)->nullable()->index();
            $table->string('error_msg', 500)->nullable();

            $table->decimal('amount', 20, 2)->nullable();
            $table->decimal('fee', 20, 2)->nullable();
            $table->string('fee_pattern', 32)->nullable();
            $table->decimal('settlement_amount', 20, 2)->nullable();
            $table->decimal('settlement_fee', 20, 2)->nullable();
            $table->string('settlement_fee_pattern', 32)->nullable();
            $table->string('currency', 16)->default('NGN');
            $table->string('pay_method', 64)->nullable();

            $table->string('sender_name', 255)->nullable();
            $table->string('sender_bank', 128)->nullable();
            $table->string('sender_account', 64)->nullable();
            $table->string('recipient_name', 255)->nullable();
            $table->string('receipt_bank', 128)->nullable();
            $table->string('receipt_account', 64)->nullable();

            $table->string('terminal_sn', 64)->nullable()->index();
            $table->text('remark')->nullable();
            $table->string('is_split', 8)->nullable();
            $table->json('split_info')->nullable();
            $table->text('product_info')->nullable();
            $table->text('additional_information')->nullable();

            $table->string('transaction_time', 32)->nullable();
            $table->string('completed_time', 32)->nullable();

            $table->boolean('signature_verified')->nullable();
            $table->string('source_ip', 64)->nullable();
            $table->timestamp('received_at')->nullable()->index();

            $table->json('headers')->nullable();
            $table->json('raw_payload')->nullable();

            $table->timestamps();

            if (Schema::hasTable('opay_payment_logs')) {
                $table->foreign('opay_payment_log_id')
                    ->references('id')
                    ->on('opay_payment_logs')
                    ->nullOnDelete();
            }

            if (Schema::hasTable('invoices')) {
                $table->foreign('invoice_id')
                    ->references('id')
                    ->on('invoices')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opay_webhooks');
    }
};
