<?php

namespace App\Livewire\ProductModule\Expired;

use App\Classes\Settings;
use App\Models\Stockbatch;
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
use PowerComponents\LivewirePowerGrid\Filters\Filter;
use PowerComponents\LivewirePowerGrid\Rules\{RuleActions};
use PowerComponents\LivewirePowerGrid\Traits\{ActionButton, WithExport};

final class NearExpirationStockList extends PowerGridComponent
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
     * @return Builder<\App\Models\Stockbatch>
     */
    public function datasource(): Builder
    {
        $countDays = app(Settings::class)->store()->near_expiry_days;

        $to = date('Y-m-d', strtotime(' + '.$countDays.' days'));
        $from = date('Y-m-d');

        return Stockbatch::with(['stock', 'supplier'])->select(
            'stock_id',
            'expiry_date', 'cost_price', 'retail_cost_price', 'supplier_id',
            DB::raw( 'SUM(bulksales) as bs'),
            DB::raw( 'SUM(wholesales) as ws'),
            DB::raw( 'SUM(quantity) as ms'),
            DB::raw( 'SUM(retail) as rt'),
            DB::raw( 'SUM(retail_store) as rts'),
            DB::raw( 'COUNT(stock_id) as tt_batch'),
        )->whereHas('stock', function ($q) {
            $q->where('status','1');
        })
            ->where(function($q){
                $q->orWhere('wholesales',">",0)
                    ->orWhere('bulksales',">",0)
                    ->orWhere('retail',">",0)
                    ->orWhere('retail_store',">",0)
                    ->orWhere('quantity',">",0);
            })
            ->orderBy('id','DESC')
            ->whereBetween('expiry_date',[$from,$to])
            ->groupBy(['expiry_date', 'stock_id', 'cost_price', 'retail_cost_price', 'supplier_id']);
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
    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('box', function(Stockbatch $stockbatch){
                return $stockbatch->stock->box;
            })
            ->add('stock_id')
            ->add('name', function(Stockbatch $stockbatch){
                return $stockbatch->stock->name;
            })
            ->add('carton', function(Stockbatch $stockbatch){
                return $stockbatch->stock->carton;
            })
            ->add('retail_cost_price', function (Stockbatch $stockbatch){
                return money($stockbatch->retail_cost_price);
            })
            ->add('tt_av_rt_cost_price', function (Stockbatch $stockbatch){
                return money($stockbatch->retail_cost_price * $stockbatch->rt);
            })
            ->add('tt_av_cost_price', function (Stockbatch $stockbatch){
                return money($stockbatch->cost_price * ($stockbatch->bs + $stockbatch->ws + $stockbatch->ms));
            })
            ->add('cost_price', function (Stockbatch $stockbatch){
                return money($stockbatch->cost_price);
            })
            ->add('carton', function(Stockbatch $stockbatch){
                return $stockbatch->stock->carton;
            })
            ->add('expiry_date')
            ->add('supplier_name', function(Stockbatch $stockbatch){
                return $stockbatch->supplier->name ?? "N/A";
            })
            ->add('formatted_expiry_date', function(Stockbatch $stockbatch){
                return $stockbatch->expiry_date->format('d/m/Y');
            })
            ->add('ws')
            ->add('bs')
            ->add('ms')
            ->add('rt')
            ->add('rts')
            ->add('total', function(Stockbatch $stockbatch){
                return $stockbatch->ws + $stockbatch->bs + $stockbatch->ms + round(abs(divide($stockbatch->rt, $stockbatch->stock->box)))+ round(abs(divide($stockbatch->rts, $stockbatch->stock->box)));
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
        $columns =  [
            Column::make('SN' ,'')->index(),
            Column::make('Stock ID', 'stock_id'),
            Column::make('Name', 'name')->sortable()->searchable(),
            Column::make('Box', 'box'),
            Column::make('Carton', 'carton'),
            Column::make('Expiry Date', 'formatted_expiry_date'),
        ];

        if(department_by_quantity_column('wholesales', false)->status) {
            $columns[] = Column::make('Wholesales', 'ws');
        }
        if(department_by_quantity_column('bulksales', false)->status) {
            $columns[] = Column::make('Bulksales', 'bs');
        }

        if(department_by_quantity_column('retail', false)->status) {
            $columns[] = Column::make('Retail', 'rt');
        }

        if(department_by_quantity_column('retail_store', false)->status) {
            $columns[] = Column::make('SuperMarket Store', 'rts');
        }

        if(department_by_quantity_column('quantity', false)->status) {
            $columns[] = Column::make('Main Store', 'ms');
        }


        $columns = array_merge($columns, [
            Column::make('Total', 'total'),
            Column::make('Cost Price', 'cost_price'),
            Column::make('Retail Cost Price', 'retail_cost_price'),
            Column::make('Total Cost', 'tt_av_cost_price'),
            Column::make('Total Retail Cost', 'tt_av_rt_cost_price'),
            Column::make('Supplier', 'supplier_name')
        ]);

        return $columns;
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
               ->route('stockbatch.edit', function(\App\Models\Stockbatch $model) {
                    return $model->id;
               }),

           Button::make('destroy', 'Delete')
               ->class('bg-red-500 cursor-pointer text-white px-3 py-2 m-1 rounded text-sm')
               ->route('stockbatch.destroy', function(\App\Models\Stockbatch $model) {
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
