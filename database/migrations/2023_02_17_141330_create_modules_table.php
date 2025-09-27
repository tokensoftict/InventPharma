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
        Schema::create('modules', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('name')->unique()->index();
            $table->string('label')->nullable();
            $table->string('description')->nullable();
            $table->boolean('status')->default(1)->comment('0=disabled, 1=enabled');
            $table->boolean('visibility')->default(0)->comment('0=not shown in navigation, 1=shown');
            $table->integer('order')->unsigned()->default(0);
            $table->string('icon', 50)->nullable();
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
        Schema::dropIfExists('modules');
        Schema::enableForeignKeyConstraints();

    }
};
