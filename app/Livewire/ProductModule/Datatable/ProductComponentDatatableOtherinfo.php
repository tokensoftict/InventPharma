<?php

namespace App\Livewire\ProductModule\Datatable;

use App\Classes\ExportDataTableComponent;
use App\Classes\Settings;
use App\Models\Stock;
use App\Traits\SimpleDatatableComponentTrait;
use Illuminate\Database\Eloquent\Builder;
use App\Classes\Column;
use App\Classes\BooleanColumn;

class ProductComponentDatatableOtherinfo extends ExportDataTableComponent
{

    use SimpleDatatableComponentTrait;

    protected $model = Stock::class;

    public array $filters = [];


    public function builder(): Builder
    {
        return Stock::query()->select('*')->filterdata($this->filters);

    }

    public static function mountColumn() : array
    {
        return  [
            Column::make("Stock ID", "id")->sortable(),
            Column::make("Name", "name")
                ->sortable()->searchable(),
            Column::make("Re-order", "reorder")
                ->format(function ($value, $row, Column $column) {
                    if (userCanView('product.toggle')){
                        return '<div class="form-check form-switch mb-3" dir="ltr">
                                        <input wire:change="toggleReorder(' . $value . ')" id="user' . $value . '" type="checkbox" class="form-check-input" id="customSwitch1" ' . ($value ? 'checked' : '') . '>
                                        <label class="form-check-label" for="customSwitch1">' . ($value? 'Active' : 'Inactive') . '</label>
                                    </div>';
                    }
                    else {
                        return  $value === 1 ? label('success', 'Active') : label('danger', 'In-active');
                    }
                })->html(),
            Column::make("Category", "category.name")
                ->sortable(),
            Column::make("Manufacturer", "manufacturer.name")
                ->sortable(),
            Column::make("Classification", "classification.name")
                ->sortable(),
            Column::make("Stock Group", "stockgroup.name")
                ->sortable(),
            Column::make("Brand", "brand.name")
                ->sortable(),
            Column::make("Action","id")
                ->format(function($value, $row, Column $column){
                    $html = 'No Action';
                    if(userCanView('product.edit')){
                        $html = '<div class="dropdown"><button class="btn btn-link font-size-16 shadow-none py-0 text-muted dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bx bx-dots-horizontal-rounded"></i></button>';

                        $html .='<ul class="dropdown-menu dropdown-menu-end">';
                        if(userCanView('product.edit')) {
                            $html .= '<a href="' . route('product.edit', $value) . '" class="dropdown-item">Edit Stock</a></li>';
                        }
                        $html .='</ul>';
                    }

                    return $html;
                })
                ->html()
        ];
    }


    public function toggleReorder(Stock $stock)
    {
        $product = $stock;
        $product->reorder = !$product->reorder;
        $product->save();
        $this->dispatch('$refreshData');
    }


}
/*
 *      Column::make("Category", "category.name")
                ->format(fn($value, $row, Column $column)=> isset($row->category->name) ? $row->category->name : "N/A")
                ->sortable()->searchable(),
            Column::make("Group", "stockgroup.name")
                ->format(fn($value, $row, Column $column)=> isset($row->stockgroup->name) ? $row->stockgroup->name : "N/A")
                ->sortable(),

    Column::make("Manufacturer", "manufacturer.name")
                ->format(fn($value, $row, Column $column)=> isset($row->manufacturer->name) ? $row->manufacturer->name : "N/A")
                ->sortable(),
 */
