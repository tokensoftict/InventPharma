<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MemberGroup;

class MemberGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $groups = [
            [
                'name' => 'GOLD',
                'label' => 'GOLD MEMBER',
                'color' => '#D4AF37',
                'bg_color' => '#FFF9E1',
                'min_sales_amount' => 1000000, // Example threshold
                'status' => true,
            ],
            [
                'name' => 'SILVER',
                'label' => 'SILVER MEMBER',
                'color' => '#808080',
                'bg_color' => '#F2F2F2',
                'min_sales_amount' => 500000, // Example threshold
                'status' => true,
            ],
            [
                'name' => 'BRONZE',
                'label' => 'BRONZE MEMBER',
                'color' => '#CD7F32',
                'bg_color' => '#FFF5EB',
                'min_sales_amount' => 0, // Default threshold
                'status' => true,
            ],
        ];

        foreach ($groups as $group) {
            MemberGroup::updateOrCreate(['name' => $group['name']], $group);
        }
    }
}
