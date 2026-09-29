<?php

namespace App\Livewire\PurchaseLimit;

use App\Models\Customer;
use App\Services\PurchaseLimitService;
use Livewire\Component;

class CustomerUsageComponent extends Component
{
    public Customer $customer;

    public function render()
    {
        $service = new PurchaseLimitService();
        $usage = $service->getCustomerUsageSummary($this->customer->id);

        return view('livewire.purchase-limit.customer-usage-component', [
            'usage' => $usage,
        ]);
    }
}
