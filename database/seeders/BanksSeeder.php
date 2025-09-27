<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BanksSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $commercial_banks = [
            'Access Bank' => '044',
            'Citibank' => '023',
            'Diamond Bank' => '063',
            'Ecobank' => '050',
            'Fidelity Bank' => '070',
            'First Bank' => '011',
            'First City Monument Bank' => '214',
            'Guaranty Trust Bank' => '058',
            'Heritage Bank' => '030',
            'Keystone Bank' => '082',
            'Mainstreet Bank' => '014',
            'Skye Bank' => '076',
            'Spring Bank' => '084',
            'Stanbic IBTC Bank' => '221',
            'Standard Chartered Bank' => '068',
            'Sterling Bank' => '232',
            'Union Bank' => '032',
            'United Bank for Africa' => '033',
            'Unity Bank' => '215',
            'Wema Bank' => '035',
            'Zenith Bank' => '057',
            'Opay' => '111',
            'Palm Pay' => '222',
            'Monie Point' => '222',
        ];

        $_insert = [];
        foreach ($commercial_banks as $bank => $nibss_code) {
            $_insert[] = [ 'name' => $bank, 'status' => '1', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()];
        }

        DB::table('banks')->insert($_insert);
    }
}
