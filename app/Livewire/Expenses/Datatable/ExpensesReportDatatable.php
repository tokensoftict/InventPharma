<?php

namespace App\Livewire\Expenses\Datatable;

use App\Classes\ExportDataTableComponent;
use App\Models\Purchase;
use App\Repositories\ExpenseRepository;
use App\Traits\SimpleDatatableComponentTrait;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Expense;

class ExpensesReportDatatable extends ExportDataTableComponent
{
    use SimpleDatatableComponentTrait;

    protected $model = Expense::class;

    public array $filters = [];
    public array $additionalSelects = [];

    public function builder(): Builder
    {
        return Expense::query()->select('*')->with(['user', 'expenses_type', 'department'])->filterdata($this->filters)->orderBy('id', 'DESC');
    }


    public static function mountColumn() : array
    {
        return [
            Column::make("Department", "department_id")
                ->format(fn($value, $row, Column $column) =>$row->department->label)
                ->sortable(),
            Column::make("Amount", "amount")
                ->format(fn($value, $row, Column $column) => money($value))
                ->footer(function($rows){
                    return money($rows->sum('amount'));
                })
                ->sortable(),
            Column::make("Expenses Type", "expenses_type_id")
                ->format(fn($value, $row, Column $column) => $row->expenses_type->name),
            Column::make("Expense Date", "expense_date")
                ->format(fn($value, $row, Column $column) => eng_str_date($value)),
            Column::make("Purpose", "purpose")
                ->format(fn($value, $row, Column $column) => $value),
            Column::make("Added By", "user_id")
                ->format(fn($value, $row, Column $column) => $row->user->name ?? ""),
            Column::make("Action","id")
                ->format(function($value, $row, Column $column) {
                    $html = "No Action";

                    if(can(['edit', 'delete'], $row)){

                        $html = '<div class="dropdown"><button class="btn btn-link font-size-16 shadow-none py-0 text-muted dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bx bx-dots-horizontal-rounded"></i></button>';
                        $html .= '<ul class="dropdown-menu dropdown-menu-end">';

                        if (auth()->user()->can("update", $row)) {
                            $html .= '<a href="' . route('expenses.edit', $row->id) . '" class="dropdown-item">Edit Expense</a></li>';
                        }

                        if (auth()->user()->can("delete", $row)) {
                            $html .= '<a href="#" wire:click.prevent="delete('.$value.')"  onclick="confirm(\'Are you sure you want to delete this expense ?, this can not be reversed\') || event.stopImmediatePropagation()"  class="dropdown-item">Delete Expense</a></li>';
                        }

                        $html .= '</ul>';
                    }

                    return $html;
                }) ->html()
        ];
    }



    public function delete(Purchase $purchase)
    {
        ExpenseRepository::destroy($purchase->id);

        $this->alert(
            "success",
            "Expenses",
            [
                'position' => 'center',
                'timer' => 6000,
                'toast' => false,
                'text' =>  "Expense has been deleted successfully!.",
            ]
        );

        return redirect()->route('expenses.index');
    }

}
