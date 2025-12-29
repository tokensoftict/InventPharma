<?php

namespace App\Livewire\Settings\ProductOption;


use App\Models\Expense;
use App\Models\ExpensesType;
use App\Models\Option;
use App\Models\OptionField;
use App\Traits\SimpleComponentTrait;
use Livewire\Component;
use App\Traits\LivewireAlert;

class ProductOptionComponent extends Component
{
    use LivewireAlert;
    use SimpleComponentTrait;


    public function mount()
    {
        $this->model = OptionField::class;
        $this->modalName = "Product Option";
        $option = Option::query()->select("id", "type")->get()->map(function ($option) {
           return [
               'id' => $option->id,
               'name' => $option->type,
           ];
        })->toArray();
        $this->data = [
            'name' => ['label' => 'Name', 'type'=>'text'],
            'option_id' => ['label' => 'Option', 'type'=>'select', 'options' => $option],
        ];

        $this->newValidateRules = [
            'name' => 'required|min:1',
            'option_id' => 'required',
        ];

        $this->updateValidateRules = $this->newValidateRules;

        $this->initControls();

    }

    public function render()
    {
        return view('livewire.settings.product-option.product-option-component');
    }
}
