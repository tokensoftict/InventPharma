<?php

namespace App\Livewire\Settings\MemberGroup;

use App\Models\Classification;
use App\Models\MemberGroup;
use App\Traits\SimpleComponentTrait;
use Livewire\Component;

class MemberGroupComponent extends Component
{

    use SimpleComponentTrait;

    public function mount()
    {
        $this->model = MemberGroup::class;
        $this->modalName = "Classifications";
        $this->data = [
            'name' => ['label' => 'Name', 'type'=>'text'],
            'label' => ['label' => 'Label', 'type'=>'text'],
            'color' => ['label' => 'Other Color', 'type'=>'color'],
            'bg_color' => ['label' => 'Other Background Color', 'type'=>'color'],
            'min_sales_amount' => ['label' => 'Other Min. Sales Amount', 'type'=>'text'],
            'retail_color' => ['label' => 'Retail Color', 'type'=>'color'],
            'retail_bg_color' => ['label' => 'Retail Background Color', 'type'=>'color'],
            'retail_min_sales_amount' => ['label' => 'Retail Min. Sales Amount', 'type'=>'text'],
        ];

        $this->newValidateRules = [
            'name' => 'required|min:1',
            'label' => 'required|min:1',
            'color' => 'required|min:1',
            'bg_color' => 'required|min:1',
            'min_sales_amount' => 'required|min:1',
            'retail_color' => 'required|min:1',
            'retail_bg_color' => 'required|min:1',
            'retail_min_sales_amount' => 'required|min:1',
        ];

        $this->updateValidateRules = $this->newValidateRules;

        $this->initControls();


    }

    public function render()
    {
        return view('livewire.settings.member-group.member-group-component');
    }
}
