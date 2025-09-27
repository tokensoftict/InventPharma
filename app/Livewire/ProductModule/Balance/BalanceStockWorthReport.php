<?php

namespace App\Livewire\ProductModule\Balance;

use App\Traits\PowerGridComponentTrait;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Footer;
use PowerComponents\LivewirePowerGrid\Header;
use PowerComponents\LivewirePowerGrid\Exportable;
use PowerComponents\LivewirePowerGrid\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridEloquent;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\Traits\ActionButton;

final class BalanceStockWorthReport extends PowerGridComponent
{
    use PowerGridComponentTrait;


    public array $filters;

    public $key = "id";

    /*
    |--------------------------------------------------------------------------
    |  Datasource
    |--------------------------------------------------------------------------
    | Provides data to your Table using a Model or Collection
    |
    */
    public function datasource(): ? Collection
    {
        $date = $this->filters['filter_date'];

        $department = $this->filters['department'];

        if($date[0]  == $date[1]){
            $closing_date = date('Y-m-d',strtotime($date[1].' +1 day'));
        }else{
            $closing_date =$date[1];
        }

        if($date[1] == date('Y-m-d')){
            $date[1] = date('Y-m-d',strtotime($date[1].' -1 day'));
        }

        $selling_price_column = [
            'retail'=>"retail_price",
            'wholesales'=>"whole_price",
            'bulksales'=>"whole_price",
            'quantity'=>"whole_price",
        ];

        $column = $department == "retail" ? "average_retail_cost_price" : "average_cost_price";
        $sumcolumn_opening = $department == "retail" ? "stockopenings.retail" : "stockopenings.".$department;
        $sumcolumn_closing = $department == "retail" ? "stockopenings.retail" : "stockopenings.".$department;

        $invoice_column = $department;
        $price_column =  $selling_price_column[$department];
        $batch_cost_column = $department == "retail" ? "retail_cost_price" : "cost_price";

        $invoices = DB::table('invoiceitembatches')
            ->select('invoiceitembatches.stock_id',
                DB::raw('SUM(invoiceitembatches.cost_price * invoiceitembatches.quantity) as invoice_cost_price'))
            ->join('invoices','invoices.id','=','invoiceitembatches.invoice_id')
            ->where('invoiceitembatches.department','=',$invoice_column)
            ->where(function($query) use(&$invoice_column,&$date){
                $query->whereBetween('invoices.invoice_date',$date)
                    ->where(function ($q){
                        $q->orWhere('invoices.status_id',status('Complete'))->orWhere('invoices.status_id',status('Paid'));
                    });
            })->groupBy('invoiceitembatches.stock_id');

        $po = DB::table('purchaseitems')->select('purchaseitems.stock_id',DB::raw('(AVG(purchaseitems.cost_price) * SUM(purchaseitems.qty)) as po_item_cost_price'))->leftJoin('purchases','purchaseitems.purchase_id','=','purchases.id')
            ->where('purchases.status_id',status('Paid'))->whereBetween('purchases.date_completed',$date)->where('purchases.department',$department)->groupBy('purchaseitems.stock_id');


        $stock_transfer_out = DB::table('stocktransferitems')->select(
            'stocktransferitems.stock_id',
            DB::raw( 'SUM(stocktransferitems.quantity * stockbatches.'.$batch_cost_column.') as stock_transfer_out')
        )->leftJoin('stocktransfers','stocktransfers.id','=','stocktransferitems.stocktransfer_id')
            ->join('stockbatches','stocktransferitems.stockbatch_id','=','stockbatches.id')
            ->whereBetween('stocktransfers.transfer_date',$date)
            ->where('stocktransfers.status_id',status('Approved'))
            ->where('from',$department)
            ->groupBy('stocktransferitems.stock_id');


        $stock_transfer_in = DB::table('stocktransferitems')
            ->select(
                'stocktransferitems.stock_id',
                DB::raw( 'SUM(stocktransferitems.quantity * stockbatches.'.$batch_cost_column.') as stock_transfer_in')
            )->leftJoin('stocktransfers','stocktransfers.id','=','stocktransferitems.stocktransfer_id')
            ->join('stockbatches','stocktransferitems.stockbatch_id','=','stockbatches.id')
            ->whereBetween('stocktransfers.transfer_date',$date)
            ->where('stocktransfers.status_id',status('Approved'))
            ->where('to',$department)
            ->groupBy('stocktransferitems.stock_id');


        $report = DB::table('stocks')->where('stocks.status','1')
            ->select(
                'stocks.id',
                'stocks.name',
                'stocks.box',
                'stocks.carton',
                'stocks.status',
                'stocks.whole_price',
                'stocks.retail_price',
                'purchaseitems.po_item_cost_price as purchase_stock_worth',
                'invoiceitembatches.invoice_cost_price as total_sold_cost_price',
                'st_in.stock_transfer_in as total_stock_transfer_in',
                'st_out.stock_transfer_out as total_stock_transfer_out',
                DB::raw('(AVG(stockopenings.'.$column.') * ('.$sumcolumn_opening.')) as opening_stock_worth'),
                DB::raw('(AVG(stockopenings.'.$column.') * ('.$sumcolumn_closing.')) as closing_stock_worth')
            )
            ->leftJoinSub($invoices,'invoiceitembatches',function($join){
                $join->on('invoiceitembatches.stock_id','=','stocks.id');
            })

            ->leftJoinSub($po,'purchaseitems',function($join){
                $join->on('purchaseitems.stock_id','=','stocks.id');
            })

            ->leftjoin('stockopenings as stock_closing','stock_closing.stock_id','=','stocks.id')

            ->leftJoin('stockopenings','stockopenings.stock_id','=','stocks.id')

            ->where('stockopenings.date_added',$date[0])

            ->where('stock_closing.date_added', $closing_date)



            ->leftJoinSub($stock_transfer_in,'st_in',function($join){
                $join->on('st_in.stock_id','=','stocks.id');
            })
            ->leftJoinSub($stock_transfer_out,'st_out',function($join){
                $join->on('st_out.stock_id','=','stocks.id');
            })
            ->where($price_column,'>',0)
            //->whereIn('stocks.id',[2686,3609, 4389,1088])
            ->groupBy('stocks.id')->get();

         return $report;

    }

    /*
    |--------------------------------------------------------------------------
    |  Relationship Search
    |--------------------------------------------------------------------------
    | Configure here relationships to be used by the Search and Table Filters.
    |
    */


    /*
    |--------------------------------------------------------------------------
    |  Add Column
    |--------------------------------------------------------------------------
    | Make Datasource fields available to be used as columns.
    | You can pass a closure to transform/modify the data.
    |
    */
    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('carton')
            ->add('box')
            ->add('name')
            ->add('total_stock_transfer_in')
            ->add('total_stock_transfer_out')
            ->add('opening_stock_worth')
            ->add('purchase_stock_worth')
            ->add('total_sold_cost_price')
            ->add('closing_stock_worth')
            ->add('compare' , function($r){
                $a = $r->total_stock_transfer_out +$r->total_sold_cost_price;

                $b = $r->opening_stock_worth+$r->purchase_stock_worth+$r->total_stock_transfer_in;

                $re = $b-$a;

                return money($r->closing_stock_worth - $re);
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
            Column::add()->index()->field('id')->title('SN')->visibleInExport(false),
            Column::make('Name', 'name'),
            Column::make('Carton', 'carton'),
            Column::make('Total Transfer In', 'total_stock_transfer_in'),
            Column::make('Total Transfer Out', 'total_stock_transfer_out'),
            Column::make('Opening Stock Cost', 'opening_stock_worth'),
            Column::make('Purchase Stock Cost', 'purchase_stock_worth'),
            Column::make('Goods Stock Cost', 'total_sold_cost_price'),
            Column::make('Closing Stock Cost', 'closing_stock_worth'),
            Column::make('Result', 'compare'),
        ];
    }
}
