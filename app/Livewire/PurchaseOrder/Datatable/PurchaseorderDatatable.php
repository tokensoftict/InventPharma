<?php

namespace App\Livewire\PurchaseOrder\Datatable;

use App\Classes\ExportDataTableComponent;
use App\Classes\Settings;
use App\Repositories\PurchaseOrderRepository;
use App\Traits\SimpleDatatableComponentTrait;
use Illuminate\Database\Eloquent\Builder;
use App\Classes\Column;
use App\Models\Purchase;

class PurchaseorderDatatable extends ExportDataTableComponent
{
    use SimpleDatatableComponentTrait;

    protected $model = Purchase::class;

    public array $filters = [];


    public array $additionalSelects = [];

    public function builder(): Builder
    {
        return Purchase::query()->select('*')->with(['purchaseitems'])->filterdata($this->filters);
    }


    public static function mountColumn() : array
    {
        return [
            Column::make("Department", "department")
                ->format(fn($value, $row, Column $column) =>Settings::$department[$value])
                ->sortable(),
            Column::make("Supplier", "supplier.name")
                ->sortable(),
            Column::make("Status", "status.name")
                ->format(fn($value, $row, Column $column) => showStatus($value))->html()
                ->sortable(),

            Column::make("Total Purchase")
                ->label(
                    fn($row, Column $column) => money($row->purchaseitems->sum( fn($item)=> ($item->cost_price * $item->qty)))
                ),

            Column::make("Date", "date_created")
                ->format(fn($value, $row, Column $column) => eng_str_date($value))
                ->sortable(),
            Column::make("Created By", "user.name")
                ->format(fn($value, $row, Column $column) => $value)
                ->sortable(),
            Column::make("Action","id")
                ->format(function($value, $row, Column $column) {
                    $html = "No Action";

                    if(can(['view', 'update', 'delete', 'complete'], $row)){

                        $html = '<div class="dropdown"><button class="btn btn-link font-size-16 shadow-none py-0 text-muted dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bx bx-dots-horizontal-rounded"></i></button>';
                        $html .= '<ul class="dropdown-menu dropdown-menu-end">';


                        if (auth()->user()->can("view", $row)) {
                            $html .= '<a href="' . route('purchase.show', $row->id) . '" class="dropdown-item">View Purchase</a></li>';
                        }

                        if (auth()->user()->can("update", $row)) {
                            $html .= '<a href="' . route('purchase.edit', $row->id) . '" class="dropdown-item">Edit Purchase</a></li>';
                        }

                        if (auth()->user()->can("complete", $row)) {
                            $html .= '<a href="#" wire:click.prevent="complete('.$value.')"  onclick="confirm(\'Are you sure you want to complete this purchase ?, this can not be reversed\') || event.stopImmediatePropagation()"  class="dropdown-item">Complete Purchase</a></li>';
                        }

                        if (auth()->user()->can("delete", $row)) {
                            $html .= '<a href="#" wire:click.prevent="delete('.$value.')"  onclick="confirm(\'Are you sure you want to delete this purchase ?, this can not be reversed\') || event.stopImmediatePropagation()"  class="dropdown-item">Delete Purchase</a></li>';
                        }
                        $html .= '</ul>';
                    }

                    return $html;
                }) ->html()
        ];
    }


    public function  complete(Purchase $purchase)
    {
        (new PurchaseOrderRepository())->complete($purchase);

        $this->alert(
            "success",
            "Purchase Order",
            [
                'position' => 'center',
                'timer' => 6000,
                'toast' => false,
                'text' =>  "Purchase Order has been completed successfully!.",
            ]
        );

        return redirect()->route('purchase.show', $purchase->id);

    }


    public function delete(Purchase $purchase)
    {
        (new PurchaseOrderRepository())->delete($purchase);

        $this->alert(
            "success",
            "Purchase Order",
            [
                'position' => 'center',
                'timer' => 6000,
                'toast' => false,
                'text' =>  "Purchase Order has been deleted successfully!.",
            ]
        );

        return redirect()->route('purchase.index');
    }



}
