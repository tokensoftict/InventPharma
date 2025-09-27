<?php

namespace App\Livewire\ProductModule\Batch;

use App\Models\Stock;
use App\Traits\PowerGridComponentTrait;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
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

final class BatchedStockListReport extends PowerGridComponent
{
    use PowerGridComponentTrait;

    public $key = 'id';

    public array $filters;

    public $quantity_col = "";

    /*
    |--------------------------------------------------------------------------
    |  Features Setup
    |--------------------------------------------------------------------------
    | Setup Table's general features
    |
    */


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
     * @return Builder<\App\Models\Batchstock>
     */
    public function datasource(): Builder
    {
        $filter_stock = [
            'wholesales',
            'retail',
            'bulksales',
            'retail_store'
        ];

        $price_column = [
            'wholesales'=>'whole_price',
            'retail'=>'retail_price',
            'bulksales'=> 'bulk_price',
            'retail_store' => 'retail_store'
        ];

        $quantity_column = [
            'wholesales'=>'wholesales',
            'retail'=>'retail',
            'bulksales'=> 'bulksales',
            'quantity'=> 'quantity',
            'retail_store' => 'retail_store'
        ];

        $this->quantity_col = $quantity_column[$this->filters['department']];


        $stocks =  Stock::query()
            ->with(['batchstock'])
            ->whereHas('batchstock', function ($batchedstock){
                $batchedstock->where($this->quantity_col, '=', 0);
            })->where('status', 1);

        if($this->filters['department'] == "quantity"){
            $stocks->where($price_column['wholesales'],">",0);
        }

        $stocks->orderBy('batched','ASC');

        return $stocks;
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
            ->add('name')
            ->add('retail_price', fn(Stock $stock) => money($stock->retail_price))
            ->add('bulk_price' , fn(Stock $stock) => money($stock->bulk_price))
            ->add('whole_price', fn(Stock $stock) => money($stock->whole_price))
            ->add('wholesales')
            ->add('bulksales')
            ->add('retail')
            ->add('quantity')
            ->add('box')
            ->add('carton');
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
        $cols =  [
            Column::make('Name', 'name')->searchable(),
            Column::make('Retail Price', 'retail_price'),
            Column::make('Ws Price', 'whole_price'),
            Column::make('Bulk Price', 'bulksales'),
        ];

        $cols[] =  Column::add()->title('Quantity')->field('quantity');

        return array_merge($cols, [
            Column::make('Box', 'box'),
            Column::make('Carton', 'carton'),
        ]);
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
     * PowerGrid Batchstock Action Buttons.
     *
     * @return array<int, Button>
     */

    /*
    public function actions(): array
    {
       return [
           Button::make('edit', 'Edit')
               ->class('bg-indigo-500 cursor-pointer text-white px-3 py-2.5 m-1 rounded text-sm')
               ->route('batchstock.edit', function(\App\Models\Batchstock $model) {
                    return $model->id;
               }),

           Button::make('destroy', 'Delete')
               ->class('bg-red-500 cursor-pointer text-white px-3 py-2 m-1 rounded text-sm')
               ->route('batchstock.destroy', function(\App\Models\Batchstock $model) {
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
     * PowerGrid Batchstock Action Rules.
     *
     * @return array<int, RuleActions>
     */

    /*
    public function actionRules(): array
    {
       return [

           //Hide button edit for ID 1
            Rule::button('edit')
                ->when(fn($batchstock) => $batchstock->id === 1)
                ->hide(),
        ];
    }
    */
}
