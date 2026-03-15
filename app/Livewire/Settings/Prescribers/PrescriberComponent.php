<?php

namespace App\Livewire\Settings\Prescribers;

use App\Models\Paymentmethod;
use App\Models\Prescriber;
use App\Traits\SimpleComponentTrait;
use Livewire\Component;

class PrescriberComponent extends Component
{
    use SimpleComponentTrait;

    public function mount()
    {
        $this->model = Prescriber::class;

        $this->modalName = "Prescriber";
        $this->data = [
            'name' => ['label' => 'Name', 'type'=>'text'],
            'phone' => ['label' => 'Phone', 'type'=>'text'],
            'company' => ['label' => 'Company', 'type'=>'text'],
            'address' => ['label' => 'Address', 'type'=>'text'],
        ];

        $this->newValidateRules = [
            'name' => 'required|min:1',
        ];

        $this->updateValidateRules = $this->newValidateRules;

        $this->initControls();

    }



    public function render()
    {
        return view('livewire.settings.prescribers.prescriber-component');
    }
}
