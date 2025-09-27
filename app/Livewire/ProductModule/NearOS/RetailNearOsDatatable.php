<?php

namespace App\Livewire\ProductModule\NearOS;

use App\Models\Retailnearoutofstock;
use App\Traits\PowerGridComponentTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use PowerComponents\LivewirePowerGrid\{Button, Column, Facades\Rule, PowerGrid, PowerGridComponent, PowerGridFields};
use Illuminate\Support\Facades\DB;

final class RetailNearOsDatatable extends PowerGridComponent
{
    use PowerGridComponentTrait;

    public $key = 'id';

    /*
    |--------------------------------------------------------------------------
    |  Features Setup
    |--------------------------------------------------------------------------
    | Setup Table's general features
    |
    */
    protected function getListeners(): array
    {
        return array_merge(
            parent::getListeners(), [
            'view_stock' => 'view_stock',
        ]);
    }


    public function datasource(): Builder
    {
        return Retailnearoutofstock::query()
            ->select(
                [
                    'retailnearoutofstock.*',
                    'stocks.name as stock_name',
                    'stocks.box as box',
                    'stocks.carton as carton',
                    'categories.name as category_name',
                    'suppliers.name as supplier_name',
                    'stockgroups.name as group_name',
                    DB::raw('(CASE
                        WHEN retailnearoutofstock.stockgroup_id IS NOT NULL THEN stockgroups.name
                        ELSE stocks.name
                    END) AS name')
                ]
            )->where("retailnearoutofstock.threshold_type", "<>", "")
            ->leftJoin('stocks', function ($stocks) {
                $stocks->on('retailnearoutofstock.stock_id', '=', 'stocks.id');
            })
            ->leftJoin('categories', 'stocks.category_id', '=', 'categories.id')
            ->leftJoin('stockgroups', function ($stockgroups) {
                $stockgroups->on('retailnearoutofstock.stockgroup_id', '=', 'stockgroups.id');
            })
            ->leftJoin('suppliers', function ($suppliers) {
                $suppliers->on('retailnearoutofstock.supplier_id', '=', 'suppliers.id');
            });
        //->whereNotNull('stocks.name');
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
            ],
            'supplier' => [
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
            ->add('stock_id')
            ->add('name')
            ->add('threshold_type', function(Retailnearoutofstock $nearoutofstock){
                return $nearoutofstock->threshold_type == "" ? "THRESHOLD" : $nearoutofstock->threshold_type;
            })
            ->add('box')
            ->add('carton')
            ->add('category_name')
            ->add('os_type')
            ->add('qty_to_buy')
            ->add('supplier_name')
            ->add('threshold_value')
            ->add('current_qty')
            ->add('current_sold')
            ->add('group_os_id')
            ->add('is_grouped')
            ->add('last_qty_purchased')
            ->add('last_purchase_date_formatted', fn (Retailnearoutofstock $model) => $model->last_purchase_date == NULL ? "" : Carbon::parse($model->last_purchase_date)->format('d/m/Y'))
            ->add('purchaseitem_id');

    }

    /*
    |--------------------------------------------------------------------------
    |  Include Columns
    |--------------------------------------------------------------------------
    | Include the columns added columns, making them visible on the Table.
    | Each column can be configured with properties, filters, add...
    |
    */
    public function actions(Retailnearoutofstock $row): array
    {
        return [
            Button::add('view_stock')
                ->slot('View Stock')
                ->class('btn btn-sm btn-primary')
                ->dispatch('view_stock', [['group_id'=> $row->stockgroup_id]])
        ];
    }

    public function actionRules(): array
    {
        return [
            Rule::button('view_stock')
                ->when(fn ($nearoutofstock) => $nearoutofstock->stockgroup_id === NULL)
                ->hide()

        ];
    }

    /**
     * PowerGrid Columns.
     *
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            Column::add()->index()->title('SN')->visibleInExport(false),
            Column::make('Product ID', 'stock_id'),
            Column::make('Name', 'name','name')->searchable()->sortable(),
            Column::make('Box', 'box','box')->sortable(),
            Column::make('Carton', 'carton','carton')->sortable(),
            Column::make('Category Name', 'category_name','category_name')->sortable(),
            Column::make('Qty to Buy', 'qty_to_buy')->sortable(),
            Column::make('Supplier', 'supplier_name', 'supplier_name')->sortable()->searchable(),
            Column::make('Threshold type', 'threshold_type')->sortable(),
            Column::make('Threshold value', 'threshold_value')->sortable(),
            Column::make('Stock Quantity', 'current_qty')->sortable(),
            Column::make('Total Sold', 'current_sold')->sortable(),
            Column::make('Last Qty Pur.', 'last_qty_purchased'),
            Column::make('Last Date Pur.', 'last_purchase_date_formatted', 'last_purchase_date')->sortable(),
            Column::action("Actions")
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


    public function view_stock(array $data)
    {
        $this->dispatch('showModal', [
            'alias' => 'product-module.near-os.view-near-os-grouped-stock',
            'size' => 'modal-xl',
            'params' => [
                'stockgroup' =>  $data['group_id']
            ]
        ]);
    }

}
