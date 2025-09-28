<?php

namespace App\Console\Commands;

use App\Enums\KafkaAction;
use App\Models\Creditpaymentlog;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Invoice;
use App\Models\Stock;
use App\Repositories\InvoiceRepository;
use App\Services\Online\ProcessOrderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FetchNewOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:refresh';

    public static array $onlineWholeSalesDepartment = [
        "bulksales",
        "quantity",
        "wholesales",
    ];

    public static array $OnlineStoreIDMapper = [
        'SUPERMARKET' => 6,
        'WHOLESALES' => 5
    ];

    public static array $onlineRetailSalesDepartment = ['retail'];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetching New Order from Website';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $baseurl = 'https://admin.generaldrugcentre.com/';//onlineBase();
        $errors = [];
        Auth::loginUsingId(1);
        $url = $baseurl."api/data/unprocessedorder";

        $contents = _FETCH($url);

        $this->info('Checking for new Order from '.onlineBase());

        $order =  $contents;
        if(is_array($contents)){

            if(count($order) == 0){
                $this->info('No Pending order to process');
                return Command::SUCCESS;
            }

        }

        $order['app_id'] = self::$OnlineStoreIDMapper[$order['store']];
        $order['local_order_id'] = $order['id'];
        ProcessOrderService::handle(['order' => $order, 'action' => KafkaAction::PROCESS_ORDER]);
        return Command::SUCCESS;
    }
}
