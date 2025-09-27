<?php

namespace App\Livewire\PaymentManager\Datatable;

use App\Classes\Column;
use App\Classes\ExportDataTableComponent;
use App\Traits\SimpleDatatableComponentTrait;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Creditpaymentlog;

class CreditPaymentLogs extends ExportDataTableComponent
{

    use SimpleDatatableComponentTrait;

    public array $additionalSelect = [];

    public array $filters;

    public bool $perPageAll = true;



    protected $model = Creditpaymentlog::class;

    public function builder(): Builder
    {
        return  Creditpaymentlog::query()->select('*')->filterdata($this->filters)->where('creditpaymentlogs.amount', '>', 0);
    }


    public static function mountColumn() : array
    {
        return [
            Column::make("User", "user.name")
                ->sortable()->searchable(),
            Column::make("Customer", 'customer.firstname')
                ->format(fn($value, $row, Column $column)=> $row->firstname.' '.$row->lastname)
                ->searchable()
                ->sortable(),
            Column::make("Amount", "amount")
                ->format(fn($value, $row, Column $column)=> money($value))
                ->sortable()
                ->footer(function($rows){
                    return money($rows->sum('amount'));
                })
                ->searchable(),
            Column::make("Payment date", "payment_date")
                ->sortable()->searchable(),
            Column::make("Payment Method", "paymentmethod.name")
                ->sortable(),
        ];
    }
/*
    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->sortable(),
            Column::make("Credit number", "credit_number")
                ->sortable(),
            Column::make("Payment id", "payment_id")
                ->sortable(),
            Column::make("User id", "user_id")
                ->sortable(),
            Column::make("Paymentmethod id", "paymentmethod_id")
                ->sortable(),
            Column::make("Customer id", "customer_id")
                ->sortable(),
            Column::make("Paymentmethoditem id", "paymentmethoditem_id")
                ->sortable(),
            Column::make("Invoicelog type", "invoicelog_type")
                ->sortable(),
            Column::make("Invoicelog id", "invoicelog_id")
                ->sortable(),
            Column::make("Amount", "amount")
                ->sortable(),
            Column::make("Payment date", "payment_date")
                ->sortable(),
            Column::make("Created at", "created_at")
                ->sortable(),
            Column::make("Updated at", "updated_at")
                ->sortable(),
        ];
    }
*/
}
