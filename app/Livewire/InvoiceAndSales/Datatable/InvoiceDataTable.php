<?php

namespace App\Livewire\InvoiceAndSales\Datatable;


use App\Classes\ExportDataTableComponent;
use App\Classes\Settings;
use App\Models\WaitingCustomer;
use App\Traits\SimpleDatatableComponentTrait;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\LivewireAlert;
use App\Classes\Column;
use App\Models\Invoice;


class InvoiceDataTable extends ExportDataTableComponent
{

    use SimpleDatatableComponentTrait,LivewireAlert;

    protected $model = Invoice::class;

    public array $filters = [];

    public bool $isWaitingList = false;

    public function builder(): Builder
    {
        if($this->isWaitingList === true){
            return WaitingCustomer::with(['invoice'])
                ->select('*')
                ->where('waiting_customers.status', '<>', WaitingCustomer::$waitingInvoiceStatus['complete'])
                ->filterdata($this->filters)
                ->orderBy('waiting_customers.entered_at');

        }

        return Invoice::query()->select('*')->where('invoices.status_id','<>',status('Deleted'))->filterdata($this->filters);

    }

    public static function  mountColumn(InvoiceDataTable $invoiceDataTable) : array
    {
        if($invoiceDataTable->isWaitingList) {
            return [
                Column::make("Customer", "customer.firstname")
                    ->format(fn($value, $row, Column $column)=> $row->firstname." ".$row->lastname)
                    ->sortable()
                    ->searchable(),
                Column::make("Update Status", "invoice.in_department")
                    ->format(function($value, $row, Column $column) {
                        return waitingListCustomerUpdateButton($row);
                    })
                    ->html(),
                Column::make("Pickup Department", "invoice.department")
                    ->format(fn($value, $row, Column $column)=> Settings::$department[$value])
                    ->sortable()->searchable(),

                Column::make("Status", "status")
                    ->format(function($value, $row, Column $column) {
                        if(!isset($value)) {
                            return showAddToWaitingStatus(false);
                        }
                        return showWaitingStatus($value);
                    })
                    ->sortable()
                    ->searchable()
                    ->html(),
            ];
        } else {
            return [
                Column::make("Invoice number", "invoice_number")
                    ->sortable()->searchable(),
                Column::make("Department", "in_department")
                    ->format(fn($value, $row, Column $column)=> Settings::$department[$value])
                    ->sortable()->searchable(),
                Column::make("Customer", "customer.firstname")
                    ->format(fn($value, $row, Column $column)=> $row->firstname." ".$row->lastname)
                    ->sortable()
                    ->searchable(),

                Column::make("Waiting Status", "waitingCustomer.status")
                    ->format(function($value, $row, Column $column) {
                        if(!isset($value)) {
                            return showAddToWaitingStatus(false);
                        }
                        return showWaitingStatus($value);
                    })
                    ->sortable()
                    ->searchable()
                    ->html(),
                Column::make("Sub total", "sub_total")
                    ->format(fn($value, $row, Column $column)=> money($row->sub_total))
                    ->sortable()
                    ->footer(function($rows){
                        return money($rows->sum('sub_total'));
                    })
                    ->searchable(),
                Column::make("Status", "status.name")
                    ->format(fn($value, $row, Column $column) => showStatus($value))->html()
                    ->sortable()->searchable(),
                Column::make("Returned Times", "void_reason")
                    ->sortable(),
                Column::make("Discount", "discount_amount")
                    ->format(fn($value, $row, Column $column)=> money($row->discount_amount))
                    ->sortable()
                    ->footer(function($rows){
                        return money($rows->sum('discount_amount'));
                    })
                    ->searchable(),
                Column::make("Total paid", "total_amount_paid")
                    ->format(fn($value, $row, Column $column)=> money($row->total_amount_paid))
                    ->sortable()
                    ->searchable()
                    ->footer(function($rows){
                        return money($rows->sum('total_amount_paid'));
                    })
                ,
                Column::make("Date", "invoice_date")
                    ->format(fn($value, $row, Column $column)=> eng_str_date($row->invoice_date))
                    ->sortable()->searchable(),
                Column::make("Time", "sales_time")
                    ->format(fn($value, $row, Column $column)=> twelveHourClock($row->sales_time))
                    ->sortable()->searchable(),
                Column::make("By", "last_updated.name")
                    ->format(fn($value, $row, Column $column)=> $value)
                    ->sortable()->searchable(),
                Column::make("Action","id")
                    ->format(function($value, $row, Column $column){
                        $html = "No Action";
                        if(can(['view','edit','printAfour','printThermal','printWaybill','delete', 'return', 'processOnlineInvoice', 'canAddToWaitingList', 'canRemoveToWaitingList'], $row)) {
                            $html = '<div class="dropdown"><button class="btn btn-link font-size-16 shadow-none py-0 text-muted dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bx bx-dots-horizontal-rounded"></i></button>';
                            $html .= '<ul class="dropdown-menu dropdown-menu-end">';
                            if (auth()->user()->can('view', $row)) {
                                $html .= '<li><a href="' . route('invoiceandsales.view', $row->id) . '" class="dropdown-item">Invoice Details</a></li>';
                            }

                            if (auth()->user()->can('edit', $row)) {
                                $html .= '<li><a href="' . route('invoiceandsales.edit', $row->id) . '" class="dropdown-item">Edit Invoice</a></li>';
                            }

                            if (auth()->user()->can('processOnlineInvoice', $row)){
                                $html .= '<li><a data-msg="Are you sure, you want to Process/Pack Invoice this invoice, this can not be reversed"  href="' . route('invoiceandsales.processOnlineInvoice', $row->id) . '" class="dropdown-item confirm_action">Process/Pack Invoice</a></li>';
                            }

                            if (auth()->user()->can('packOnlineInvoice', $row)){
                                $html .= '<li><a data-msg="Are you sure, you want to Mark the Invoice has Packed, this can not be reversed"  href="' . route('invoiceandsales.packOnlineInvoice', $row->id) . '" class="dropdown-item confirm_action">Mark Invoice has Packed</a></li>';
                            }

                            if (auth()->user()->can('return', $row)) {
                                $html .= '<li><a href="' . route('invoiceandsales.return', $row->id) . '" class="dropdown-item">Return Invoice</a></li>';
                            }

                            if (auth()->user()->can('printAfour', $row)) {
                                $html .= '<li><a href="' . route('invoiceandsales.print_afour', $row->id) . '" class="dropdown-item print">Print A4</a></li>';
                            }

                            if (auth()->user()->can('return', $row)) {
                                $html .= '<li><a href="' . route('invoiceandsales.return', $row->id) . '" class="dropdown-item">Return Invoice</a></li>';
                            }

                            if (auth()->user()->can('printThermal', $row)) {
                                $html .= '<li><a href="' . route('invoiceandsales.pos_print', $row->id) . '" class="dropdown-item print">Print Thermal</a></li>';
                            }

                            if (auth()->user()->can('printWaybill', $row)) {
                                $html .= '<li><a href="' . route('invoiceandsales.print_way_bill', $row->id) . '" class="dropdown-item print">Print Waybill</a></li>';
                            }

                            if (auth()->user()->can('delete', $row)) {
                                $html .= '<li><a href="' . route('invoiceandsales.destroy', $row->id) . '" href="javascript:" class="dropdown-item">Delete Invoice</a></li>';
                            }

                            if (auth()->user()->can('canAddToWaitingList', $row)) {
                                $html .= '<li><a href="' . route('invoiceandsales.addToWaitingList', $row->id) . '" href="javascript:" onclick="return confirm(\'Are you sure want to add this invoice to waiting list, this can not be reversed\');" class="dropdown-item">Add To Waiting List</a></li>';
                            }

                            if (auth()->user()->can('canRemoveToWaitingList', $row)) {
                                $html .= '<li><a href="' . route('invoiceandsales.removeFromWaitingList', $row->id) . '" href="javascript:" onclick="return confirm(\'Are you sure want to remove this invoice to waiting list, this can not be reversed\');" class="dropdown-item">Remove Waiting List</a></li>';
                            }

                            if (auth()->user()->can('setWaitingListInvoiceToPacking', $row)) {
                                $html .= '<li><a href="' . route('invoiceandsales.packWaitingListInvoice', $row->id) . '" href="javascript:" onclick="return confirm(\'Are you sure you want set the invoice queue status to packing, this can not be reversed\');" class="dropdown-item">Start Packing</a></li>';
                            }

                            if (auth()->user()->can('setWaitingListInvoiceToPacked', $row)) {
                                $html .= '<li><a href="' . route('invoiceandsales.packedWaitingListInvoice', $row->id) . '" href="javascript:" onclick="return confirm(\'Are you sure you want set the invoice queue status to packed, this can not be reversed\');" class="dropdown-item">Complete Packing</a></li>';
                            }

                            if (auth()->user()->can('setWaitingListInvoiceToPicking', $row)) {
                                $html .= '<li><a href="' . route('invoiceandsales.pickWaitingListInvoice', $row->id) . '" href="javascript:" onclick="return confirm(\'Are you sure you want set the invoice queue status to picking, this can not be reversed\');" class="dropdown-item">Start Picking</a></li>';
                            }

                            if (auth()->user()->can('setWaitingListInvoiceToCompletePicking', $row->invoice)) {
                                $html .= '<li><a href="' . route('invoiceandsales.completePickingWaitingListInvoice', $row->invoice_id) . '" href="javascript:" onclick="return confirm(\'Are you sure you want set the invoice queue status to complete picking, this can not be reversed\');" class="dropdown-item">Complete Picking</a></li>';
                            }

                            $html .= '</ul></div>';
                        }
                        return $html;
                    })
                    ->html()
            ];
        }

    }

}
