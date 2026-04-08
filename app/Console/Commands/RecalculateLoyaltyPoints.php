<?php

namespace App\Console\Commands;

use App\Classes\Settings;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\LoyaltyTransaction;
use App\Models\Status;
use App\Services\LoyaltyService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculateLoyaltyPoints extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'loyalty:recalculate {--date=2026-01-03 : The start date for recalculation} {--force : Force the operation without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset and recalculate loyalty points for all customers based on invoices since a specific date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startDate = $this->option('date');
        $this->info("Starting loyalty points recalculation from $startDate...");

        if (!$this->option('force') && !$this->confirm('This will RESET all loyalty points and DELETE all loyalty transactions. Do you wish to continue?', false)) {
            $this->warn('Operation cancelled.');
            return;
        }

        // Step 1: Reset all customers
        $this->info('Resetting customer balances...');
        Customer::query()->update([
            'loyalty_points' => 0,
            'retail_loyalty_points' => 0
        ]);

        // Step 2: Clear transactions
        $this->info('Clearing loyalty transactions...');
        DB::table('loyalty_transactions')->truncate();

        // Step 3: Fetch eligible invoices
        $statuses = [status("Paid"), status("Complete"), status("Dispatched")];
        $invoices = Invoice::whereIn('status_id', $statuses)
            ->where('invoice_date', '>=', $startDate)
            ->with('customer')
            ->get();

        $this->info("Processing {$invoices->count()} invoices...");

        $bar = $this->output->createProgressBar($invoices->count());
        $bar->start();

        $loyaltyService = app(LoyaltyService::class);

        foreach ($invoices as $invoice) {
            if ($invoice->customer) {
                // We use the existing logic in LoyaltyService
                // Note: LoyaltyService::earnPoints will now handle splitting based on in_department
                $loyaltyService->earnPoints($invoice->customer, $invoice, 'Recalculation');
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Loyalty points recalculation completed successfully!');
    }
}
