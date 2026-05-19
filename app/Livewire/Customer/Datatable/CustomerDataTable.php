<?php

namespace App\Livewire\Customer\Datatable;

use App\Classes\BooleanColumn;
use App\Classes\ExportDataTableComponent;
use App\Traits\SimpleDatatableComponentTrait;
use App\Classes\Column;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;


class CustomerDataTable extends ExportDataTableComponent
{
    use SimpleDatatableComponentTrait;

    protected $model = Customer::class;


    public array $filters = [];

    public function builder(): Builder
    {
        if(isset($this->filters['retail_customer']) and $this->filters['retail_customer'] = 1) {
            unset($this->filters['retail_customer']);
            return Customer::query()->select('*')
                ->with(['creditpaymentlog', 'invoice', 'payment', 'memberGroup', 'retailMemberGroup'])
                ->where('retail_customer', 1)
                ->whereHas('invoice', function (Builder $query) {
                    $query->where('in_department', 'retail');
                })
                ->filterdata($this->filters)->filterdata($this->filters);
        }else if (isset($this->filters['retail_customer']) and $this->filters['retail_customer'] = 0) {
            unset($this->filters['retail_customer']);
            return Customer::query()->select('*')->with(['creditpaymentlog', 'invoice', 'payment', 'memberGroup', 'retailMemberGroup'])
                ->where('retail_customer', 0)
                ->whereHas('invoice', function (Builder $query) {
                    $query->where('in_department', 'wholesales');
                })->filterdata($this->filters);
        } else{
           return Customer::query()->select('*')->with(['creditpaymentlog', 'invoice', 'payment', 'memberGroup', 'retailMemberGroup'])->filterdata($this->filters);
        }

    }

    public function refresh($what)
    {

    }

    public static function mountColumn(): array
    {
        return [
            Column::make("Name", 'firstname')
                ->format(fn($value, $row, Column $column) => $row->firstname . ' ' . $row->lastname)
                ->sortable()->searchable(),
            Column::make("Email", "email")
                ->sortable()->searchable(),
            Column::make("Status", "status")
                ->format(function ($value, $row, Column $column) {
                    return '  <div class="form-check form-switch mb-3" dir="ltr">
                                    <input wire:change="toggle(' . $row->id . ')" id="user' . $row->id . '" type="checkbox" class="form-check-input" id="customSwitch1" ' . ($value ? 'checked' : '') . '>
                                    <label class="form-check-label" for="customSwitch1">' . $value ? 'Active' : 'Inactive' . '</label>
                                </div>';
                })->html()
                ->sortable(),
            Column::make("Address", "address")
                ->sortable(),
            Column::make("Phone number", "phone_number")
                ->sortable()->searchable(),
            BooleanColumn::make("Retail customer", "retail_customer")
                ->sortable(),
            Column::make("City", "city.name")
                ->sortable(),
            Column::make("Member Group", "memberGroup.label")
                ->sortable(),
            Column::make("Retail Member Group", "retailMemberGroup.label")
                ->sortable(),
            Column::make("Credit balance", "credit_balance")
                ->format(fn($value, $row, Column $column) => money($value))
                ->sortable(),
            Column::make("Last Payment Date", "id")
                ->format(function ($value, $row, Column $column) {
                    $date = isset($row->payment->payment_date) ? $row->payment->payment_date : false;
                    return $date ? convert_date($date) : "N/A";
                }),
            Column::make("Last Credit Payment Date", "id")
                ->format(function ($value, $row, Column $column) {
                    $date = isset($row->creditpaymentlog->payment_date) ? $row->creditpaymentlog->payment_date : false;
                    return $date ? convert_date($date) : "N/A";
                }),
            Column::make("Last Invoice Date", "id")
                ->format(function ($value, $row, Column $column) {
                    $date = isset($row->invoice->invoice_date) ? $row->invoice->invoice_date : false;
                    return $date ? convert_date($date) : "N/A";
                })
                ->sortable(),
            Column::make("Loyalty Point", "loyalty_points")
                ->sortable(),
            Column::make("Retail Loyalty Point", "retail_loyalty_points")
                ->sortable(),

            Column::make("Action", "id")
                ->format(function ($value, $row, Column $column) {
                    $html = "No Action";

                    if (userCanView('customer.update')) {

                        $html = ' <a class="btn btn-outline-primary btn-sm edit" wire:click="edit(' . $value . ')" href="javascript:void(0);" >

                                        <span wire:loading wire:target="edit(' . $value . ')" class="spinner-border spinner-border-sm me-2" role="status"></span>

                                        <i wire:loading.remove wire:target="edit(' . $value . ')" class="fas fa-pencil-alt"></i>

                                    </a>';
                    }

                    return $html;
                })->html()
        ];
    }



    public function edit($id)
    {
        $this->dispatch('editCustomer', $id);
    }

}
