<?php

namespace App\Livewire\InvoiceAndSales\Datatable;

use App\Models\Invoiceactivitylog;
use App\Models\MultipleInvoiceScanReport;
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

final class MultipleScanInvoiceReport extends PowerGridComponent
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
     * @return Builder<\App\Models\Invoiceactivitylog>
     */
    public function datasource(): Builder
    {
        $this->filters['filters']['between.invoice_date'][0] = (new Carbon($this->filters['filters']['between.invoice_date'][0]));
        $this->filters['filters']['between.invoice_date'][1] = (new Carbon($this->filters['filters']['between.invoice_date'][1]));
        return MultipleInvoiceScanReport::query()->with('invoice', 'user') ->whereBetween('updated_at', $this->filters['filters']['between.invoice_date']);
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
            ],
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
            ->add('invoice_number')
            ->add('scan_date')
            ->add("user.name")
            ->add('scan_time')
            ->add('no_of_items')
            ->add('updated_at');

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
            Column::make('No. of Times',  'no_of_items'),
            Column::make('Scan Date',  'scan_date'),
            Column::make('Last Scan Date',  'updated_at'),
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


    public function actions(MultipleInvoiceScanReport $model): array
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
