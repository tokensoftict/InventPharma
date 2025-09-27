<?php

namespace App\Livewire\PurchaseOrder\Datatable;

use App\Classes\ExportDataTableComponent;
use App\Classes\Settings;
use App\Models\Purchaseitem;
use App\Traits\SimpleDatatableComponentTrait;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Illuminate\Database\Eloquent\Builder;

class PurchaseOrderItemDatatable extends ExportDataTableComponent
{

    use SimpleDatatableComponentTrait;

    public array $filters = [];

    protected $model = Purchaseitem::class;


    public function builder(): Builder
    {
        return Purchaseitem::query()->select('*')->with(['purchase'])->filterdata($this->filters);

    }

    public function columns(): array
    {
       return [
           Column::make("Stock", "stock.name")
               ->format(fn($value, $row, Column $column)=> $value)
               ->sortable(),
           Column::make("Department", "purchase.department")
               ->format(fn($value, $row, Column $column) =>Settings::$department[$value])
               ->sortable(),
           Column::make("Supplier", "purchase.supplier.name")
               ->format(fn($value, $row, Column $column)=> $value)
               ->searchable()
               ->sortable(),
           Column::make("Purchase date", "purchase.date_created")
               ->format(fn($value, $row, Column $column)=> eng_str_date($value))
               ->sortable(),

           Column::make("Cost Price", "cost_price")
               ->format(fn($value, $row, Column $column)=> number_format($row->cost_price,2))
               ->sortable(),
           Column::make("Quantity", "qty")
               ->format(fn($value, $row, Column $column)=> number_format($value,2).$row->unit)
               ->sortable(),
           Column::make("Status", "purchase.status.name")
               ->format(fn($value, $row, Column $column) => showStatus($value))->html()
               ->sortable(),
           Column::make("Completed By", "purchase.complete_by.name")
               ->format(fn($value, $row, Column $column)=> $value)
               ->sortable(),
           Column::make("Action","id")
               ->format(function($value, $row, Column $column){

                   $html = 'No Action';
                   if(can(["edit","view"], $row->purchase)) {
                       $html = '<div class="dropdown"><button class="btn btn-link font-size-16 shadow-none py-0 text-muted dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bx bx-dots-horizontal-rounded"></i></button>';
                       $html .= '<ul class="dropdown-menu dropdown-menu-end">';
                       if(auth()->user()->can('view',$row->purchase)){
                       $html .= '<a href="' . route('purchase.show', $row->purchase_id) . '" class="dropdown-item">View Purchase</a></li>';
                       }
                       if(auth()->user()->can('edit',$row->purchase)){
                       $html .= '<a href="' . route('purchase.edit', $row->purchase_id) . '" class="dropdown-item">Edit Purchase</a></li>';
                       }

                       $html .= '</ul>';
                   }
                   return $html;
               })
               ->html()
       ];
    }


}
