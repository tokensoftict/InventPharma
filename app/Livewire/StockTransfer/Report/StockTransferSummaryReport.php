<?php

namespace App\Livewire\StockTransfer\Report;

use App\Models\Stocktransferitem;
use App\Traits\PowerGridComponentTrait;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use PowerComponents\LivewirePowerGrid\Rules\{Rule, RuleActions};
use PowerComponents\LivewirePowerGrid\Traits\{ActionButton, WithExport};
use PowerComponents\LivewirePowerGrid\Filters\Filter;
use PowerComponents\LivewirePowerGrid\{Button,
    Column,
    Exportable,
    Footer,
    Header,
    PowerGrid,
    PowerGridComponent,
    PowerGridEloquent,
    PowerGridFields};

final class StockTransferSummaryReport extends PowerGridComponent
{
   use PowerGridComponentTrait;

   public array $filters;

   public $key = 'stock_id';
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
     * @return Builder<\App\Models\Stocktransferitem>
     */
    public function datasource(): Builder
    {
        return Stocktransferitem::query()->with(['stock'])->select(
            'stock_id',
            DB::raw( 'SUM(quantity * selling_price) as total_sub_total'),
            DB::raw( 'SUM(selling_price) as cost'),
            DB::raw( 'SUM(quantity) as total_qty')
        )->whereHas('stocktransfer', function ($query){
            $query->where('to',$this->filters['department'])
                ->whereBetween('transfer_date', $this->filters['between.transfer_date']);
                if($this->filters['department'] === "retail") {
                    $query->where('from', '<>', 'retail_store'); // dont include transfer made from retail store to retail
                }
            $query->where('status_id',$this->filters['status_id']);
                $query->orderBy("id","DESC");
        })->groupBy('stock_id');
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
        return [];
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
            ->add('name', fn(Stocktransferitem $stocktransferitem) => $stocktransferitem->stock->name)
            ->add('total_qty')
            ->add('total_sub_total', fn(Stocktransferitem $stocktransferitem) => money($stocktransferitem->total_sub_total))
            ->add('cost', fn(Stocktransferitem $stocktransferitem) => money($stocktransferitem->cost));

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
            Column::make('Name', 'name'),
            Column::make('Quantity', 'total_qty'),
            Column::add()->field('cost')->title('Cost Price'),
            Column::make('Total Cost Price', 'total_sub_total')
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
     * PowerGrid Stocktransferitem Action Buttons.
     *
     * @return array<int, Button>
     */

    /*
    public function actions(): array
    {
       return [
           Button::make('edit', 'Edit')
               ->class('bg-indigo-500 cursor-pointer text-white px-3 py-2.5 m-1 rounded text-sm')
               ->route('stocktransferitem.edit', function(\App\Models\Stocktransferitem $model) {
                    return $model->id;
               }),

           Button::make('destroy', 'Delete')
               ->class('bg-red-500 cursor-pointer text-white px-3 py-2 m-1 rounded text-sm')
               ->route('stocktransferitem.destroy', function(\App\Models\Stocktransferitem $model) {
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
     * PowerGrid Stocktransferitem Action Rules.
     *
     * @return array<int, RuleActions>
     */

    /*
    public function actionRules(): array
    {
       return [

           //Hide button edit for ID 1
            Rule::button('edit')
                ->when(fn($stocktransferitem) => $stocktransferitem->id === 1)
                ->hide(),
        ];
    }
    */
}
