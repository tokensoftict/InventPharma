<?php

namespace App\Livewire\PaymentManager\Datatable;

use App\Classes\ExportDataTableComponent;
use App\Models\Creditpaymentlog;
use App\Models\Invoice;
use App\Traits\SimpleDatatableComponentTrait;
use App\Classes\Column;
use App\Models\Paymentmethoditem;
use Illuminate\Database\Eloquent\Builder;

class PaymentMethodItemsDatatable extends ExportDataTableComponent
{

    use SimpleDatatableComponentTrait;

    protected $model = Paymentmethoditem::class;

    public bool $perPageAll = true;


    public array $filters;


    public function builder(): Builder
    {
        return  Paymentmethoditem::query()->select('*')->filterdata($this->filters);
    }

    public static function mountColumn() : array
    {
        return [
            Column::make("Customer", "payment.customer.firstname")
                ->format(fn($value, $row, Column $column)=> $row->customer->firstname." ".$row->customer->lastname)
                ->searchable()
                ->sortable(),
            Column::make("Type", "invoice_type")
                ->format(function($value, $row, Column $column){
                    return PaymentListDatatable::$invoiceType[$row->invoice_type];
                } )
                ->sortable(),
            Column::make("Invoice Number", "payment.invoice_number")
                ->format(fn($value, $row, Column $column)=> $value)
                ->sortable(),
            Column::make("Amount", "amount")
                ->format(fn($value, $row, Column $column)=> money($value))
                ->sortable(),
            Column::make("Payment Method", "paymentmethod.name")
                ->format(fn($value, $row, Column $column) => $value)
                ->sortable(),
            Column::make("Payment Date", "payment_date")
                ->format(fn($value, $row, Column $column) => eng_str_date($row->payment_date))
                ->sortable(),
            Column::make("Time", "payment.payment_time")
                ->format(fn($value, $row, Column $column) => twelveHourClock($value))
                ->sortable(),
            Column::make("By", "user_id")
                ->format(fn($value, $row, Column $column) => $row->user->name)
                ->searchable()
                ->sortable(),

        ];
    }


/*
    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->sortable(),
            Column::make("User id", "user_id")
                ->sortable(),
            Column::make("Customer id", "customer_id")
                ->sortable(),
            Column::make("Payment id", "payment_id")
                ->sortable(),
            Column::make("Paymentmethod id", "paymentmethod_id")
                ->sortable(),
            Column::make("Invoice type", "invoice_type")
                ->sortable(),
            Column::make("Invoice id", "invoice_id")
                ->sortable(),
            Column::make("Payment date", "payment_date")
                ->sortable(),
            Column::make("Amount", "amount")
                ->sortable(),
            Column::make("Payment info", "payment_info")
                ->sortable(),
            Column::make("Created at", "created_at")
                ->sortable(),
            Column::make("Updated at", "updated_at")
                ->sortable(),
        ];
    }
    */
}
