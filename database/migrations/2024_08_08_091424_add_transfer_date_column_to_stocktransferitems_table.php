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
        Schema::table('stocktransferitems', function (Blueprint $table) {
            $table->date("transfer_date")->index()->nullable()->after("cost_price");
        });


        //update and set stocktransfers item date to transfer date
       DB::table("stocktransferitems")
           ->select("stocktransfers.transfer_date")
           ->join("stocktransfers", "stocktransfers.id", "=", "stocktransferitems.stocktransfer_id")
           ->update(['stocktransferitems.transfer_date' => DB::raw('stocktransfers.transfer_date')]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stocktransferitems', function (Blueprint $table) {
            $table->dropColumn("transfer_date");
        });
    }
};
