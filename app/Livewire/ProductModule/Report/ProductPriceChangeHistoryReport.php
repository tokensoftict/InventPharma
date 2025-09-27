<?php

namespace App\Livewire\ProductModule\Report;

use App\Models\Pricechangehistory;
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

final class ProductPriceChangeHistoryReport extends PowerGridComponent
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
     * @return Builder<\App\Models\Pricechangehistory>
     */
    public function datasource(): Builder
    {
        return Pricechangehistory::with(['stock', 'user'])->filterdata($this->filters);
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
            ->add('change_by', function (Pricechangehistory $pricechangehistory){
                return $pricechangehistory->user->name;
            })
            ->add('from', function(Pricechangehistory $pricechangehistory){
                return money($pricechangehistory->from);
            })
            ->add('to', function(Pricechangehistory $pricechangehistory){
                return money($pricechangehistory->to);
            })
            ->add('stock_id')
            ->add('name', function (Pricechangehistory $pricechangehistory){
                return $pricechangehistory->stock->name;
            })
            ->add('department', function (Pricechangehistory $pricechangehistory){
                return department_by_quantity_column($pricechangehistory->department)->label;
            })

            ->add('change_date_formatted', fn (Pricechangehistory $model) => Carbon::parse($model->change_date)->format('d/m/Y'))
            ->add('time', function(Pricechangehistory $model) {
                return $model->change_time->format('h:i a');
            })
            ->add('created_at_formatted', fn (Pricechangehistory $model) => Carbon::parse($model->created_at)->format('d/m/Y H:i:s'));
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
            Column::make('Department', 'department'),
            Column::make('Change By', 'change_by'),
            Column::make('Change From', 'from')->sortable(),
            Column::make('Change To', 'to')->sortable(),
            Column::make('Change date', 'change_date_formatted', )->sortable(),
            Column::make('Change time', 'time')->sortable(),
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
     * PowerGrid Pricechangehistory Action Buttons.
     *
     * @return array<int, Button>
     */

    /*
    public function actions(): array
    {
       return [
           Button::make('edit', 'Edit')
               ->class('bg-indigo-500 cursor-pointer text-white px-3 py-2.5 m-1 rounded text-sm')
               ->route('pricechangehistory.edit', function(\App\Models\Pricechangehistory $model) {
                    return $model->id;
               }),

           Button::make('destroy', 'Delete')
               ->class('bg-red-500 cursor-pointer text-white px-3 py-2 m-1 rounded text-sm')
               ->route('pricechangehistory.destroy', function(\App\Models\Pricechangehistory $model) {
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
     * PowerGrid Pricechangehistory Action Rules.
     *
     * @return array<int, RuleActions>
     */

    /*
    public function actionRules(): array
    {
       return [

           //Hide button edit for ID 1
            Rule::button('edit')
                ->when(fn($pricechangehistory) => $pricechangehistory->id === 1)
                ->hide(),
        ];
    }
    */
}
