<?php

namespace App\Livewire\PurchaseOrder\Supplier\Payment\Datatable;

use App\Classes\ExportDataTableComponent;
use App\Traits\SimpleDatatableComponentTrait;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\SupplierCreditPaymentHistory;

class SupplierPaymentDatatable extends ExportDataTableComponent
{
    use SimpleDatatableComponentTrait;

    protected $model = SupplierCreditPaymentHistory::class;

    public array $filters = [];
    public array $additionalSelects = [];

    public function builder(): Builder
    {
        return SupplierCreditPaymentHistory::query()->select('*')->with(['user', 'paymentmethod', 'purchase', 'supplier'])->filterdata($this->filters)->orderBy('id', 'DESC');
    }


    public static function mountColumn() : array
    {
        return [
            Column::make("Supplier", "supplier_id")
                ->format(fn($value, $row, Column $column) => '<a target="new" href="' . route('supplier.show', $row->supplier->id) . '"><strong>' . $row->supplier->name . '</strong></a>')
                ->html()
                ->sortable()
                ->searchable(),
            Column::make("Type", "type")
                ->sortable(),
            Column::make("Payment Method", "paymentmethod_id")
                ->format(function($value, $row, Column $column){
                    if($row->paymentmethod_id === 8)
                    {
                        $chequeDate = $row->payment_info['cheque_date'] ?? '';
                        $dateOfIssued = $row->payment_info['date_of_issued'] ?? '';
                        $status = $row->payment_info['status'] ?? 'Pending';
                        $statusBadge = '<span class="badge badge-soft-' . ($status === 'Approved' ? 'success' : ($status === 'Declined' ? 'danger' : 'warning')) . ' ms-1">' . $status . '</span>';

                        return $row->paymentmethod->name . " (Issued: " . $dateOfIssued . ", Cheque: " . $chequeDate . ") " . $statusBadge;
                    }

                    return $row->paymentmethod->name ?? "";
                })
                ->html()
                ->sortable(),
            Column::make("Amount", "amount")
                ->format(fn($value, $row, Column $column) =>money($row->amount))
                ->footer(function($rows){
                    return money($rows->sum('amount'));
                })
                ->sortable(),
            Column::make("Payment date", "payment_date")
                ->format(fn($value, $row, Column $column) => eng_str_date($value))
                ->sortable(),
            Column::make("Remark", "remark"),
            Column::make("Created By", "user_id")
                ->format(fn($value, $row, Column $column) =>  $row->user->name ?? "")
                ->sortable(),
            Column::make("Last Modified", "updated_at")
                ->sortable(),
            Column::make("Action","id")
                ->format(function($value, $row, Column $column) {
                    $html = "No Action";

                    if(can(['edit', 'delete'], $row)){

                        $html = '<div class="dropdown"><button class="btn btn-link font-size-16 shadow-none py-0 text-muted dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bx bx-dots-horizontal-rounded"></i></button>';
                        $html .= '<ul class="dropdown-menu dropdown-menu-end">';

                        $html .= '<li><a href="' . route('supplier.payment.show', $row->id) . '" class="dropdown-item">View Payment</a></li>';

                        if (auth()->user()->can("update", $row)) {
                            $html .= '<li><a href="' . route('supplier.payment.edit', $row->id) . '" class="dropdown-item">Edit Payment</a></li>';
                        }

                        if (auth()->user()->can("delete", $row)) {
                            $html .= '<li><a href="#" wire:click.prevent="delete('.$value.')"  onclick="confirm(\'Are you sure you want to delete this expense ?, this can not be reversed\') || event.stopImmediatePropagation()"  class="dropdown-item">Delete Payment</a></li>';
                        }


                        $html .= '</ul></div>';
                    }

                    return $html;
                }) ->html()
        ];
    }

    public function delete(SupplierCreditPaymentHistory $supplierCreditPaymentHistory)
    {
        if($supplierCreditPaymentHistory) $supplierCreditPaymentHistory->delete();

        $this->alert(
            "success",
            "Expenses",
            [
                'position' => 'center',
                'timer' => 6000,
                'toast' => false,
                'text' =>  "Payment has been deleted successfully!.",
            ]
        );

        return redirect()->route('supplier.payment.index');
    }

    public function approveCheque($id)
    {
        $payment = SupplierCreditPaymentHistory::findOrFail($id);
        $payment_info = $payment->payment_info;
        $payment_info['status'] = 'Approved';
        $payment->payment_info = $payment_info;
        $payment->save();

        $this->alert(
            "success",
            "Cheque Approval",
            [
                'position' => 'center',
                'timer' => 6000,
                'toast' => false,
                'text' => 'Cheque payment approved successfully!',
            ]
        );
    }

    public function declineCheque($id)
    {
        $payment = SupplierCreditPaymentHistory::findOrFail($id);
        $payment_info = $payment->payment_info;
        $payment_info['status'] = 'Declined';
        $payment->payment_info = $payment_info;
        $payment->save();

        $this->alert(
            "warning",
            "Cheque Approval",
            [
                'position' => 'center',
                'timer' => 6000,
                'toast' => false,
                'text' => 'Cheque payment declined successfully!',
            ]
        );
    }


}
