<?php

namespace App\Livewire\ProductModule\BinCard;

use App\Classes\Settings;
use App\Models\Stockbincard;
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

final class StockBatchUpdateReport extends PowerGridComponent
{

    use PowerGridComponentTrait;

    public $key = "id";

    public array  $filters;

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
     * @return Builder<\App\Models\Stockbincard>
     */
    public function datasource(): Builder
    {
        $date = $this->filters['between.stockbincards.bin_card_date'];

        return Stockbincard::query()->with(['stock','user'])->whereBetween('bin_card_date',$date)
            ->where(function($q) use(&$request){
                $q->orwhere('bin_card_type',"APP//BATCH_UPDATE");
            })
            ->orderBy('id','DESC');
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
            ->add('id')
            ->add('stock_id')
            ->add('name', function(Stockbincard $stockbincard){
                return $stockbincard->stock->name;
            })
            ->add('bin_card_type', function (Stockbincard $stockbincard){
                return ProductBincard::$bincardType[$stockbincard->bin_card_type];
            })
            ->add('user_id')
            ->add('by', function(Stockbincard $stockbincard){
                return $stockbincard->user->name;
            })
            ->add('in_qty')
            ->add('out_qty')
            ->add('sold_qty')
            ->add('return_qty')
            ->add('stockbatch_id')
            ->add('to_department', function (Stockbincard $stockbincard){
                return Settings::$department[$stockbincard->to_department];
            })
            ->add('from_department', function (Stockbincard $stockbincard){
                return Settings::$department[$stockbincard->from_department];
            })
            ->add('supplier_id')
            ->add('invoice_id')
            ->add('stocktransfer_id')
            ->add('purchase_id')
            ->add('balance')
            ->add('comment')
            ->add('bin_card_date', function (Stockbincard $stockbincard){
                return eng_str_date($stockbincard->bin_card_date);
            })
            ->add('department_balance');
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
            Column::make('Type', 'bin_card_type'),
            Column::make('From', 'from_department'),
            Column::make('To', 'to_department'),
            Column::make('In Qty', 'in_qty'),
            Column::make('Out Qty', 'out_qty'),
            Column::make('Sold Qty', 'sold_qty'),
            Column::make('Return Qty', 'return_qty'),
            Column::make('Balance', 'department_balance'),
            Column::make('Grand Balance', 'balance'),
            Column::make('Date', 'bin_card_date'),
            Column::make('Comment', 'comment')

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
     * PowerGrid Stockbincard Action Buttons.
     *
     * @return array<int, Button>
     */

    /*
    public function actions(): array
    {
       return [
           Button::make('edit', 'Edit')
               ->class('bg-indigo-500 cursor-pointer text-white px-3 py-2.5 m-1 rounded text-sm')
               ->route('stockbincard.edit', function(\App\Models\Stockbincard $model) {
                    return $model->id;
               }),

           Button::make('destroy', 'Delete')
               ->class('bg-red-500 cursor-pointer text-white px-3 py-2 m-1 rounded text-sm')
               ->route('stockbincard.destroy', function(\App\Models\Stockbincard $model) {
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
     * PowerGrid Stockbincard Action Rules.
     *
     * @return array<int, RuleActions>
     */

    /*
    public function actionRules(): array
    {
       return [

           //Hide button edit for ID 1
            Rule::button('edit')
                ->when(fn($stockbincard) => $stockbincard->id === 1)
                ->hide(),
        ];
    }
    */
}
