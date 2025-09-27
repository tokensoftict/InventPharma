<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $payment_method = [
            ['name'=>"CASH","status"=>1] ,
            ['name'=>"POS","status"=>1] ,
            ['name'=>"TRANSFER","status"=>1] ,
            ['name'=>"CREDIT","status"=>1],
            ['name'=>"DEPOSIT","status"=>0],
            ['name'=>"SPLIT-PAYMENTS","status"=>1],
            ['name'=>"ONLINE-PAYMENT","status"=>1]
        ];

        DB::table('paymentmethods')->insert($payment_method);
    }
}
