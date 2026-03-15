<?php

namespace App\Livewire\Settings\CustomPrice;

use App\Models\CustomPrice;
use App\Traits\SimpleComponentTrait;
use Livewire\Component;

class CustomPriceComponent extends Component
{

    use SimpleComponentTrait;

    public function mount()
    {
        $this->model = CustomPrice::class;

        $this->modalName = "Custom Price";
        $this->data = [
            'name' => ['label' => 'Name', 'type'=>'text'],
            'department' => ['label' => 'Department', 'type'=>'select',
                'options'=> [
                    [
                        'id' => 'retail',
                        'name' => 'Retail Department',
                    ],
                    [
                        'id' => 'wholesale',
                        'name' => 'Wholesales Department',
                    ]
                ]
            ],
        ];

        $this->newValidateRules = [
            'name' => 'required|min:1',
            'department' => 'required|min:1',
        ];

        $this->updateValidateRules = $this->newValidateRules;

        $this->initControls();

    }

    public function render()
    {
        return view('livewire.settings.custom-price.custom-price-component');
    }


    public function toggle_default_price($id)
    {
        $this->modelId = $id;
        $model = $this->model::find($id);

        $models = CustomPrice::query()->where('department',$model->department)->get();
        foreach ($models as $model_) {
            $model_->default_price = false;
            $model_->save();
        }

        $model->default_price = !$model->default_price;
        $model->save();
        $this->dispatch('refreshData',[]);
    }
}
