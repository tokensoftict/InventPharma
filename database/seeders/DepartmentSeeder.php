<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('departments')->insert(
            [
                [
                    'name'=>'Main Store',
                    'label'=>'Main Store',
                    'status'=>1,
                    'quantity_column'=>'quantity',
                    'type' => 'Carton',
                    'price_column' => 'whole_price',
                    'created_at'=>Carbon::now(),
                    'updated_at'=>Carbon::now(),
                ],

                [
                    'name'=>'WholeSales',
                    'label'=>'Whole Sales Department',
                    'status'=>1,
                    'quantity_column'=>'wholesales',
                    'type' => 'Carton',
                    'price_column' => 'whole_price',
                    'created_at'=>Carbon::now(),
                    'updated_at'=>Carbon::now()
                ],
                [
                    'name'=>'Bulk-Sales',
                    'label'=>'Bulk Sales Department',
                    'status'=>1,
                    'quantity_column'=>'bulksales',
                    'type' => 'Carton',
                    'price_column' => 'whole_price',
                    'created_at'=>Carbon::now(),
                    'updated_at'=>Carbon::now()
                ],
                [
                    'name'=>'Retail',
                    'label'=>'Retail Department',
                    'status'=>1,
                    'quantity_column'=>'retail',
                    'type' => 'Pieces',
                    'price_column' => 'retail_price',
                    'created_at'=>Carbon::now(),
                    'updated_at'=>Carbon::now()
                ],
                [
                    'name'=>'Administrator',
                    'label'=>'Administrative Department',
                    'status'=>1,
                    'quantity_column'=>NULL,
                    'type' => 'Pieces',
                    'price_column' => 'whole_price',
                    'created_at'=>Carbon::now(),
                    'updated_at'=>Carbon::now()
                ]
            ]
        );
    }
}
