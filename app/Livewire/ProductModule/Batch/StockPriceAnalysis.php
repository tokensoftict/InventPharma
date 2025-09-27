<?php

namespace App\Livewire\ProductModule\Batch;

use App\Models\Stockbatch;
use App\Traits\PowerGridComponentTrait;
use Illuminate\Support\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use PowerComponents\LivewirePowerGrid\Rules\{Rule, RuleActions};
use PowerComponents\LivewirePowerGrid\Filters\Filter;
use PowerComponents\LivewirePowerGrid\Traits\ActionButton;
use PowerComponents\LivewirePowerGrid\{Button, Column, Exportable, Footer, Header, PowerGrid, PowerGridComponent, PowerGridEloquent};

final class StockPriceAnalysis extends PowerGridComponent
{
    use PowerGridComponentTrait;


    public $key = 'stockbatches.stock_id';

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
     * @return Builder<\App\Models\Stockbatch>
     */
    public function datasource(): Builder
    {
        return Stockbatch::with(['stock'])->select(
            'stockbatches.stock_id',
            //DB::raw('sb.supplier_id'),
            DB::raw( 'SUM(stockbatches.wholesales) as ws'),
            DB::raw( 'SUM(stockbatches.bulksales) as bs'),
            DB::raw( 'SUM(stockbatches.quantity) as ms'),
            DB::raw( 'SUM(stockbatches.retail) as rt'),
            DB::raw( 'COUNT(stockbatches.retail) as tt_batch'),
            DB::raw( 'SUM((stockbatches.wholesales+stockbatches.bulksales+stockbatches.quantity) * (stockbatches.cost_price)) as total_cost_total'),
            DB::raw( 'SUM(stockbatches.retail * (stockbatches.retail_cost_price)) as total_rt_cost_total')
        )
           // ->join('stockbatches as sb', 'stockbatches.stock_id','=','sb.stock_id')
            ->groupBy('stock_id');
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
            'stock' => [
                'name',
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

            ->addColumn('stock_id')
            ->addColumn('supplier', function(Stockbatch $stockbatch){
                return $stockbatch->last_supplier->name ?? "N/A";
            })
            ->addColumn('name', function(Stockbatch $stockbatch){
                return $stockbatch->stock->name;
            })
            ->addColumn('box', function(Stockbatch $stockbatch){
                return $stockbatch->stock->box;
            })
            ->addColumn('selling_price', function(Stockbatch $stockbatch){
                return money($stockbatch->stock->whole_price);
            })
            ->addColumn('retail_selling_price', function(Stockbatch $stockbatch){
                return money($stockbatch->stock->retail_price);
            })
            ->addColumn('tt_qty', function(Stockbatch $stockbatch){
                return $stockbatch->bs+$stockbatch->ws+$stockbatch->ms+round(abs((divide($stockbatch->rt,$stockbatch->stock->box))));
            })
            ->addColumn('ws')
            ->addColumn('av_retail_cost_price', function(Stockbatch $stockbatch){
                if($stockbatch->total_rt_cost_total == 0) return  number_format(0,2);
               return money((abs( $stockbatch->total_rt_cost_total/($stockbatch->rt))));
            })
            ->addColumn('av_cost', function(Stockbatch $stockbatch){
                if($stockbatch->total_cost_total == 0) return  number_format(0,2);
               return  money((abs( $stockbatch->total_cost_total/($stockbatch->ms+$stockbatch->bs+$stockbatch->ws))));
            })
            ->addColumn('bs')
            ->addColumn('ms')
            ->addColumn('rt')
            ->addColumn('rtbox', function(Stockbatch $stockbatch){
                return divide($stockbatch->rt,$stockbatch->stock->box);
            })
            ->addColumn('tt_batch')
            ->addColumn('total_cost_total')
            ->addColumn('total_rt_cost_total');

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
            Column::make('SN', '')->index(),
            Column::make('Name', 'name')->searchable(),
            Column::make('Box', 'box'),
            Column::make('Retail Qty', 'rt'),
            Column::make('Retail Qty(Box)', 'rtbox'),
            Column::make('Ws Qty', 'ws'),
            Column::make('MS Qty', 'ms'),
            Column::make('Total Qty', 'tt_qty'),
            Column::make('Selling Price', 'selling_price'),
            Column::make('Retail Selling Price', 'retail_selling_price'),
            Column::make('Av. Rt. Cost Price', 'av_retail_cost_price'),
            Column::make('Av. Cost Price', 'av_cost'),
            Column::make('Last Supplier', 'supplier'),
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
     * PowerGrid Stockbatch Action Buttons.
     *
     * @return array<int, Button>
     */

    /*
    public function actions(): array
    {
       return [
           Button::make('edit', 'Edit')
               ->class('bg-indigo-500 cursor-pointer text-white px-3 py-2.5 m-1 rounded text-sm')
               ->route('stockbatch.edit', ['stockbatch' => 'id']),

           Button::make('destroy', 'Delete')
               ->class('bg-red-500 cursor-pointer text-white px-3 py-2 m-1 rounded text-sm')
               ->route('stockbatch.destroy', ['stockbatch' => 'id'])
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
     * PowerGrid Stockbatch Action Rules.
     *
     * @return array<int, RuleActions>
     */

    /*
    public function actionRules(): array
    {
       return [

           //Hide button edit for ID 1
            Rule::button('edit')
                ->when(fn($stockbatch) => $stockbatch->id === 1)
                ->hide(),
        ];
    }
    */
}
