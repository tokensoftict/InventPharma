<?php

namespace App\Repositories;

use App\Jobs\AddLogToProductBinCard;
use App\Jobs\PushStockUpdateToServer;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Stock;
use App\Models\Stockbatch;
use App\Models\SupplierCreditPaymentHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Opposite of PurchaseOrderRepository: stock goes OUT of the store back to the supplier,
 * and the amount reduces what the company owes the supplier (positive RETURN ledger entry).
 */
class PurchaseReturnRepository
{
    public static function purchaseReturn(PurchaseReturn $return): array
    {
        if (isset($return->id)) {
            return [
                'status_id' => $return->status_id,
                'user_id' => $return->user_id,
                'supplier_id' => $return->supplier_id,
                'purchase_id' => $return->purchase_id,
                'department' => $return->department,
                'date_created' => mysql_str_date($return->date_created),
                'remark' => $return->remark,
                'notes'  => $return->remark,
                'items' => $return->items->map(fn ($i) => [
                    'stock_id' => $i->stock_id,
                    'name' => $i->name,
                    'batch_no' => $i->batch_no,
                    'expiry_date' => $i->expiry_date?->toDateString(),
                    'qty' => $i->qty,
                    'cost_price' => $i->cost_price,
                    'reason' => $i->reason,
                    'total' => $i->qty * $i->cost_price,
                ])->values()->toJson(),
            ];
        }

        return [
            'status_id' => status('Draft'),
            'user_id' => auth()->id(),
            'supplier_id' => '',
            'purchase_id' => null,
            'department' => '',
            'date_created' => todaysDate(),
            'remark' => '',
            'items' => collect([])->toJson(),
        ];
    }

    /**
     * Create or update a DRAFT purchase return together with its items.
     */
    public function save(PurchaseReturn $return, array $data): PurchaseReturn
    {
        $items = is_string($data['items']) ? json_decode($data['items'], true) : $data['items'];
        $items = $items ?? [];

        if (empty($items)) {
            throw ValidationException::withMessages(['items' => 'Please add at least one item to return.']);
        }

        if (empty($data['supplier_id'])) {
            throw ValidationException::withMessages(['supplier_id' => 'Please select a supplier.']);
        }

        if (empty($data['department'])) {
            throw ValidationException::withMessages(['department' => 'Please select a department.']);
        }

        $header = [
            'status_id' => status('Draft'),
            'supplier_id' => $data['supplier_id'],
            'purchase_id' => $data['purchase_id'] ?: null,
            'department' => $data['department'],
            'date_created' => $data['date_created'] ?? todaysDate(),
            'remark' => $data['remark'] ?? $data['notes'] ?? null,
            'user_id' => $data['user_id'] ?? auth()->id(),
        ];

        return DB::transaction(function () use ($return, $header, $items) {
            if (isset($return->id)) {
                if ($return->isComplete()) {
                    throw ValidationException::withMessages(['items' => 'A completed purchase return cannot be edited.']);
                }
                $return->update($header);
                $return->items()->delete();
            } else {
                $header['created_by'] = auth()->id();
                $return = PurchaseReturn::create($header);
            }

            foreach ($items as $item) {
                $qty = (int) ($item['qty'] ?? 0);
                if (empty($item['stock_id']) || $qty <= 0) {
                    throw ValidationException::withMessages(['items' => 'Every item needs a product and a quantity greater than zero.']);
                }

                $return->items()->save(new PurchaseReturnItem([
                    'stock_id' => $item['stock_id'],
                    'batch_no' => $item['batch_no'] ?: null,
                    'expiry_date' => !empty($item['expiry_date']) ? $item['expiry_date'] : null,
                    'qty' => $qty,
                    'cost_price' => (float) ($item['cost_price'] ?? 0),
                    'reason' => $item['reason'] ?? null,
                    'user_id' => auth()->id(),
                ]));
            }

            return $return->fresh(['items']);
        });
    }

    /**
     * Complete the return: deduct the stock from batches, log bin cards and reduce the supplier balance.
     * All-or-nothing: if any product has insufficient stock nothing is changed.
     */
    public function complete(PurchaseReturn $purchaseReturn): PurchaseReturn
    {
        $bincards = [];
        $stockIds = [];

        $result = DB::transaction(function () use ($purchaseReturn, &$bincards, &$stockIds) {
            $return = PurchaseReturn::where('id', $purchaseReturn->id)->lockForUpdate()->first();

            if (!$return) {
                throw ValidationException::withMessages(['purchase_return' => 'Purchase return not found.']);
            }

            if ($return->isComplete()) {
                throw ValidationException::withMessages(['purchase_return' => 'This purchase return has already been completed.']);
            }

            $items = $return->items()->get();
            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'There are no items on this purchase return.']);
            }

            if (empty($return->supplier_id)) {
                throw ValidationException::withMessages(['supplier_id' => 'A supplier is required.']);
            }

            $column = $return->department; // quantity | retail | wholesales | bulksales ...

            foreach ($items as $item) {
                $stock = Stock::withoutGlobalScopes()->where('id', $item->stock_id)->lockForUpdate()->first();
                if (!$stock) {
                    throw ValidationException::withMessages(['items' => "Product #{$item->stock_id} no longer exists."]);
                }

                $batches = Stockbatch::where('stock_id', $stock->id)
                    ->where($column, '>', 0)
                    ->lockForUpdate()
                    ->orderByRaw('CASE WHEN batch_no = ? THEN 0 ELSE 1 END', [$item->batch_no ?? ''])
                    ->orderBy('expiry_date', 'ASC')
                    ->get();

                $available = (int) $batches->sum($column);
                if ($available < $item->qty) {
                    throw ValidationException::withMessages([
                        'items' => "Only {$available} unit(s) of {$stock->name} are available in this department; cannot return {$item->qty}.",
                    ]);
                }

                $remaining = (int) $item->qty;
                foreach ($batches as $batch) {
                    if ($remaining <= 0) break;

                    $take = min($remaining, (int) $batch->{$column});
                    $batch->{$column} = $batch->{$column} - $take;
                    $batch->save();
                    $remaining -= $take;

                    $stock->refresh();
                    $stock->updateQuantity();

                    $bincards[] = [
                        'bin_card_type' => 'APP//PURCHASE-RETURN',
                        'bin_card_date' => todaysDate(),
                        'user_id' => Auth::id(),
                        'out_qty' => $take,
                        'in_qty' => 0,
                        'stock_id' => $stock->id,
                        'stockbatch_id' => $batch->id,
                        'from_department' => $column,
                        'supplier_id' => $return->supplier_id,
                        'purchase_id' => $return->purchase_id,
                        'comment' => "Stock returned to supplier - Purchase Return #{$return->id} by " . (Auth::user()->name ?? 'System'),
                        'balance' => $stock->totalBalance(),
                        'department_balance' => $stock->getCurrentlevel($column),
                    ];
                }

                $stockIds[] = $stock->id;
            }

            $total = round((float) $items->sum(fn ($i) => $i->qty * $i->cost_price), 5);

            $return->status_id = status('Complete');
            $return->date_completed = todaysDate();
            $return->completed_by = auth()->id();
            $return->save();

            // Reduces what the company owes the supplier (positive entry against the negative credit balance)
            SupplierCreditPaymentHistory::create([
                'user_id' => auth()->id(),
                'supplier_id' => $return->supplier_id,
                'type' => 'RETURN',
                'purchase_id' => $return->purchase_id,
                'purchase_return_id' => $return->id,
                'paymentmethod_id' => null,
                'reference' => 'PRT-' . $return->id,
                'amount' => $total,
                'status' => 'Active',
                'payment_date' => $return->date_completed,
                'payment_info' => ['status' => 'Approved', 'method' => 'Purchase Return', 'purchase_return_id' => $return->id],
                'remark' => "Purchase Return #{$return->id}" . ($return->remark ? ' - ' . $return->remark : ''),
            ]);

            return $return;
        });

        if (!empty($bincards)) {
            dispatch(new AddLogToProductBinCard($bincards));
        }

        if (!empty($stockIds) && config('app.sync_with_online') == 1) {
            dispatch(new PushStockUpdateToServer(array_values(array_unique($stockIds))));
        }

        return $result->fresh(['items', 'supplier']);
    }

    public function delete(PurchaseReturn $return): bool
    {
        if ($return->isComplete()) {
            throw ValidationException::withMessages(['purchase_return' => 'A completed purchase return cannot be deleted.']);
        }

        return (bool) $return->delete();
    }
}
