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
use App\Enums\KafkaAction;
use App\Enums\KafkaTopics;
use App\Jobs\PushDataServer;

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

    private array $customer_ids = [];
    public function handle()
    {
        $startDate = $this->option('date');
        $this->info("Starting loyalty points recalculation from $startDate...");

        if (!$this->option('force') && !$this->confirm('This will RESET all loyalty points and DELETE all loyalty transactions. Do you wish to continue?', false)) {
            $this->warn('Operation cancelled.');
            return;
        }

        // Step 1: Capture all customers who currently have points (for sync integrity)
        $this->info('Identifying customers with existing points for synchronization...');
        Customer::where('loyalty_points', '>', 0)
            ->orWhere('retail_loyalty_points', '>', 0)
            ->chunkById(500, function($customers) {
                foreach($customers as $customer) {
                    $this->appendCustomer($customer->id);
                }
            });

        // Step 2: Reset all customers
        $this->info('Resetting customer balances...');
        Customer::query()->update([
            'loyalty_points' => 0,
            'retail_loyalty_points' => 0
        ]);

        // Step 3: Clear transactions
        $this->info('Clearing loyalty transactions...');
        DB::table('loyalty_transactions')->truncate();

        // Step 4: Fetch eligible invoices
        $statuses = [status("Paid"), status("Complete"), status("Dispatched")];
        $invoiceQuery = Invoice::whereIn('status_id', $statuses)
            ->where('invoice_date', '>=', $startDate)
            ->with('customer');

        $totalInvoices = $invoiceQuery->count();
        $this->info("Processing {$totalInvoices} invoices in chunks...");

        $bar = $this->output->createProgressBar($totalInvoices);
        $bar->start();

        $loyaltyService = app(LoyaltyService::class);

        $invoiceQuery->chunkById(200, function($invoices) use ($loyaltyService, $bar) {
            foreach ($invoices as $invoice) {
                if ($invoice->customer) {
                    // We use the existing logic in LoyaltyService
                    $loyaltyService->earnPoints($invoice->customer, $invoice, 'Recalculation');
                    $this->appendCustomer($invoice->customer_id);
                }
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();

        $this->pushCustomerUpdate();

        $this->info('Loyalty points recalculation completed successfully!');
    }


    private function pushCustomerUpdate()
    {
        $allCustomerIds = array_keys($this->customer_ids);
        if (empty($allCustomerIds)) {
            return;
        }

        $this->info("Pushing updates for " . count($allCustomerIds) . " customers to Kafka in batches of 100...");

        // Chunk the customer IDs to avoid large Kafka payloads
        foreach (array_chunk($allCustomerIds, 100) as $chunk) {
            $customers = Customer::whereIn('id', $chunk)->get();
            $chunkData = [];
            foreach ($customers as $customer) {
                /** @var Customer $customer */
                $chunkData[] = $customer->getBulkPushData();
            }

            dispatch(new PushDataServer([
                'KAFKA_ACTION' => KafkaAction::UPDATE_CUSTOMER,
                'KAFKA_TOPICS' => KafkaTopics::GENERAL,
                'action' => 'update', // Changed to update as these are NOT new
                'table' => 'existing_customer',
                'endpoint' => 'customer',
                'data' => $chunkData
            ]));
        }
    }

    private function appendCustomer(int $customer_id)
    {
        // Using associative array keys for O(1) lookups
        $this->customer_ids[$customer_id] = true;
    }
}
