<?php

namespace App\Livewire\InvoiceAndSales\Datatable;

use App\Classes\ExportDataTableComponent;
use App\Classes\Settings;
use App\Models\Invoice;
use App\Traits\SimpleDatatableComponentTrait;
use Illuminate\Database\Eloquent\Builder;
use App\Classes\Column;;
use App\Models\Invoiceitem;

class InvoiceitemDatatable extends ExportDataTableComponent
{

    public  array $filters;

    use SimpleDatatableComponentTrait;

    protected $model = Invoiceitem::class;

    public bool $perPageAll = true;


    public function builder(): Builder
    {
        $query = Invoiceitem::query()->select('*')
            ->whereHas('invoice', function (Builder $query){
                $query->where('status_id','!=',status('Deleted'));
                if(isset($department)) {
                    if(isset($this->filters['department'])){
                        $query->where('department',$this->filters['department']);
                    }
                }
            });


        return $query->filterdata($this->filters);
    }

    public static function  mountColumn() : array
    {
        return [
            Column::make("Invoice number", "invoice.invoice_number")
                ->sortable()->searchable(),
            Column::make("Department", "invoice.in_department")
                ->format(fn($value, $row, Column $column)=> Settings::$department[$value])
                ->sortable(),
            Column::make("Status", "invoice.status_id")
                ->format(fn($value, $row, Column $column)=> showStatus($value))->html()
                ->sortable()
            ,
            Column::make("Product", "stock.name")
                ->format(fn($value, $row, Column $column)=> $value)
                ->sortable()->searchable(),
            Column::make("Customer", "customer.firstname")
                ->format(fn($value, $row, Column $column)=> $row->customer->firstname." ".$row->customer->lastname)
                ->sortable()->searchable(),
            Column::make("Selling Price", "selling_price")
                ->format(fn($value, $row, Column $column)=> money($row->selling_price))
                ->footer(function($rows){
                    return money($rows->sum('selling_price'));
                })
                ->sortable(),
            Column::make("Cost Price", "cost_price")
                ->format(fn($value, $row, Column $column)=> money($row->cost_price))
                ->footer(function($rows){
                    return money($rows->sum('cost_price'));
                })
                ->sortable(),
            Column::make("Quantity", "quantity")
                ->format(fn($value, $row, Column $column)=> money($row->quantity))
                ->footer(function($rows){
                    return money($rows->sum('quantity'));
                })
                ->sortable(),
            Column::make("Discount", "discount_amount")
                ->format(fn($value, $row, Column $column)=> money($row->discount_amount))
                ->footer(function($rows){
                    return money($rows->sum('discount_amount'));
                })
                ->sortable(),
            Column::make("Total",)
                ->label(fn($row, Column $column)=> money($row->quantity *  ($row->selling_price-$row->discount_amount)))

                ->sortable(),
            Column::make("Date", "invoice.invoice_date")
                ->format(fn($value, $row, Column $column)=> eng_str_date($value))
                ->sortable(),
            Column::make("Time", "invoice.sales_time")
                ->format(fn($value, $row, Column $column)=> twelveHourClock($value))
                ->sortable(),
            Column::make("By", "invoice.last_updated.name")
                ->format(fn($value, $row, Column $column)=> $value)
                ->sortable(),
            Column::make("Action","id")
                ->format(function($value, $row, Column $column){
                    $html = "No Action";
                    if(can(['view','edit','printAfour','printThermal','printWaybill','delete', 'processOnlineInvoice'], $row->invoice)) {
                        $html = '<div class="dropdown"><button class="btn btn-link font-size-16 shadow-none py-0 text-muted dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bx bx-dots-horizontal-rounded"></i></button>';
                        $html .= '<ul class="dropdown-menu dropdown-menu-end">';
                        if (auth()->user()->can('view', $row->invoice)) {
                            $html .= '<li><a href="' . route('invoiceandsales.view', $row->invoice_id) . '" class="dropdown-item">Invoice Details</a></li>';
                        }
                        if (auth()->user()->can('edit', $row->invoice)) {
                            $html .= '<li><a href="' . route('invoiceandsales.edit', $row->invoice_id) . '" class="dropdown-item">Edit Invoice</a></li>';
                        }
                        if (auth()->user()->can('printAfour', $row->invoice)) {
                            $html .= '<li><a href="' . route('invoiceandsales.print_afour', $row->invoice_id) . '" class="dropdown-item">Print A4</a></li>';
                        }

                        if (auth()->user()->can('processOnlineInvoice', $row->invoice)){
                            $html .= '<li><a data-msg="Are you sure, you want to Process/Pack Invoice this invoice, this can not be reversed"  href="' . route('invoiceandsales.processOnlineInvoice', $row->invoice_id) . '" class="dropdown-item confirm_action">Process/Pack Invoice</a></li>';
                        }

                        if (auth()->user()->can('packOnlineInvoice', $row->invoice)){
                            $html .= '<li><a data-msg="Are you sure, you want to Mark the Invoice has Packed, this can not be reversed"  href="' . route('invoiceandsales.packOnlineInvoice', $row->invoice_id) . '" class="dropdown-item confirm_action">Mark Invoice has Packed</a></li>';
                        }

                        if (auth()->user()->can('printThermal', $row->invoice)) {
                            $html .= '<li><a href="' . route('invoiceandsales.pos_print', $row->invoice_id) . '" class="dropdown-item">Print Thermal</a></li>';
                        }
                        if (auth()->user()->can('printWaybill', $row->invoice)) {
                            $html .= '<li><a href="' . route('invoiceandsales.print_way_bill', $row->invoice_id) . '" class="dropdown-item">Print Waybill</a></li>';
                        }
                        if (auth()->user()->can('delete', $row->invoice)) {
                            $html .= '<li><a href="' . route('invoiceandsales.destroy', $row->invoice_id) . '" href="javascript:" class="dropdown-item">Delete Invoice</a></li>';
                        }
                        $html .= '</ul>';
                    }
                    return $html;

                })
                ->html()
        ];
    }

}
