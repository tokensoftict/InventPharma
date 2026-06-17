<?php

namespace App\Console\Commands;

use App\Models\SupplierCreditPaymentHistory;
use Illuminate\Console\Command;

class ApproveChequePayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:approve-cheques';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto approves all existing supplier cheque payments using user_id 1 as the approver';

    /**
     * Execute the console command.
     *
     * @return int
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
            $oldStatus = $payment_info['status'] ?? 'Pending';

            if ($oldStatus !== 'Approved') {
                $payment_info['status'] = 'Approved';
                $payment_info['approved_by'] = 1;
                $payment->payment_info = $payment_info;
                $payment->user_id = 1;
                $payment->save();

                $supplierName = $payment->supplier->name ?? 'N/A';
                $this->line("Approved cheque ID {$payment->id} for supplier '{$supplierName}' (Amount: {$payment->amount}).");
                $count++;
            }
        }

        $this->info("Successfully approved {$count} cheque payments.");

        return Command::SUCCESS;
    }
}
