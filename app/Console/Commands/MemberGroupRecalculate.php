<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Services\MemberGroupService;
use Illuminate\Console\Command;

class MemberGroupRecalculate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'member-group:recalculate {--force : Force the operation without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate both Retail and Other member groups for all customers based on cumulative sales since 2026-01-03';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('force') && !$this->confirm('This will recalculate member groups for ALL customers. This may trigger many Kafka sync jobs. Continue?', false)) {
            $this->warn('Operation cancelled.');
            return;
        }

        $service = app(MemberGroupService::class);
        $customerCount = Customer::count();

        $this->info("Recalculating member groups for {$customerCount} customers...");

        $bar = $this->output->createProgressBar($customerCount);
        $bar->start();

        Customer::chunk(200, function ($customers) use ($service, $bar) {
            foreach ($customers as $customer) {
                $service->recalculateForCustomer($customer);
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info('Recalculation completed successfully!');
    }
}
