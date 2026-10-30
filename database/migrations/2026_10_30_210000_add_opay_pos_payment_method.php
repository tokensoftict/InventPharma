<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Adds the "OPAY-POS" payment method (id 9): card/transfer/QR/USSD payments
     * collected through the OPay POS terminal connected to the Print Agent.
     */
    public function up(): void
    {
        if (!DB::table('paymentmethods')->where('id', 9)->exists()) {
            DB::table('paymentmethods')->insert(['id' => 9, 'name' => 'OPAY-POS', 'status' => 1]);
        }

        Cache::forget('paymentmethods');
    }

    public function down(): void
    {
        DB::table('paymentmethods')->where('id', 9)->where('name', 'OPAY-POS')->delete();

        Cache::forget('paymentmethods');
    }
};

