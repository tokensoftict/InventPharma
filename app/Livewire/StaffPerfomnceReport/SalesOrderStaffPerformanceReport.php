<?php

namespace App\Livewire\StaffPerfomnceReport;

use App\Classes\Settings;
use App\Models\Invoice;
use App\Models\Invoiceitem;
use App\Models\User;
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
    PowerGridEloquent,
    PowerGridFields};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PowerComponents\LivewirePowerGrid\Filters\Filter;
use PowerComponents\LivewirePowerGrid\Rules\{RuleActions};
use PowerComponents\LivewirePowerGrid\Traits\ActionButton;

final class SalesOrderStaffPerformanceReport extends PowerGridComponent
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
     * @return Builder<\App\Models\Invoice>
     */
    public function datasource(): Builder
    {
          $group_id = [7, 6];

        $users = User::whereIn('usergroup_id', $group_id)->pluck('id')->toArray();

        return Invoiceitem::with(['invoice', 'user','user.usergroup','user.department'])
            ->select(
                "invoiceitems.added_by",
                "invoiceitems.added_by as id",
                DB::raw('count(DISTINCT invoice_id) as invoice_count'),
                DB::raw('count(stock_id) as total_sub_total'),
                DB::raw("count(stock_id) as product_count")
            )
            ->whereHas('invoice', function (Builder $query) use(&$users){
                $query->where("in_department", $this->filters['filters']['custom_dropdown_id'])
                    ->whereIn('status_id', [status("Paid"),status("Complete")])
                    //->whereBetween('invoice_date', ['2022-01-01','2022-01-31']);
                    ->whereBetween('invoice_date', $this->filters['filters']['between.invoice_date']);
            })
            ->whereIn('added_by', $users)
            ->orderBy('product_count', 'DESC')
            ->groupBy('invoiceitems.added_by');

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
            'create_by' => [
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
            ->add('fullname', fn(Invoiceitem $invoice) => Str::title($invoice->user->name))
            ->add('group', fn(Invoiceitem $invoice) => Str::upper($invoice->user->usergroup->name))
            ->add('department', fn(Invoiceitem $invoice) =>  Str::upper($invoice->user->department->name))
            ->add('invoice_count')
            ->add('product_count');
            //->addColumn('total_sub_total', fn(Invoiceitem $invoice) => money($invoice->total_sub_total));

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
            Column::make('Name', 'fullname'),
            Column::make('Group', 'group'),
            Column::make('Department', 'department'),
            Column::make('Invoice Count', 'invoice_count'),
            Column::make('Product Count', 'product_count')
           // Column::make('Total', 'total_sub_total'),
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
     * PowerGrid Invoice Action Buttons.
     *
     * @return array<int, Button>
     */

    /*
    public function actions(): array
    {
       return [
           Button::make('edit', 'Edit')
               ->class('bg-indigo-500 cursor-pointer text-white px-3 py-2.5 m-1 rounded text-sm')
               ->route('invoice.edit', ['invoice' => 'id']),

           Button::make('destroy', 'Delete')
               ->class('bg-red-500 cursor-pointer text-white px-3 py-2 m-1 rounded text-sm')
               ->route('invoice.destroy', ['invoice' => 'id'])
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
     * PowerGrid Invoice Action Rules.
     *
     * @return array<int, RuleActions>
     */

    /*
    public function actionRules(): array
    {
       return [

           //Hide button edit for ID 1
            Rule::button('edit')
                ->when(fn($invoice) => $invoice->id === 1)
                ->hide(),
        ];
    }
    */
}
