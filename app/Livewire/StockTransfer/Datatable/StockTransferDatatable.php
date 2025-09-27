<?php

namespace App\Livewire\StockTransfer\Datatable;

use App\Classes\ExportDataTableComponent;
use App\Classes\Settings;
use App\Repositories\StockTransferRepository;
use App\Traits\SimpleDatatableComponentTrait;
use App\Classes\Column;
use App\Models\Stocktransfer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class StockTransferDatatable extends ExportDataTableComponent
{

    use SimpleDatatableComponentTrait;


    protected $model = Stocktransfer::class;

    public array $filters = [];

    public function builder(): Builder
    {
        return Stocktransfer::query()->select('*')->with(['stocktransferitems'])->filterdata($this->filters);
    }


    public static function mountColumn() : array
    {
        return [
            Column::make("Transfer date", "transfer_date")
                ->format(fn($value, $row, Column $column) => eng_str_date($value))
                ->sortable(),
            Column::make("From", "from")
                ->format(fn($value, $row, Column $column) => Settings::$department[$value])
                ->sortable(),
            Column::make("To", "to")
                ->format(fn($value, $row, Column $column) => Settings::$department[$value])
                ->sortable(),
            Column::make("Status", "status.name")
                ->format(fn($value, $row, Column $column) => showStatus($value))->html()
                ->sortable(),
            Column::make("Total Items")
                ->label(
                    fn($row, Column $column) => $row->stocktransferitems->count()
                ),
            Column::make("Transfer By", "user.name")
                ->format(fn($value, $row, Column $column) => $value)
                ->sortable(),
            Column::make("Action","id")
                ->format(function($value, $row, Column $column) {

                    $html = "No Action";

                    if(can(['view', 'create', 'update', 'delete', 'complete'], $row)) {
                        $html = '<div class="dropdown"><button class="btn btn-link font-size-16 shadow-none py-0 text-muted dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bx bx-dots-horizontal-rounded"></i></button>';
                        $html .= '<ul class="dropdown-menu dropdown-menu-end">';

                        if (auth()->user()->can("view", $row)) {
                            $html .= '<a href="' . route('transfer.show', $row->id) . '" class="dropdown-item">View Transfer</a></li>';
                        }

                        if (auth()->user()->can("update", $row)) {
                            $html .= '<a href="' . route('transfer.edit', $row->id) . '" class="dropdown-item">Edit Transfer</a></li>';
                        }

                        if (auth()->user()->can("complete", $row)) {
                            $html .= '<a href="#" wire:click.prevent="complete('.$value.')"  onclick="confirm(\'Are you sure you want to complete this transfer ?, this can not be reversed\') || event.stopImmediatePropagation()" class="dropdown-item">Complete Transfer</a></li>';
                        }

                        if (auth()->user()->can("delete", $row)) {
                            $html .= '<a href="#" wire:click.prevent="delete('.$value.')"  onclick="confirm(\'Are you sure you want to delete this transfer ?, this can not be reversed\') || event.stopImmediatePropagation()"  class="dropdown-item">Delete Transfer</a></li>';
                        }

                    }


                    $html .= '</ul>';
                    return $html;
                }) ->html()
        ];
    }

    public function complete(Stocktransfer $stocktransfer)
    {
        $complete = DB::transaction(function () use($stocktransfer){

           return (new StockTransferRepository())->complete( $stocktransfer);

        });

        if(is_array($complete))
        {
            $this->errors = $complete;

            $this->alert(
                "error",
                "Stock Transfer",
                [
                    'position' => 'center',
                    'timer' => 12000,
                    'toast' => false,
                    'text' =>  "An error occurred while completing this transfer, please check and adjust need items",
                ]
            );

            return false;
        }

        $this->alert(
            "success",
            "Stock Transfer",
            [
                'position' => 'center',
                'timer' => 12000,
                'toast' => false,
                'text' =>  "Stock Transfer has been completed successfully!.",
            ]
        );

        return redirect()->route('transfer.show', $stocktransfer->id);
    }


    public function delete(Stocktransfer $stocktransfer)
    {
       DB::transaction(function() use($stocktransfer){
           (new StockTransferRepository())->delete($stocktransfer);
       });

        $this->alert(
            "success",
            "Stock Transfer",
            [
                'position' => 'center',
                'timer' => 12000,
                'toast' => false,
                'text' =>  "Stock Transfer has been deleted successfully!.",
            ]
        );

        return redirect()->route('transfer.index');
    }

}
