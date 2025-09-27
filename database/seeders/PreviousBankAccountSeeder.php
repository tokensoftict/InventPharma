<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PreviousBankAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $bank = array(
            array('id' => '1','bank_id'=>21 ,'account_number' => '00','account_name' => 'ZENITH WS','status' => '1'),
            array('id' => '2','bank_id'=>7  ,'account_number' => '00','account_name' => 'FCMB WS','status' => '1'),
            array('id' => '3','bank_id'=>16 ,'account_number' => '00','account_name' => 'STERLING WS','status' => '1'),
            array('id' => '4', 'bank_id'=>21,'account_number' => '00','account_name' => 'ZENITH RT','status' => '0'),
            array('id' => '5','bank_id'=>7,'account_number' => '00','account_name' => 'FCMB RT','status' => '0',),
            array('id' => '6','bank_id'=>16,'account_number' => '00','account_name' => 'STERLING RT','status' => '1'),
            array('id' => '7', 'bank_id'=>8,'account_number' => '00','account_name' => 'GTB WS','status' => '1',),
            array('id' => '8','bank_id'=>8,'account_number' => '00','account_name' => 'GTB RT','status' => '1',)
        );


        DB::table('bank_accounts')->insert($bank);

    }
}
