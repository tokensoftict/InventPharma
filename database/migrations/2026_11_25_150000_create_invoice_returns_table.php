<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('invoice_returns')) {
            Schema::create('invoice_returns', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('invoice_id')->index();
                $table->string('invoice_number')->nullable()->index();
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->string('department')->nullable()->index();
                $table->unsignedInteger('return_sequence')->default(1);

                $table->decimal('original_invoice_amount', 20, 5)->default(0);
                $table->decimal('current_invoice_amount', 20, 5)->default(0);
                $table->decimal('return_difference', 20, 5)->default(0);

                $table->string('return_reason');
                $table->text('other_reason_explanation')->nullable();

                $table->boolean('is_customer_refunded')->default(false)->index();
                $table->string('refund_status', 40)->default('Not Refunded')->index();
                $table->decimal('refund_amount', 20, 5)->default(0);
                $table->unsignedBigInteger('paymentmethod_id')->nullable()->index();
                $table->string('refund_payment_method', 40)->nullable()->index();
                $table->unsignedBigInteger('bank_account_id')->nullable()->index();

                $table->unsignedBigInteger('processed_by')->index();
                $table->date('return_date')->index();
                $table->dateTime('processed_at')->index();
                $table->string('idempotency_key', 100)->nullable()->unique();
                $table->json('returned_items_snapshot')->nullable();
                $table->timestamps();
            });
        } elseif (!Schema::hasColumn('invoice_returns', 'paymentmethod_id')) {
            Schema::table('invoice_returns', function (Blueprint $table) {
                $table->unsignedBigInteger('paymentmethod_id')->nullable()->index()->after('refund_amount');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_returns');
    }
};
