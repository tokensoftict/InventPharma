<?php

namespace App\Livewire\AccessControl;

use App\Models\Usergroup;
use App\Traits\SimpleComponentTrait;
use Livewire\Component;

class UserGroupComponent extends Component
{

    use SimpleComponentTrait;

    public function mount()
    {
        $this->model = Usergroup::class;
        $this->modalName = "User Group";
        $this->data = [
            'name' => ['label' => 'Group Name', 'type'=>'text']
        ];

        $this->newValidateRules = [
            'name' => 'required|min:3',
        ];

        $this->updateValidateRules = $this->newValidateRules;

        $this->initControls();

    }

    public function booted()
    {
        $this->cacheModel = "usergroups";
    }

    public function render()
    {
        return view('livewire.access-control.user-group-component');
    }
}
