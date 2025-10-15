<?php

namespace App\Livewire\ProductModule\NearOS;

use App\Classes\Settings;
use App\Models\Nearoutofstock;
use App\Models\OutOfStockLog;
use App\Traits\PowerGridComponentTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use PowerComponents\LivewirePowerGrid\{Button, Column, Facades\Rule, PowerGrid, PowerGridComponent, PowerGridFields};
use Illuminate\Support\Facades\DB;

final class NearOsDatatableClickedUser extends PowerGridComponent
{
    use PowerGridComponentTrait;

    public array $filters;

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
        return OutOfStockLog::query()
            ->with(['stock', 'stock.stockgroup', 'stock.stockgroup.oneStock'])
            ->select(
                [
                    'out_of_stock_logs.*',
                    'stocks.id as stock_id',
                    'stocks.name as stock_name',
                    'stocks.box as box',
                    'stocks.carton as carton',
                    'categories.name as category_name',
                   // 'suppliers.name as supplier_name',
                    'stockgroups.name as group_name',
                ]
            )
            ->leftJoin('stocks', function ($stocks) {
                $stocks->on('out_of_stock_logs.stock_id', '=', 'stocks.id');
            })
            ->leftJoin('categories', 'stocks.category_id', '=', 'categories.id')
            ->leftJoin('stockgroups', function ($stockgroups) {
                $stockgroups->on('stocks.stockgroup_id', '=', 'stockgroups.id');
            })
            ->where('out_of_stock_logs.department', $this->filters['custom_dropdown_id'])
            ->orderBy('clicks', 'DESC');
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
            'stock.stockgroup' => [
                'name',
            ],
            'stock' => [
                'name',
            ],
            /*
            'supplier' => [
                'name'
            ]
            */
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
        $store = app(Settings::class)->store();
        $from =  date('Y-m-d', strtotime(' - '.$store->threshold_days.' days'));
        $to = todaysDate();

        return PowerGrid::fields()
            ->add('stock_id')
            ->add('name', function (OutOfStockLog $nearoutofstock) {
                return $nearoutofstock->stock->name;
            })
            ->add('box', function(OutOfStockLog $nearoutofstock){
                return $nearoutofstock->stock->box ?? $nearoutofstock->stockgroup?->oneStock?->box;
            })
            ->add('carton', function(OutOfStockLog $nearoutofstock){
                return $nearoutofstock->stock->carton ?? $nearoutofstock->stockgroup?->oneStock?->carton;
            })
            ->add('clicks')
            ->add('category_name', function (OutOfStockLog $nearoutofstock){
                if($nearoutofstock->stockgroup_id === NULL) return $nearoutofstock->category_name;
                if($nearoutofstock->stockgroup_id !== NULL) return $nearoutofstock->stockgroup?->oneStock?->category?->name;
                return "N/A";
            })
            ->add('os_type')
            ->add('supplier_name', function (OutOfStockLog $nearoutofstock){
                return $nearoutofstock->stock->purchaseitems()->with(['purchase'])->whereHas('purchase',function($q){
                    $q->where('status_id',status('Complete'));
                })
                    ->orderBy('id','DESC')
                    ->limit(1)->first()?->purchase?->supplier?->name;
            })
            ->add('current_qty', function(OutOfStockLog $nearoutofstock){
                if($this->filters['custom_dropdown_id'] == "wholesales") {
                    return $nearoutofstock->stock->wholesales + $nearoutofstock->stock->quantity;
                }
                return $nearoutofstock->stock->retail;
           })
            ->add('current_sold', function (OutOfStockLog $nearoutofstock) use(&$from,$to){
                $filter =  $this->filters['custom_dropdown_id'] == "wholesales" ? ['wholesales', 'quantity'] : ['retail'];
                return $nearoutofstock->stock->invoiceitems()->whereIn('department', $filter)
                    ->whereHas('invoice',function($q) use(&$from,$to){
                        $q->whereBetween('invoice_date',[$from,$to]);
                    })
                    ->sum('quantity');
            })
            ->add('group_os_id')
            ->add('is_grouped')
            ->add('last_qty_purchased', function (OutOfStockLog $nearoutofstock){
                return $nearoutofstock->stock->purchaseitems()->whereHas('purchase',function($q){
                    $q->where('status_id',status('Complete'));
                })
                    ->orderBy('id','DESC')
                    ->limit(1)->first()?->qty;
            })
            ->add('last_purchase_date', function (OutOfStockLog $nearoutofstock){
                return $nearoutofstock->stock->purchaseitems()->with(['purchase'])->whereHas('purchase',function($q){
                    $q->where('status_id',status('Complete'));
                })
                    ->orderBy('id','DESC')
                    ->limit(1)->first()?->purchase?->date_completed ?? "N/A" ;
            })
            ->add('last_purchase_date_fomartted', function (OutOfStockLog $nearoutofstock){
                return $nearoutofstock->stock->purchaseitems()->with(['purchase'])->whereHas('purchase',function($q){
                    $q->where('status_id',status('Complete'));
                })
                    ->orderBy('id','DESC')
                    ->limit(1)->first()?->purchase?->date_completed->format("d/m/Y") ?? "N/A" ;
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



    public function actionRules(): array
    {

        return [
            /*
            Rule::button('view_stock')
                ->when(fn ($nearoutofstock) => $nearoutofstock->stockgroup_id === NULL)
                ->hide()
*/
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
            Column::make('Clicks', 'clicks','clicks')->sortable(),
            Column::make('Supplier', 'supplier_name', 'supplier_name')->sortable()->searchable(),
            Column::make('Stock Quantity', 'current_qty')->sortable(),
            Column::make('Total Sold', 'current_sold')->sortable(),
            Column::make('Last Qty Pur.', 'last_qty_purchased'),
            Column::make('Last Date Pur.', 'last_purchase_date_fomartted'),
            //Column::action("Actions")
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
        /*
        $this->dispatch('showModal', [
            'alias' => 'product-module.near-os.view-near-os-grouped-stock',
            'size' => 'modal-xl',
            'params' => [
                'stockgroup' =>  $data['group_id']
            ]
        ]);
        */
    }

}
