<?php

namespace App\Livewire\Expenses\Datatable;

use App\Models\Expense;
use App\Traits\PowerGridComponentTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use PowerComponents\LivewirePowerGrid\{Button,
    Column,
    Exportable,
    Footer,
    Header,
    PowerGrid,
    PowerGridComponent,
    PowerGridEloquent};
use PowerComponents\LivewirePowerGrid\Filters\Filter;
use PowerComponents\LivewirePowerGrid\Rules\{RuleActions};
use PowerComponents\LivewirePowerGrid\Traits\{ActionButton, WithExport};

final class ExpensesDataTable extends PowerGridComponent
{
    use PowerGridComponentTrait;

    public $key = "id";
    public array $filters;

    /*
    |--------------------------------------------------------------------------
    |  Datasource
    |--------------------------------------------------------------------------
    | Provides data to your Table using a Model or Collection
    |
    */

    /**
     * PowerGrid datasource.
     *
     * @return Builder<\App\Models\Expense>
     */
    public function datasource(): Builder
    {
        return Expense::query()->with(['user', 'expenses_type', 'department']);
    }

    /*
    |--------------------------------------------------------------------------
    |  Relationship Search
    |--------------------------------------------------------------------------
    | Configure here relationships to be used by the Search and Table Filters.
    |
    */

    /**
     * Relationship search.
     *
     * @return array<string, array<int, string>>
     */
    public function relationSearch(): array
    {
        return [
            'expenses_type' => [
                'name'
            ],
            'department' => [
                'label'
            ]
        ];
    }

    /*
    |--------------------------------------------------------------------------
    |  Add Column
    |--------------------------------------------------------------------------
    | Make Datasource fields available to be used as columns.
    | You can pass a closure to transform/modify the data.
    |
    | ❗ IMPORTANT: When using closures, you must escape any value coming from
    |    the database using the `e()` Laravel Helper function.
    |
    */
    public function addColumns(): PowerGridEloquent
    {
        return PowerGrid::eloquent()
            ->addColumn('id')
            ->addColumn('amount')
            ->addColumn('department_id')
            ->addColumn('department', fn (Expense $model) => $model->department->label)
            ->addColumn('expenses_type', fn (Expense $model) => $model->expenses_type->name)
            ->addColumn('added_by', fn (Expense $model) => $model->user->name ?? "")
            ->addColumn('department_id_lower', fn (Expense $model) => strtolower(e($model->department_id)))
            ->addColumn('expenses_type_id')
            ->addColumn('user_id')
            ->addColumn('expense_date_formatted', fn (Expense $model) => Carbon::parse($model->expense_date)->format('d/m/Y'))
            ->addColumn('purpose')
            ->addColumn('created_at_formatted', fn (Expense $model) => Carbon::parse($model->created_at)->format('d/m/Y H:i:s'));
    }

    /*
    |--------------------------------------------------------------------------
    |  Include Columns
    |--------------------------------------------------------------------------
    | Include the columns added columns, making them visible on the Table.
    | Each column can be configured with properties, filters, actions...
    |
    */

     /**
      * PowerGrid Columns.
      *
      * @return array<int, Column>
      */
    public function columns(): array
    {
        return [
            Column::add()->index()->title('SN')->visibleInExport(false),
            Column::make('Amount', 'amount')->sortable()->searchable(),
            Column::make('Department', 'department')->sortable()->searchable(),
            Column::make('Expenses Type', 'expenses_type'),
            Column::make('Expense date', 'expense_date_formatted', 'expense_date')->sortable(),
            Column::make('Purpose', 'purpose')->sortable()->searchable(),
            Column::make('Added By', 'added_by'),
        ];
    }

    /**
     * PowerGrid Filters.
     *
     * @return array<int, Filter>
     */
    public function filters(): array
    {
        return [

        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Actions Method
    |--------------------------------------------------------------------------
    | Enable the method below only if the Routes below are defined in your app.
    |
    */

    /**
     * PowerGrid Expense Action Buttons.
     *
     * @return array<int, Button>
     */

    /*
    public function actions(): array
    {
       return [
           Button::make('edit', 'Edit')
               ->class('bg-indigo-500 cursor-pointer text-white px-3 py-2.5 m-1 rounded text-sm')
               ->route('expense.edit', function(\App\Models\Expense $model) {
                    return $model->id;
               }),

           Button::make('destroy', 'Delete')
               ->class('bg-red-500 cursor-pointer text-white px-3 py-2 m-1 rounded text-sm')
               ->route('expense.destroy', function(\App\Models\Expense $model) {
                    return $model->id;
               })
               ->method('delete')
        ];
    }
    */

    /*
    |--------------------------------------------------------------------------
    | Actions Rules
    |--------------------------------------------------------------------------
    | Enable the method below to configure Rules for your Table and Action Buttons.
    |
    */

    /**
     * PowerGrid Expense Action Rules.
     *
     * @return array<int, RuleActions>
     */

    /*
    public function actionRules(): array
    {
       return [

           //Hide button edit for ID 1
            Rule::button('edit')
                ->when(fn($expense) => $expense->id === 1)
                ->hide(),
        ];
    }
    */
}
