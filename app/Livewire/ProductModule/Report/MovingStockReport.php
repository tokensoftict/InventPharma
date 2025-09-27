<?php

namespace App\Livewire\ProductModule\Report;

use App\Models\Movingstock;
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
use PowerComponents\LivewirePowerGrid\Filters\Filter;
use PowerComponents\LivewirePowerGrid\Rules\{RuleActions};
use PowerComponents\LivewirePowerGrid\Traits\{ActionButton, WithExport};

final class MovingStockReport extends PowerGridComponent
{
  use PowerGridComponentTrait;

  public $key = "id";
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
     * @return Builder<\App\Models\Movingstock>
     */
    public function datasource(): Builder
    {
        return Movingstock::query()->with(['stock', 'stock.category']);
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
            ->add('id')
            ->add('category', function (Movingstock $movingstock) {
                return $movingstock->stock->category->name ?? "";
            })
            ->add('stock_id')
            ->add('supplier_id')
            ->add('stockgroup_id')
            ->add('retail_qty')
            ->add('no_qty_sold')
            ->add('daily_qty_sold')
            ->add('average_inventory')
            ->add('turn_over_rate')
            ->add('group_os_id')
            ->add('is_grouped')
            ->add('turn_over_rate2')
            ->add('lastpurchase_days')
            ->add('moving_stocks_constant2')
            ->add('name')
            ->add('box')
            ->add('threshold')
            ->add('cartoon')
            ->add('formatted_carton', function (Movingstock $movingstock){
                return $movingstock->cartoon ?? $movingstock->stock->carton;
            })
            ->add('supplier_name')
            ->add('whole_price', function($movingstock){
                return isset($movingstock->stock->whole_price) ? number_format($movingstock->stock->whole_price) : "";
            })
            ->add('retail_price', function($movingstock){
                return isset($movingstock->stock->retail_price) ? number_format($movingstock->stock->retail_price) : "";
            })
            ->add('av_cost_price')
            ->add('av_rt_cost_price')
            ->add('rt_qty')
            ->add('all_qty')
            ->add('tt_av_cost_price')
            ->add('tt_av_rt_cost_price')
            ->add('last_supply_date', function(Movingstock $movingstock){
                return $movingstock->last_supply_date != NULL ? eng_str_date($movingstock->last_supply_date) : "N/A";
            })
            ->add('last_supply_quantity');
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
            Column::make('Stock ID', 'stock_id')->sortable(),
            Column::make('Name', 'name') ->sortable()->searchable(),
            Column::make('Category', 'category') ->sortable()->searchable(),
            Column::make('Box', 'box'),
            Column::make('Carton', 'formatted_carton'),
            Column::make('Threshold', 'threshold'),
            Column::make('Whole Price', 'whole_price'),
            Column::make('Retail Price', 'retail_price'),
            Column::make('Av.Cost price', 'av_cost_price'),
            Column::make('Av. Rt .Cost price', 'av_rt_cost_price'),
            Column::make('Rt Qty', 'rt_qty'),
            Column::make('WS,MS,BS Qty', 'all_qty'),
            Column::make('Daily Qty Sold', 'daily_qty_sold'),
            Column::make('Av. Inventory', 'average_inventory'),
            Column::make('Turn Over rate', 'turn_over_rate'),
            Column::make('Turn Over rate2', 'turn_over_rate2'),
            Column::make('Worth', 'tt_av_cost_price'),
            Column::make('RT Worth', 'tt_av_rt_cost_price'),
            Column::make('Last Supplier', 'supplier_name') ->sortable()->searchable(),
            Column::make('Last Sup. Date', 'last_supply_date'),
            Column::make('Last Sup. Qty', 'last_supply_quantity'),
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
     * PowerGrid Movingstock Action Buttons.
     *
     * @return array<int, Button>
     */

    /*
    public function actions(): array
    {
       return [
           Button::make('edit', 'Edit')
               ->class('bg-indigo-500 cursor-pointer text-white px-3 py-2.5 m-1 rounded text-sm')
               ->route('movingstock.edit', function(\App\Models\Movingstock $model) {
                    return $model->id;
               }),

           Button::make('destroy', 'Delete')
               ->class('bg-red-500 cursor-pointer text-white px-3 py-2 m-1 rounded text-sm')
               ->route('movingstock.destroy', function(\App\Models\Movingstock $model) {
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
     * PowerGrid Movingstock Action Rules.
     *
     * @return array<int, RuleActions>
     */

    /*
    public function actionRules(): array
    {
       return [

           //Hide button edit for ID 1
            Rule::button('edit')
                ->when(fn($movingstock) => $movingstock->id === 1)
                ->hide(),
        ];
    }
    */
}
