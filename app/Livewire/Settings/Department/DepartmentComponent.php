<?php

namespace App\Livewire\Settings\Department;

use App\Models\Category;
use App\Models\Department;
use App\Traits\SimpleComponentTrait;
use Livewire\Component;

class DepartmentComponent extends Component
{

    use SimpleComponentTrait;


    public function mount()
    {
        $this->model = Department::class;
        $this->modalName = "Department";
        $this->data = [
            'name' => ['label' => 'Department Name', 'type'=>'text'],
            'type' => ['label' => 'Product Type', 'type'=>'select',
                'options'=> [
                    [
                        'id'=>'Carton',
                        'name'=>'Carton'
                    ],
                    [
                        'id'=>'Pieces',
                        'name'=>'Pieces'
                    ]
                ]
            ],
            'quantity_column' => ['label' => 'Quantity Column(Dont Edit)', 'type'=>'text'],
            'price_column' => ['label' => 'Price Type', 'type'=>'select',
                'options'=> [
                    [
                        'id'=>'whole_price',
                        'name'=>'Whole Sales Price'
                    ],
                    [
                        'id'=>'retail_price',
                        'name'=>'Retail Price'
                    ]
                ]
            ],
        ];

        $this->newValidateRules = [
            'name' => 'required|min:1',
        ];

        $this->updateValidateRules = $this->newValidateRules;


        $this->initControls();

    }


    public function booted()
    {
        $this->cacheModel = "departments";
    }



    public function render()
    {
        return view('livewire.settings.department.department-component');
    }
}
