<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AddPreDraftStatusToStatusTable extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $status =  [
            'name'=>'Pre-Draft',
            'label'=>'primary'
        ];
        Cache::forget("statuses");
        DB::table('statuses')->updateOrInsert(['name'=> $status['name']], $status);

    }
}
