<?php

namespace App\Console\Commands;

use App\Classes\Settings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class RunCommandsManually extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'run:commands';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $settings = app(Settings::class);

        if($settings->get('m_run_nears') === "run"){
            $settings->put("m_run_nears", 'running');
            $settings->put("nearos_status", 'okay');
            $this->info("Near OS is now running");
            Artisan::call('nearos:compute');

        }

        // this handle running of
        if($settings->get('m_retail_run_nears') === "run"){
            $settings->put("m_retail_run_nears", 'running');
            $settings->put("retail_nearos_status", 'okay');

            $this->info("Retail Near OS is now running");

            Artisan::call('retailnearos:compute');
        }

        if($settings->get('m_run_moving_stock') === "run"){
            $settings->put("m_run_moving_stock", 'running');
            $settings->put("moving_stocks_run_status", 'okay');

            $this->info("Moving Stock is now running");

            Artisan::call('run:movingstocks');

        }



        return Command::SUCCESS;
    }
}
