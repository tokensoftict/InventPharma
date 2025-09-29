<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Models\WaitingCustomer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AddCustomerToWaitingList
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int|null|Invoice $invoice = NULL;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(int | Invoice $invoice)
    {
        $this->invoice = $invoice;
    }
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if(!$this->invoice instanceof Invoice){
            $this->invoice = Invoice::find($this->invoice);
        }

        if (!$this->invoice) return;

        if(!is_null($this->invoice->onliner_order_id)) return; // this is an online invoice

        if($this->invoice->department === 'bulksales') return; //this is still an online invoice

        if($this->invoice->department === 'retail') return; //this is a retail invoice

        if(WaitingCustomer::where('invoice_id', $this->invoice->id)->exists()) return; // invoice has been added already

        $waitingCustomer = WaitingCustomer::create([
            'invoice_id' => $this->invoice->id,
            'customer_id' => $this->invoice->customer_id,
            'date_added' => now()->format('Y-m-d'),
            'invoice_number' => $this->invoice->invoice_number,
            'status' => WaitingCustomer::$waitingInvoiceStatus['picking'],
            'entered_at' => now(),
        ]);
        addCustomerWaitingListStatusHistory($waitingCustomer, 'waiting');
        addCustomerWaitingListStatusHistory($waitingCustomer, 'picking');
    }
}
