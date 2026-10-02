<?php

namespace App\Livewire\Settings\CustomerGroup;

use App\Models\CustomerGroup;
use App\Traits\SimpleComponentTrait;
use Livewire\Component;

class CustomerGroupComponent extends Component
{

    use SimpleComponentTrait;


    public function mount()
    {
        $this->model = CustomerGroup::class;
        $this->modalName = "Customer Group";
        $this->data = [
            'name' => ['label' => 'Name', 'type'=>'text'],
            'description' => ['label' => 'Description', 'type'=>'textarea'],
        ];

        $this->newValidateRules = [
            'name' => 'required|min:3',
        ];

        $this->updateValidateRules = $this->newValidateRules;

        $this->initControls();

    }

    public function booted()
    {
        //$this->cacheModel = "customer_groups";
    }


    public function render()
    {
        return view('livewire.settings.customer-group.customer-group-component');
    }
}
