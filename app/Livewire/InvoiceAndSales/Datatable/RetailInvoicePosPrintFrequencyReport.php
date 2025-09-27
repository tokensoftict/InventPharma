<?php

namespace App\Livewire\InvoiceAndSales\Datatable;

use App\Models\Invoiceactivitylog;
use App\Traits\PowerGridComponentTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use PowerComponents\LivewirePowerGrid\{Button,
    Column,
    Exportable,
    Facades\Rule,
    Footer,
    Header,
    PowerGrid,
    PowerGridComponent,
    PowerGridFields};
use Illuminate\Support\Facades\DB;

final class RetailInvoicePosPrintFrequencyReport extends PowerGridComponent
{
    use PowerGridComponentTrait;

    public $key = "user_id";


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
     * @return Builder<\App\Models\Invoiceactivitylog>
     */
    public function datasource(): Builder
    {
        return Invoiceactivitylog::select('user_id', DB::raw('count(invoice_id) as print_count'), 'invoice_id', 'invoice_number')
            ->where('activity', 'LIKE', '%Thermal%')
            ->whereBetween('activity_date', $this->filters['filters']['between.invoice_date'])
            ->having('print_count', '>=', 2)
            ->whereHas('invoice', function($query){
                $query
                    ->where(function($q){
                        $q->orWhere('status_id', status('Complete'))
                            ->orWhere('status_id', status('Paid'));
                    })
                    ->where('in_department', 'retail');
            })

            ->groupBy('user_id', 'invoice_id', 'invoice_number');
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
            'user' => [
                'name'
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
    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('invoice_id')
            ->add('print_count')
            ->add('invoice_number')
            ->add('user_id')
            ->add('username', function (Invoiceactivitylog $log){
                return $log->user->name;
            });

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
            Column::make('Invoice number', 'invoice_number')->sortable(),
            Column::make('Invoice ID', 'invoice_id')->sortable(),
            Column::make('No. of Times',  'print_count'),
            Column::make('User', 'username')->sortable()->searchable(),
            Column::action("Action")
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
     * PowerGrid Invoiceactivitylog Action Buttons.
     *
     * @return array<int, Button>
     */


    public function actions(Invoiceactivitylog $model): array
    {
        return [
            Button::make('view', 'View Invoice')
                ->class('btn btn-primary btn-sm')
                ->route('invoiceandsales.view', [$model->invoice_id])
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Actions Rules
    |--------------------------------------------------------------------------
    | Enable the method below to configure Rules for your Table and Action Buttons.
    |
    */

    /**
     * PowerGrid Invoiceactivitylog Action Rules.
     *
     * @return array<int, RuleActions>
     */


    public function actionRules(): array
    {
        return [

            //Hide button edit for ID 1
            Rule::button('view')
                ->when(fn($invoiceactivitylog) => $invoiceactivitylog->id === 1)
                ->hide(),
        ];
    }

}
