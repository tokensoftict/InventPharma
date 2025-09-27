<?php

namespace Database\Seeders;


use App\Classes\Statusesclass;
use Illuminate\Database\Seeder;


class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Statusesclass::loadSystemStatus();
    }
}
