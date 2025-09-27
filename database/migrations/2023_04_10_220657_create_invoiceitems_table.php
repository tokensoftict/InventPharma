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
        Schema::create('invoiceitems', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId("invoice_id")->constrained()->cascadeOnDelete();
            $table->foreignId("stock_id")->nullable()->constrained()->nullOnDelete();
            $table->bigInteger("quantity");
            $table->foreignId("customer_id")->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger("added_by");
            $table->unsignedBigInteger("discount_added_by")->nullable();
            $table->decimal("cost_price",20,5)->nullable();
            $table->decimal("selling_price",20,5)->nullable();
            $table->mediumText('department')->nullable();
            $table->decimal("profit",20,5)->nullable();
            $table->enum("discount_type", ['Fixed','Percentage','None'])->nullable(); //['Fixed','Percentage','None']
            $table->decimal("discount_value",20,5)->default(0);
            $table->decimal("discount_amount",20,5)->nullable();
            $table->integer('before_customer_id')->nullable();
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
        Schema::dropIfExists('invoiceitems');
        Schema::enableForeignKeyConstraints();
    }
};
