<?php

namespace App\Livewire\PaymentManager\Datatable;

use App\Classes\ExportDataTableComponent;
use App\Traits\SimpleDatatableComponentTrait;
use Illuminate\Support\Facades\DB;
use App\Classes\Column;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Invoiceitem;

class ProfitAndLossDatatable extends ExportDataTableComponent
{

    use SimpleDatatableComponentTrait;

    protected $model = Invoiceitem::class;

    public array $filters;

    public function builder(): Builder
    {
         return Invoiceitem::select(
            DB::raw( 'SUM(invoiceitems.quantity)  as total_qty'),
            DB::raw( 'SUM(invoiceitems.quantity * (invoiceitems.selling_price - invoiceitems.discount_amount)) as total_selling_total'),
            DB::raw( 'SUM(invoiceitems.quantity * (invoiceitems.cost_price)) as total_cost_total'))
            ->whereIn('status_id',[2,4,3])
            ->filterdata($this->filters)
            ->groupBy('stock_id','stocks.name');

    }


    public function configure(): void
    {
        $this->setPrimaryKey('stock_id');
        $this->setOfflineIndicatorDisabled();
        $this->setQueryStringDisabled();
        $this->setEagerLoadAllRelationsEnabled();
        $this->setEmptyMessage('No Data found..');
        $this->setTableAttributes([
            'class' => 'table-nowrap mb-0',
        ]);
    }


    public function columns(): array
    {
        $this->index = $this->page > 1 ? ($this->page - 1) * $this->perPage : 0;
        return [
            Column::make('No.','stock_id')->format(fn () => ++$this->index),
            ...self::mountColumn()
        ];
    }


    public static function mountColumn() : array
    {
        return [
            Column::make("Product Name", "stock.name")
                ->format(fn($value, $row, Column $column)=> $value)
                ->searchable()
                ->sortable(),
            Column::make("Quantity Sold", 'total_qty')->deselected()

        ];
    }


    /*
     *  return [
            Column::make("Id", "id")
                ->sortable(),
            Column::make("Invoice id", "invoice_id")
                ->sortable(),
            Column::make("Stock id", "stock_id")
                ->sortable(),
            Column::make("Quantity", "quantity")
                ->sortable(),
            Column::make("Customer id", "customer_id")
                ->sortable(),
            Column::make("Status id", "status_id")
                ->sortable(),
            Column::make("Added by", "added_by")
                ->sortable(),
            Column::make("Invoice date", "invoice_date")
                ->sortable(),
            Column::make("Sales time", "sales_time")
                ->sortable(),
            Column::make("Cost price", "cost_price")
                ->sortable(),
            Column::make("Selling price", "selling_price")
                ->sortable(),
            Column::make("Profit", "profit")
                ->sortable(),
            Column::make("Total cost price", "total_cost_price")
                ->sortable(),
            Column::make("Total selling price", "total_selling_price")
                ->sortable(),
            Column::make("Discount value", "discount_value")
                ->sortable(),
            Column::make("Total profit", "total_profit")
                ->sortable(),
            Column::make("Total incentives", "total_incentives")
                ->sortable(),
            Column::make("Discount type", "discount_type")
                ->sortable(),
            Column::make("Discount amount", "discount_amount")
                ->sortable(),
            Column::make("Discount added by", "discount_added_by")
                ->sortable(),
            Column::make("Created at", "created_at")
                ->sortable(),
            Column::make("Updated at", "updated_at")
                ->sortable(),
        ];
     */
}
