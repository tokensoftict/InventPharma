<?php

namespace App\Livewire\Settings\Manufacturer;

use App\Models\Category;
use App\Models\Department;
use App\Models\Manufacturer;
use App\Traits\SimpleComponentTrait;
use Livewire\Component;

class ManufacturerComponent extends Component
{

    use SimpleComponentTrait;


    public function mount()
    {
        $this->model = Manufacturer::class;
        $this->modalName = "Manufacturer";
        $this->data = [
            'name' => ['label' => 'Manufacturer Name', 'type'=>'text'],
        ];

        $this->newValidateRules = [
            'name' => 'required|min:1',
        ];

        $this->updateValidateRules = $this->newValidateRules;

        $this->initControls();

    }

    public function booted()
    {
        //$this->cacheModel = "manufacturers";
    }


    public function render()
    {
        return view('livewire.settings.manufacturer.manufacturer-component');
    }
}
