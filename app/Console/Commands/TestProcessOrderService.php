<?php

namespace App\Console\Commands;

use App\Services\Online\ProcessOrderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class TestProcessOrderService extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-process-order-service';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $kafkaMessage = Storage::json('sample.json');
        ProcessOrderService::handle($kafkaMessage);
    }

}
