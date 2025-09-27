<?php

namespace App\Livewire\ProductModule\Balance;

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

final class StockBalanceBySupplier extends PowerGridComponent
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
        return Stockbatch::with(['stock'])->select(
            'stock_id',
            DB::raw( 'SUM(wholesales) as ws'),
            DB::raw( 'SUM(bulksales) as bs'),
            DB::raw( 'SUM(quantity) as ms'),
            DB::raw( 'SUM(retail) as rt'),
            DB::raw( 'SUM(retail_store) as rts'),
            DB::raw( 'COUNT(retail) as tt_batch')
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
            ->groupBy('stock_id')
            ->filterdata($this->filters);
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
            ->add('name', function(Stockbatch $stockbatch){
                return $stockbatch->stock->name;
            })
            ->add('box', function(Stockbatch $stockbatch){
                return $stockbatch->stock->box;
            })
            ->add('carton', function(Stockbatch $stockbatch){
                return $stockbatch->stock->carton;
            })
            ->add('ws')
            ->add('bs')
            ->add('ms')
            ->add('rt')
            ->add('rts')
            ->add('total', function(Stockbatch $stockbatch) {
                $total = 0;
                if(department_by_quantity_column('wholesales', false)->status) {
                    $total += $stockbatch->ws;
                }
                if(department_by_quantity_column('bulksales', false)->status) {
                    $total += $stockbatch->bs;
                }
                if(department_by_quantity_column('quantity', false)->status) {
                    $total += $stockbatch->ms;
                }
                if(department_by_quantity_column('retail', false)->status) {
                    $total+=round(abs($stockbatch->rt/ $stockbatch->stock->box));
                }

                return $total;
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
            Column::make('Name', 'name')->sortable()->searchable(),
            Column::make('Box', 'box'),
            Column::make('Carton', 'carton')
        ];

        if(department_by_quantity_column('wholesales', false)->status) {
            $columns[] =  Column::make('Wholesales', 'ws');
        }
        if(department_by_quantity_column('bulksales', false)->status) {
            $columns[] =  Column::make('Bulksales', 'bs');
        }
        if(department_by_quantity_column('retail', false)->status) {
            $columns[] =Column::make('Retail', 'rt');
        }
        if(department_by_quantity_column('retail_store', false)->status) {
            $columns[] = Column::make('SuperMarket Store', 'rts');
        }

        if(department_by_quantity_column('quantity', false)->status) {
            $columns[] = Column::make('Main Store', 'ms');
        }

        $columns = array_merge($columns, [
            $columns[] = Column::make('Total', 'total'),
        ]);

        return  $columns;
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
