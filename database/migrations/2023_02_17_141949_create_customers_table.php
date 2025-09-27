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
        Schema::create('customers', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('firstname')->nullable()->index();
            $table->string('lastname')->nullable()->index();
            $table->string('email')->nullable();
            $table->boolean('status')->nullable()->default(1);
            $table->mediumText('address')->nullable();
            $table->string('phone_number')->nullable()->index();
            $table->boolean('retail_customer')->default(0);
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('credit_balance', 20, 5)->default(0);
            $table->decimal('deposit_balance', 20, 5)->default(0);
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
        Schema::dropIfExists('customers');
        Schema::enableForeignKeyConstraints();
    }
};
