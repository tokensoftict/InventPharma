<?php

namespace App\Livewire\Settings\ProductOption;

use App\Classes\Settings;
use App\Models\Option;
use App\Models\OptionField;
use App\Models\OptionFieldValue;
use App\Traits\LivewireAlert;
use App\Traits\SimpleComponentTrait;
use Livewire\Component;

class ProductOptionFieldComponent extends Component
{
    use LivewireAlert;
    use SimpleComponentTrait;

    public OptionField $option;

    public function mount()
    {
        $this->model = OptionFieldValue::class;
        $this->customQuery =
        $this->modalName = "Product Option Field - ".$this->option->name;
        $this->data = [
            'name' => ['label' => 'Name', 'type'=>'text'],
            'option_field_id' => ['value' => $this->option->id, 'type'=>'hidden', 'showValue' => false],
        ];

        $this->newValidateRules = [
            'name' => 'required|min:1|unique:option_field_values,name',
            'option_field_id' => 'required',
        ];

        $this->updateValidateRules = $this->newValidateRules;

        $this->initControls();
    }


    public function custom_query()
    {
        return OptionFieldValue::query()->where('option_field_id', $this->option->id)->paginate(Settings::$pagination);
    }

    public function render()
    {
        return view('livewire.settings.product-option.product-option-field-component');
    }
}
