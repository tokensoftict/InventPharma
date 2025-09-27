<?php

namespace App\Livewire\ProductModule\Report;

use App\Classes\Settings;
use App\Models\Stockbatch;
use App\Traits\LivewireAlert;
use App\Traits\PowerGridComponentTrait;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\{Button,
    Column,
    Exportable,
    Footer,
    Header,
    PowerGrid,
    PowerGridComponent,
    PowerGridEloquent,
    PowerGridFields};
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use PowerComponents\LivewirePowerGrid\Filters\Filter;
use PowerComponents\LivewirePowerGrid\Rules\{RuleActions};
use PowerComponents\LivewirePowerGrid\Traits\{ActionButton, WithExport};


final class StockMoveListReport extends PowerGridComponent
{
    use PowerGridComponentTrait;
    use LivewireAlert;

    public $key = 'stock_id';

    public array $filters;

    public array $productCache = [];

    /*
    |--------------------------------------------------------------------------
    |  Datasource
    |--------------------------------------------------------------------------
    | Provides data to your Table using a Model or Collection
    |
    */

    protected function getListeners(): array
    {
        return array_merge(
            parent::getListeners(), [
            'exportToTransfer' => 'exportToTransfer',
        ]);
    }

    public function setUp(): array
    {
        $this->deferLoading = true;

        $this->showCheckBox('stock_id');

        $this->primaryKey = $this->key;

        $this->tableName = 'stockbatches';

        return [
            Exportable::make('export')
                ->type(
                    Exportable::TYPE_XLS,
                    Exportable::TYPE_CSV),
            Header::make()->showSearchInput(),
            Footer::make()
                ->showPerPage(100, Settings::$perPageAccepted)
                ->showRecordCount(),
        ];
    }


    /**
     * PowerGrid datasource.
     *
     * @return Builder<\App\Models\Stockbatch>
     */
    public function datasource(): Builder
    {

        return Stockbatch::with([
            'stock',
            'stock.invoiceitembatches' => function($query){
                $query->select(
                    'stock_id',
                    DB::raw( 'SUM(quantity) as qty')
                )->where('department', $this->filters['department_to'])
                    ->whereHas('invoice',function($q){
                        $day =30;
                        $from = date('Y-m-d', strtotime(' - '.$day.' days'));
                        $to = date('Y-m-d');
                        $q->whereBetween('invoice_date',[$from,$to])
                            ->groupBy('stock_id');
                    });
            }])->select(
            'stock_id',
            DB::raw( 'SUM('.$this->filters['department_from'].') as from_qty'),
            DB::raw( 'SUM('.$this->filters['department_to'].') as to_qty'),
            DB::raw( '(SUM('.$this->filters['department_from'].') -  SUM('.$this->filters['department_to'].')) as result_minus'),
            DB::raw( '(SUM('.$this->filters['department_from'].')-(SUM('.$this->filters['department_from'].') -  SUM('.$this->filters['department_to'].'))) as result')
        )->havingRaw('(SUM('.$this->filters['department_from'].')-(SUM('.$this->filters['department_from'].') -  SUM('.$this->filters['department_to'].'))) <= 0')
            ->havingRaw('SUM('.$this->filters['department_from'].') > 0')
            ->whereHas('stock',function($q){
                $q->where('status','1');
            })
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
            ],
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
            ->add('name', function (Stockbatch $batch){
                return $batch->stock->name;
            })
            ->add('from_qty')
            ->add('to_qty')
            ->add('result_minus')
            ->add('result')
            ->add('qty_to_move', function (Stockbatch $batch){
                $qty_sold = $batch->stock->invoiceitembatches->first();
                $num = ($qty_sold->quantity ?? 0);

                if($this->filters['department_to'] == "retail" && $num > 0){
                    $num = round($num /$batch->stock->box);
                }

                $result =  round(($num/30) * 4);
                if($result == 0){
                    $result =  5;
                }

                if($result > $batch->from_qty){
                    $total_move = $batch->from_qty;
                    $this->productCache[$batch->stock_id] = (int)$total_move;
                    return $batch->from_qty;
                }else{
                    $total_move = $result;
                    $this->productCache[$batch->stock_id] = (int)$total_move;
                    return $result;
                }

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
        return [
            Column::add()->index()->title('SN')->visibleInExport(false),
            Column::make('Stock ID', 'stock_id'),
            Column::make('Name', 'name'),
            Column::make(department_by_quantity_column($this->filters['department_from'])->label, 'from_qty'),
            Column::make(department_by_quantity_column($this->filters['department_to'])->label, 'to_qty'),
            Column::make(department_by_quantity_column($this->filters['department_from'])->label."-".department_by_quantity_column($this->filters['department_to'])->label, 'result_minus'),
            Column::make("Result", 'result'),
            Column::make("Qty to Move", 'qty_to_move'),
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

    public function header(): array
    {

        return [
            Button::add('bulk-delete')
                ->slot('Export To Transfer <i class="fa fa-arrow-right"></i>')
                ->class('btn btn-success')
                ->dispatch('exportToTransfer', []),
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

    public function exportToTransfer()
    {
        if (count($this->checkboxValues) == 0) {
            $this->alert('error', 'Product Selection',
                [
                    'position' => 'center',
                    'timer' => 2000,
                    'toast' => false,
                    'text' =>"You must select at least one Product!"
                ]
            );
            return;
        }

        $export = Arr::only($this->productCache, $this->checkboxValues);

        session()->put('transfer_stock',$export);

        return redirect()->route('transfer.create', ['from'=>$this->filters['department_from'], 'to'=>$this->filters['department_to'] ]);
    }
}

