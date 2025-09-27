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
        Schema::create('stockbincards', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('stock_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string("bin_card_type",100)->index(); //['APP//RECEIVED','APP//TRANSFER','APP//SOLD','APP//RETURN','APP//DEPARTMENT','APP//BRANCH']
            $table->date("bin_card_date")->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->integer("in_qty")->default(0);
            $table->integer("out_qty")->default(0);
            $table->integer("sold_qty")->default(0);
            $table->integer("return_qty")->default(0);
            $table->foreignId('stockbatch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('to_department', 50)->nullable()->index();
            $table->string('from_department',50)->nullable()->index();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('stocktransfer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('purchase_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('balance')->nullable();
            $table->mediumText('comment')->nullable();
            $table->decimal('department_balance')->nullable();

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
        Schema::dropIfExists('stockbincards');
        Schema::enableForeignKeyConstraints();
    }
};
