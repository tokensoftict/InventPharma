<?php

namespace App\Livewire\AccessControl;

use App\Classes\Settings;
use App\Models\Department;
use App\Models\User;
use App\Models\Usergroup;
use App\Traits\SimpleComponentTrait;
use Livewire\Component;

class UserComponent extends Component
{
    use SimpleComponentTrait;

    public function mount()
    {
        $this->model = User::class;
        $this->modalName = "User";
        $this->data = [
            'name' => ['label' => 'Full Name', 'type'=>'text'],
            'username' => ['label' => 'Username', 'type'=>'text'],
            'phone' => ['label' => 'Phone Number', 'type'=>'text'],
            'email' => ['label' => 'Email Address', 'type'=>'email'],
            'password' => ['label' => 'Password', 'type'=>'password'],
            'department_id' => ['label' => 'Department', 'type'=>'select', 'label_option'=>'label',
                'options'=> departments()->toArray()
            ],
            'usergroup_id' => [
                'label' => 'User Group',
                'type'=>'select',
                'options'=> usergroups()->toArray()
            ],
        ];

        $this->newValidateRules = [
            'name' => 'required|min:3',
            'username' => 'required|unique:users,username',
            'phone' => 'required|min:3',
            'email' => 'required|unique:users,email',
            'password' => 'required|min:6|max:36',
            'usergroup_id' => 'required',
        ];

        $this->updateValidateRules = [
            'name' => 'required|min:3',
            //'username' => 'required|unique:users,username,'.$this->modelId,
            'phone' => 'required|min:3',
            //'email' => 'required|unique:users,email,'.$this->modelId,
            'usergroup_id' => 'required',
        ];

        $this->initControls();

    }


    public function edit($id)
    {
        $this->modelId = $id;

        $data = $this->model::find($id);

        foreach($this->data as $key=>$value)
        {
            $this->formData[$key] = $data->{$key};
        }

        if(isset($this->formData['password'])){
            $this->formData['password']= "";
        }

        $this->modalTitle = "Update";

        $this->saveButton = "Update";

        if(isset($this->editcallback) && count($this->editcallback) > 0)
        {
            foreach ($this->editcallback as $callback)
            {
                $this->$callback();
            }

        }


        $this->dispatch('refreshData',[]);
        $this->dispatch("openModal", []);
    }



    public function get()
    {
        return User::with(['department','usergroup'])->orderBy('name','ASC')->paginate(Settings::$pagination);

    }


    public function render()
    {
        return view('livewire.access-control.user-component');
    }
}
