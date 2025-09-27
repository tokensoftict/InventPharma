<?php

namespace App\Livewire\Settings\Paymentmethod;

use App\Models\Category;
use App\Models\Department;
use App\Models\Manufacturer;
use App\Models\Paymentmethod;
use App\Traits\SimpleComponentTrait;
use Livewire\Component;

class PaymentmethodComponent extends Component
{

    use SimpleComponentTrait;

    public function mount()
    {
        $this->model = Paymentmethod::class;

        $this->modalName = "Payment Method";
        $this->data = [
            'name' => ['label' => 'Payment Method', 'type'=>'text'],
        ];

        $this->newValidateRules = [
            'name' => 'required|min:1',
        ];

        $this->updateValidateRules = $this->newValidateRules;

        $this->initControls();

    }

    public function booted()
    {
        $this->cacheModel = "paymentmethods";
    }

    public function render()
    {
        return view('livewire.settings.paymentmethod.paymentmethod-component');
    }
}
