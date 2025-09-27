<?php

namespace App\Livewire\Settings\Brand;

use App\Models\Brand;
use App\Traits\SimpleComponentTrait;
use Livewire\Component;

class BrandComponent extends Component
{

    use SimpleComponentTrait;


    public function mount()
    {
        $this->model = Brand::class;
        $this->modalName = "Brand";
        $this->data = [
            'name' => ['label' => 'Brand Name', 'type'=>'text'],
        ];

        $this->newValidateRules = [
            'name' => 'required|min:1',
        ];

        $this->updateValidateRules = $this->newValidateRules;

        $this->initControls();

    }

    public function booted()
    {
        //$this->cacheModel = "brands";
    }


    public function render()
    {
        return view('livewire.settings.brand.brand-component');
    }
}
