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
        Schema::create('departments', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('name',50)->unique();
            $table->string('label',100)->nullable();
            $table->string('quantity_column',50)->nullable()->index();
            $table->string('price_column',50)->nullable()->index();
            $table->enum('type', ['Carton','Pieces'])->nullable();
            $table->boolean('status')->default(1)->index()->comment('0=disabled, 1=enabled');
            $table->softDeletes();
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
        Schema::dropIfExists('departments');
        Schema::enableForeignKeyConstraints();
    }
};
