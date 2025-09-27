<?php

namespace App\Livewire\AccessControl;

use App\Models\Module;
use App\Models\Usergroup;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class PermissionComponent extends Component
{


    public Usergroup $usergroup;

    public  $modules;

    public array $privileges = [];

    public function boot()
    {

    }

    public function mount()
    {
        $id = $this->usergroup->id;
        $this->modules = Module::where('status', '=', '1')
            ->with(['tasks','tasks.permissions' => function ($q) use ($id) {
                $q->where('usergroup_id', '=', $id);
            }])
            ->get(['id', 'name','label' ,'icon']);
        foreach ($this->modules as $module)
        {
            foreach ($module->tasks as $task)
            {
                if(count($task->permissions))
                {
                    $this->privileges[$task->id]  = 1;
                }

            }

        }

    }

    public function render()
    {
        return view('livewire.access-control.permission-component');
    }

    public function syncPermission()
    {
       $selectedPrivileges =  Arr::where($this->privileges, function($value, $key){
               return $value == 1;
        });
        $selectedPrivileges = array_keys($selectedPrivileges);

        $this->usergroup->group_tasks()->sync($selectedPrivileges);

        Cache::forget('route-permission-'.$this->usergroup->id);
        loadUserMenu($this->usergroup->id);
        $this->alert(
            "success",
            "Privileges",
            [
                'position' => 'center',
                'timer' => 2000,
                'toast' => false,
                'text' =>  "Privileges has been assigned successfully!.",
            ]
        );

    }

}
