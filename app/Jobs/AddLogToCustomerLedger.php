<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\CustomerLedger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AddLogToCustomerLedger //implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private array $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if($this->data['payment_id'] !== NULL) {
            CustomerLedger::where('payment_id', $this->data['payment_id'] )->delete();
        }
        if($this->data['invoice_id'] !== NULL){
            CustomerLedger::where('invoice_id', $this->data['invoice_id'] )->delete();
        }
        $ledger = CustomerLedger::create($this->data);
        $customer = $this->data['customer_id'];
        $summation = CustomerLedger::where('customer_id', $customer)->sum('amount');
        $ledger->total = $summation;
        $ledger->update();
    }
}
