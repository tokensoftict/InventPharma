<?php

namespace App\Livewire\PurchaseOrder\Datatable;

use App\Classes\ExportDataTableComponent;
use App\Traits\SimpleDatatableComponentTrait;
use Illuminate\Database\Eloquent\Builder;
use App\Classes\Column;
use App\Models\Supplier;

class SupplierBalanceReport extends ExportDataTableComponent
{
    use SimpleDatatableComponentTrait;

    protected $model = Supplier::class;

    public array $filters = [];
    public array $additionalSelects = [];

    public function builder(): Builder
    {
        return Supplier::query()->select('*')->with(['payment','supplier_credit_payment_histories'])->filterdata($this->filters);
    }


    public static function mountColumn() : array
    {
        return [
            Column::make("Name", "name")
                ->sortable(),
            Column::make("Address", "address")
                ->sortable(),
            Column::make("Email", "email")
                ->sortable(),
            Column::make("Phonenumber", "phonenumber")
                ->sortable(),
            Column::make("Status", "status")
                ->sortable(),
            Column::make("Credit Balance", "id")
                ->format(fn($value, $row, Column $column) => money($row->credit_balance))
                ->footer(function($rows){
                    return money($rows->sum('credit_balance'));
                })
                ->sortable(),
            Column::make("Last Payment Date", "id")
                ->format(function($value, $row, Column $column){
                    $date = isset($row->payment->payment_date) ? $row->payment->payment_date : false;
                    return $date ? convert_date($date) : "N/A";
                }),
            Column::make("Action","id")
                ->format(function($value, $row, Column $column) {
                    $html = "No Action";

                    if(userCanView('supplier.update')){

                        $html=' <a class="btn btn-outline-primary btn-sm edit" wire:click="edit('.$value.')" href="javascript:void(0);" >

                                        <span wire:loading wire:target="edit('.$value .')" class="spinner-border spinner-border-sm me-2" role="status"></span>

                                        <i wire:loading.remove wire:target="edit('.$value.')" class="fas fa-pencil-alt"></i>

                                    </a>';
                    }

                    return $html;
                })
                ->html()
        ];
    }

    public function edit($id)
    {
        $this->dispatch('editSupplier', $id);
    }
}
