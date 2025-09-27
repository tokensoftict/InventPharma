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
        Schema::create('stocktransfers', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->date("transfer_date")->index();
            $table->foreignId("user_id")->nullable()->constrained()->nullOnDelete();
            $table->string("from", 20)->nullable()->index();
            $table->string("to", 20)->nullable()->index();
            $table->foreignId('status_id')->default(3)->constrained()->cascadeOnDelete();
            $table->mediumText("note")->nullable();
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
        Schema::dropIfExists('stocktransfers');
    }
};
