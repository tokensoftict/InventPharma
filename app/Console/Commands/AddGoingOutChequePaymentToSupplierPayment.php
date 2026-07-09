<?php

namespace App\Console\Commands;

use App\Models\SupplierCreditPaymentHistory;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AddGoingOutChequePaymentToSupplierPayment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:add-going-out-cheque-payment-to-supplier-payment';

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
        $payments = SupplierCreditPaymentHistory::where('paymentmethod_id', 8)->get();

        if ($payments->isEmpty()) {
            $this->info('No cheque payments found to approve.');
            return Command::SUCCESS;
        }


        $count = 0;
        foreach ($payments as $payment) {
            $payment_info = $payment->payment_info ?? [];

            if(isset($payment_info['cheque_date'])) {
                $payment_info['going_out_date'] =  (new Carbon($payment_info["cheque_date"]))->addDay()->format('Y-m-d');
                $payment->payment_info = $payment_info;
                $payment->save();
                $this->line("cheque date is : {$payment_info['cheque_date']}, going out date : {$payment_info['going_out_date']}");
                $count++;
            }
        }

        $this->info("Successfully added $count going_out_date to cheque payments.");

        return Command::SUCCESS;
    }
}
