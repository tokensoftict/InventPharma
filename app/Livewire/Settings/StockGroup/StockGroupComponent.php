<?php

namespace App\Livewire\Settings\StockGroup;

use App\Models\Category;
use App\Models\Classification;
use App\Models\Stockgroup;
use App\Traits\SimpleComponentTrait;
use Livewire\Component;

class StockGroupComponent extends Component
{

    use SimpleComponentTrait;


    public function mount()
    {
        $this->model = Stockgroup::class;
        $this->modalName = "Stock Group";
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
        //$this->cacheModel = "stockgroups";
    }


    public function render()
    {
        return view('livewire.settings.stockgroup.stock-group-component');
    }
}
