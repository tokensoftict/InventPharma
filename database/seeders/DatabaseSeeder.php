<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Classes\Settings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run(Settings $settings)
    {
        $this->call(BanksSeeder::class);
        $this->call(GroupSeeder::class);
        Artisan::call('task:generate');
        $this->call(PermissionSeeder::class);
        $this->call(CustomerTableSeeder::class);
        $this->call(StatusSeeder::class);
        $this->call(PaymentMethodSeeder::class);
        $this->call(DepartmentSeeder::class);
        $this->call(UserSeeder::class);

        $Systemconfig = [
            "name" => "TOKENSOFT INVENTORY",
            "branch_name" => null,
            "first_address" => null,
            "second_address" => null,
            "contact_number" => null,
            "footer_notes" => null,
            "threshold_days" => "30",
            "near_expiry_days" => "180",
            "system_status" => "okay",
            "supply_days" => "20",
            "nearos_status" => "okay",
            "retail_nearos_status" => "okay",
            "qty_to_buy_threshold" => "0.25",
            "moving_stocks_ndays" => 30,
            "moving_stocks_constant" => "1666.66000",
            "moving_stocks_constant2" => "45454.54500",
            "moving_stocks_run_status" => "okay",
            "total_moving_to_process" => 0,
            "moving_stock_last_run" => now()->toDateTimeString(),
            "m_run_moving_stock" => "okay",
            "total_moving_processed" => 0,
            "last_backup_date" => "2021-07-23",
            "retail_qty_to_buy_threshold" => "0.25",
            "retail_threshold_days" => "30",
            "retail_supply_days" => 3,
            "tax" => "0.00",
            "logo" => "1659902910.png",
            "nearos_last_run" => now()->toDateTimeString(),
            "retail_nearos_last_run" => now()->toDateTimeString(),
            "m_run_nears" => "okay",
            "m_retail_run_nears" => "okay"
        ];
        $settings->put($Systemconfig);

    }
}
