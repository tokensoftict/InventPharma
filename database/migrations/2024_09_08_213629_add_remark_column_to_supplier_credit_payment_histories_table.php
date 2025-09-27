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
        Schema::table('supplier_credit_payment_histories', function (Blueprint $table) {
            $table->mediumText('remark')->nullable()->after('payment_info');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('supplier_credit_payment_histories', function (Blueprint $table) {
            $table->dropColumn('remark');
        });
    }
};
