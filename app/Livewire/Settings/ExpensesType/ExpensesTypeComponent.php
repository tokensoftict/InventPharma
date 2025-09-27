<?php

namespace App\Livewire\Settings\ExpensesType;


use App\Models\Expense;
use App\Models\ExpensesType;
use App\Traits\SimpleComponentTrait;
use Livewire\Component;
use App\Traits\LivewireAlert;

class ExpensesTypeComponent extends Component
{
    use LivewireAlert;
    use SimpleComponentTrait;


    public function mount()
    {
        $this->model = ExpensesType::class;
        $this->modalName = "Expenses Type";
        $this->data = [
            'name' => ['label' => 'Type Name', 'type'=>'text'],
        ];

        $this->newValidateRules = [
            'name' => 'required|min:1',
        ];

        $this->updateValidateRules = $this->newValidateRules;

        $this->initControls();

    }

    public function render()
    {
        return view('livewire.settings.expenses-type.expenses-type-component');
    }
}
