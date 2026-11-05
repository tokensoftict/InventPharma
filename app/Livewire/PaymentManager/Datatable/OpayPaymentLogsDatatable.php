<?php

namespace App\Livewire\PaymentManager\Datatable;

use App\Classes\Column;
use App\Classes\ExportDataTableComponent;
use App\Models\OpayPaymentLog;
use App\Traits\SimpleDatatableComponentTrait;
use Illuminate\Database\Eloquent\Builder;

class OpayPaymentLogsDatatable extends ExportDataTableComponent
{
    use SimpleDatatableComponentTrait;

    public array $additionalSelect = [];

    public array $filters = [];

    public bool $perPageAll = true;

    protected $model = OpayPaymentLog::class;

    public function builder(): Builder
    {
        return OpayPaymentLog::query()
            ->with(['invoice', 'creditpaymentlog', 'customer', 'user', 'payment'])
            ->select('opay_payment_logs.*')
            ->filterdata($this->filters)
            ->orderByDesc('opay_payment_logs.id');
    }

    public static function mountColumn(): array
    {
        return [
            Column::make("Date / Time", "created_at")
                ->format(fn($value, $row, Column $column) => $row->created_at ? $row->created_at->format('Y-m-d h:i:s A') : '')
                ->sortable(),

            Column::make("Type", "invoice_id")
                ->format(function ($value, $row, Column $column) {
                    if ($row->invoice_id) {
                        return '<span class="badge bg-primary">Invoice</span>';
                    }
                    if ($row->creditpaymentlog_id) {
                        return '<span class="badge bg-info">Credit Payment</span>';
                    }
                    return '<span class="badge bg-secondary">Attempt</span>';
                })
                ->html(),

            Column::make("Reference #", "order_no")
                ->format(function ($value, $row, Column $column) {
                    if ($row->invoice) {
                        return $row->invoice->invoice_number;
                    }
                    if ($row->creditpaymentlog) {
                        return $row->creditpaymentlog->credit_number;
                    }
                    return $value ?? '-';
                })
                ->searchable()
                ->sortable(),

            Column::make("Customer", "customer.firstname")
                ->format(fn($value, $row, Column $column) => trim(($row->customer->firstname ?? '') . ' ' . ($row->customer->lastname ?? '')) ?: 'Walk-in')
                ->searchable()
                ->sortable(),

            Column::make("Transaction #", "transaction_number")
                ->format(fn($value, $row, Column $column) => $row->transaction_number ?: '-')
                ->searchable()
                ->sortable(),

            Column::make("OPay Order #", "order_number")
                ->format(fn($value, $row, Column $column) => $row->order_number ?: '-')
                ->searchable()
                ->sortable(),

            Column::make("Terminal SN", "terminal_sn")
                ->format(fn($value, $row, Column $column) => $row->terminal_sn ?: '-')
                ->searchable()
                ->sortable(),

            Column::make("Channel / Card", "cashier_method")
                ->format(function ($value, $row, Column $column) {
                    $parts = array_filter([
                        $row->cashier_method ? strtoupper($row->cashier_method) : null,
                        $row->card_type,
                        $row->card_number,
                    ]);
                    return !empty($parts) ? implode(' · ', $parts) : '-';
                })
                ->searchable()
                ->sortable(),

            Column::make("Amount", "amount")
                ->format(fn($value, $row, Column $column) => money($row->amount))
                ->footer(function ($rows) {
                    return money($rows->where('status', 'success')->sum('amount'));
                })
                ->searchable()
                ->sortable(),

            Column::make("Status", "status")
                ->format(function ($value, $row, Column $column) {
                    $status = strtolower((string) $row->status);
                    $badge = match ($status) {
                        'success' => 'bg-success',
                        'cancelled' => 'bg-warning text-dark',
                        'pending' => 'bg-info',
                        default => 'bg-danger',
                    };
                    $label = strtoupper($status ?: 'UNKNOWN');
                    $code = $row->response_code ? " ({$row->response_code})" : '';
                    $msg = $row->response_message ? '<br/><small class="text-muted">' . e($row->response_message) . '</small>' : '';
                    return '<span class="badge ' . $badge . '">' . e($label . $code) . '</span>' . $msg;
                })
                ->sortable()
                ->html(),

            Column::make("Cashier", "user.name")
                ->format(fn($value, $row, Column $column) => $row->user->name ?? '-')
                ->searchable()
                ->sortable(),

            Column::make("Action", "payment_id")
                ->format(function ($value, $row, Column $column) {
                    if ($row->payment_id) {
                        return '<a href="' . route('payment.show', $row->payment_id) . '" class="btn btn-sm btn-outline-primary">View Payment</a>';
                    }
                    return '-';
                })
                ->html(),
        ];
    }
}

