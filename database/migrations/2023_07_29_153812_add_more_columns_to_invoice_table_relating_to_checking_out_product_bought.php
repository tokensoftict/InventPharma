<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
            Schema::table('invoices', function (Blueprint $table) {
                $table->unsignedBigInteger("scan_user_id")->nullable()->after('retail_printed');
                $table->date("scan_date")->nullable()->after('retail_printed');
                $table->time("scan_time")->nullable()->after('retail_printed');
                $table->foreign('scan_user_id')->references('id')->on('users')->nullOnDelete();;
            });
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign('invoices_scan_user_id_foreign');
            $table->dropColumn(['scan_date', 'scan_time', 'scan_user_id']);
        });
    }
};
