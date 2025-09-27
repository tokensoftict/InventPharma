<?php

namespace App\Livewire\AccessControl\User;


use App\Classes\ExportDataTableComponent;
use App\Traits\LivewireAlert;
use App\Traits\SimpleDatatableComponentTrait;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;
use App\Classes\Column;

class UserDatatable extends ExportDataTableComponent
{

    use SimpleDatatableComponentTrait, LivewireAlert;

    protected $model = User::class;


    public array $filters = [];

    public function builder(): Builder
    {
        return User::query()->select('*')->filterdata($this->filters);

    }

    public static function  mountColumn() : array
    {
        return [
            Column::make("Name", "name")
                ->sortable()->searchable(),
            Column::make("Email Address", "email")
                ->sortable()->searchable(),
            Column::make("Username", "username")
                ->sortable()->searchable(),
            Column::make("Group", "usergroup.name")
                ->sortable()->searchable(),
            Column::make("Status", "status")
                ->format(function($value, $row, Column $column){
                    if(userCanView('user.toggle')) {
                       return '<div class="form-check form-switch mb-3" dir="ltr">
                                    <input wire:change="toggle('.$row->id.')" id="user{{ $classification->id }}" type="checkbox" class="form-check-input" id="customSwitch1" '.($value ? 'checked' : '').'>
                                    <label class="form-check-label" for="customSwitch1">'.($value ? 'Active' : 'Inactive' ).'</label>
                                </div>';
                    }else{
                             return  $value ? 'Active' : 'Inactive';
                      }
                })->html()
                ->sortable(),
            Column::make("Phone Number", "phone")
                ->sortable(),
            Column::make("Department", "department.label")
                ->sortable(),
            Column::make("Created", "created_at")
                ->sortable(),
            Column::make("Action","id")
                ->format(function($value, $row, Column $column){
                    $html = "No Action";
                    if (userCanView('user.edit')) {
                        $html = '<div class="dropdown"><button class="btn btn-link font-size-16 shadow-none py-0 text-muted dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bx bx-dots-horizontal-rounded"></i></button>';
                        $html .= '<ul class="dropdown-menu dropdown-menu-end">';
                            $html .= '<li><a href="#" wire:click.prevent="edit('.$row->id.')" class="dropdown-item">Edit User</a></li>';
                        $html .= '</ul></div>';
                    }
                    return $html;
                })->html()
        ];

    }


    public function toggle(User $user)
    {
        $user->status = !$user->status;
        $user->update();
    }


    public function edit($id)
    {
        $this->dispatch('editData', $id);

    }

    /*
    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->sortable(),
            Column::make("Name", "name")
                ->sortable(),
            Column::make("Email", "email")
                ->sortable(),
            Column::make("Email verified at", "email_verified_at")
                ->sortable(),
            Column::make("Usergroup id", "usergroup_id")
                ->sortable(),
            Column::make("Status", "status")
                ->sortable(),
            Column::make("Phone", "phone")
                ->sortable(),
            Column::make("Username", "username")
                ->sortable(),
            Column::make("Department id", "department_id")
                ->sortable(),
            Column::make("Created at", "created_at")
                ->sortable(),
            Column::make("Updated at", "updated_at")
                ->sortable(),
        ];
    }
    */
}
