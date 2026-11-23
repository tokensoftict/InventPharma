<?php

namespace App\Livewire\PurchaseReturn\Datatable;

use App\Classes\Column;
use App\Classes\ExportDataTableComponent;
use App\Classes\Settings;
use App\Models\PurchaseReturn;
use App\Repositories\PurchaseReturnRepository;
use App\Traits\SimpleDatatableComponentTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class PurchaseReturnDatatable extends ExportDataTableComponent
{
    use SimpleDatatableComponentTrait;

    protected $model = PurchaseReturn::class;

    public array $filters = [];

    public array $additionalSelects = [];

    public function builder(): Builder
    {
        return PurchaseReturn::query()->select('purchase_returns.*')->with(['items', 'supplier', 'user', 'status'])
            ->filterdata($this->filters)
            ->orderBy('purchase_returns.id', 'DESC');
    }

    public static function mountColumn(): array
    {
        return [
            Column::make("Return #", "id")
                ->sortable(),
            Column::make("Department", "department")
                ->format(fn($value, $row, Column $column) => Settings::$department[$value] ?? $value)
                ->sortable(),
            Column::make("Supplier", "supplier.name")
                ->sortable(),
            Column::make("Status", "status.name")
                ->format(fn($value, $row, Column $column) => showStatus($row->status_id))->html()
                ->sortable(),
            Column::make("Total Returned")
                ->label(fn($row, Column $column) => money($row->items->sum(fn($item) => $item->cost_price * $item->qty))),
            Column::make("Return Date", "date_created")
                ->format(fn($value, $row, Column $column) => eng_str_date($value))
                ->sortable(),
            Column::make("Created By", "user.name")
                ->format(fn($value, $row, Column $column) => $value)
                ->sortable(),
            Column::make("Action", "id")
                ->format(function ($value, $row, Column $column) {
                    $html = "No Action";

                    if (can(['view', 'update', 'delete', 'complete'], $row)) {
                        $html = '<div class="dropdown"><button class="btn btn-link font-size-16 shadow-none py-0 text-muted dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bx bx-dots-horizontal-rounded"></i></button>';
                        $html .= '<ul class="dropdown-menu dropdown-menu-end">';

                        if (auth()->user()->can('view', $row)) {
                            $html .= '<li><a href="' . route('purchasereturn.show', $row->id) . '" class="dropdown-item">View Return</a></li>';
                        }

                        if (auth()->user()->can('update', $row)) {
                            $html .= '<li><a href="' . route('purchasereturn.edit', $row->id) . '" class="dropdown-item">Edit Return</a></li>';
                        }

                        if (auth()->user()->can('complete', $row)) {
                            $html .= '<li><a href="#" wire:click.prevent="complete(' . $value . ')" onclick="confirm(\'Are you sure you want to complete this return? The stock will be deducted and the supplier balance reduced. This can not be reversed\') || event.stopImmediatePropagation()" class="dropdown-item">Complete Return</a></li>';
                        }

                        if (auth()->user()->can('delete', $row)) {
                            $html .= '<li><a href="#" wire:click.prevent="delete(' . $value . ')" onclick="confirm(\'Are you sure you want to delete this return ?, this can not be reversed\') || event.stopImmediatePropagation()" class="dropdown-item">Delete Return</a></li>';
                        }

                        $html .= '</ul></div>';
                    }

                    return $html;
                })->html(),
        ];
    }

    public function complete(PurchaseReturn $purchaseReturn)
    {
        $this->authorize('complete', $purchaseReturn);

        try {
            (new PurchaseReturnRepository())->complete($purchaseReturn);
        } catch (ValidationException $e) {
            $this->alert('error', 'Purchase Return', [
                'position' => 'center', 'timer' => 6000, 'toast' => false,
                'text' => collect($e->errors())->flatten()->first(),
            ]);
            return;
        }

        return redirect()->route('purchasereturn.show', $purchaseReturn->id);
    }

    public function delete(PurchaseReturn $purchaseReturn)
    {
        $this->authorize('delete', $purchaseReturn);

        try {
            (new PurchaseReturnRepository())->delete($purchaseReturn);
        } catch (ValidationException $e) {
            $this->alert('error', 'Purchase Return', [
                'position' => 'center', 'timer' => 6000, 'toast' => false,
                'text' => collect($e->errors())->flatten()->first(),
            ]);
            return;
        }

        return redirect()->route('purchasereturn.index');
    }
}
