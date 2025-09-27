<?php

namespace App\Livewire\InvoiceAndSales;

use App\Models\WaitingCustomer;
use Livewire\Component;
use App\Traits\LivewireAlert;

class WaitingListScreen extends Component
{
    use LivewireAlert;

    public static array $dispatchArea = [
        'Whole Sales Department' => 'Upstairs',
        'Main Store' => 'Downstairs',
    ];

    public function render()
    {
        return view('livewire.invoice-and-sales.waiting-list-screen', [
            'waitingList' => WaitingCustomer::with('invoice.customer')
                ->where('date_added', now()->format('Y-m-d'))
                ->where('status', '<>', WaitingCustomer::$waitingInvoiceStatus['complete'])
                ->orderBy('entered_at')
                ->get(),
            'serverTime' => now()->timestamp, // current server time in seconds
        ]);
    }
}
