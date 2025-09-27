<?php

namespace App\Livewire\Settings\Classification;

use App\Models\Category;
use App\Models\Classification;
use App\Traits\SimpleComponentTrait;
use Livewire\Component;

class ClassificationComponent extends Component
{

    use SimpleComponentTrait;


    public function mount()
    {
        $this->model = Classification::class;
        $this->modalName = "Classifications";
        $this->data = [
            'name' => ['label' => 'Name', 'type'=>'text'],
        ];

        $this->newValidateRules = [
            'name' => 'required|min:1',
        ];

        $this->updateValidateRules = $this->newValidateRules;

        $this->initControls();


    }

    public function booted()
    {
        //$this->cacheModel = "classifications";
    }


    public function render()
    {
        return view('livewire.settings.classification.classification-component');
    }
}
