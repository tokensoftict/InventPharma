<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use App\Enums\KafkaAction;
use App\Enums\KafkaTopics;
use App\Jobs\PushDataServer;
use App\Traits\ModelFilterTraits;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Purchase
 *
 * @property int $id
 * @property int|null $status_id
 * @property int|null $user_id
 * @property int|null $completed_by
 * @property int|null $supplier_id
 * @property string $department
 * @property Carbon|null $date_created
 * @property Carbon|null $date_completed
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property User|null $user
 * @property Status|null $status
 * @property Supplier|null $supplier
 * @property Collection|Purchaseitem[] $purchaseitems
 *
 * @package App\Models
 */
class Purchase extends Model
{


    protected $table = 'purchases';

    use ModelFilterTraits;

    protected $casts = [
        'status_id' => 'int',
        'user_id' => 'int',
        'completed_by' => 'int',
        'supplier_id' => 'int',
        'date_created' => 'datetime',
        'date_completed' => 'datetime'
    ];

    protected $fillable = [
        'status_id',
        'user_id',
        'completed_by',
        'supplier_id',
        'department',
        'date_created',
        'date_completed',
        'created_by'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function complete_by()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function create_by()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseitems()
    {
        return $this->hasMany(Purchaseitem::class);
    }

    public function paymentHistories()
    {
        return $this->hasMany(SupplierCreditPaymentHistory::class, 'purchase_id');
    }

    public function payments()
    {
        return $this->hasMany(SupplierCreditPaymentHistory::class, 'purchase_id')
            ->where('type', 'PAYMENT');
    }

    public function activePayments()
    {
        return $this->hasMany(SupplierCreditPaymentHistory::class, 'purchase_id')
            ->where('type', 'PAYMENT')
            ->where(function ($query) {
                $query->whereNull('status')->orWhere('status', '!=', 'Reversed');
            })
            ->whereNull('reversed_at');
    }

    public function stockExchangeAllocations()
    {
        return $this->hasMany(SupplierCreditPaymentHistory::class, 'purchase_id')
            ->where('type', 'PAYMENT')
            ->whereNotNull('invoice_id');
    }

    public function calculateTotalAmount(): float
    {
        if (!$this->exists) {
            return 0.0;
        }

        if (\Illuminate\Support\Facades\Schema::hasTable('purchaseitems')) {
            $itemsTotal = (float) $this->purchaseitems()
                ->toBase()
                ->selectRaw('COALESCE(SUM(qty * COALESCE(cost_price, 0)), 0) as items_total')
                ->value('items_total');

            if ($itemsTotal > 0) {
                return round($itemsTotal, 5);
            }
        }

        if (\Illuminate\Support\Facades\Schema::hasTable('supplier_credit_payment_histories')) {
            $creditRecordTotal = (float) $this->paymentHistories()
                ->toBase()
                ->where('type', 'CREDIT')
                ->selectRaw('COALESCE(SUM(ABS(amount)), 0) as credit_total')
                ->value('credit_total');

            return round($creditRecordTotal, 5);
        }

        return 0.0;
    }

    public function getTotalAmountAttribute(): float
    {
        return $this->calculateTotalAmount();
    }

    public function calculateTotalPaid(): float
    {
        if (!$this->exists || !\Illuminate\Support\Facades\Schema::hasTable('supplier_credit_payment_histories')) {
            return 0.0;
        }

        $payments = $this->payments()->get();

        $activeTotal = $payments->filter(function ($item) {
            if ($item->status === 'Reversed' || !is_null($item->reversed_at)) {
                return false;
            }
            if (isset($item->payment_info['status']) && in_array($item->payment_info['status'], ['Declined', 'Reversed'], true)) {
                return false;
            }
            return true;
        })->sum('amount');

        return max(0.0, round((float) $activeTotal, 5));
    }

    public function getTotalPaidAttribute(): float
    {
        return $this->calculateTotalPaid();
    }

    public function calculateOutstandingBalance(): float
    {
        return max(0.0, round($this->calculateTotalAmount() - $this->calculateTotalPaid(), 5));
    }

    public function getOutstandingBalanceAttribute(): float
    {
        return $this->calculateOutstandingBalance();
    }

    public function getPaymentStatusAttribute(): string
    {
        $total = $this->calculateTotalAmount();
        $paid = $this->calculateTotalPaid();

        if (round($paid, 2) <= 0) {
            return 'Unpaid';
        }

        if (round($total - $paid, 2) > 0) {
            return 'Partially Paid';
        }

        return 'Paid';
    }

    public function isEligibleForPayment(): bool
    {
        if (!$this->exists || is_null($this->supplier_id)) {
            return false;
        }

        $ineligibleStatuses = array_values(array_filter([
            status('Cancelled'),
            status('Deleted'),
            status('Declined'),
            status('Pre-Draft'),
            status('Draft'),
        ]));

        if (!is_null($this->status_id) && in_array((int) $this->status_id, $ineligibleStatuses, true)) {
            return false;
        }

        if (round($this->calculateTotalAmount(), 2) <= 0) {
            return false;
        }

        if (round($this->calculateOutstandingBalance(), 2) <= 0) {
            return false;
        }

        return true;
    }


    public function newonlinePush()
    {
        $this->performOnlinePush();
    }

    public function updateonlinePush()
    {
        $this->performOnlinePush();
    }


    public function performOnlinePush()
    {
        if ($this->status_id === status('Complete')) {

            $data = [];
            foreach ($this->purchaseitems as $purchaseitem) {
                if ($purchaseitem->stock->bulk_price > 0 || $purchaseitem->stock->retail_price > 0) {

                    if (isset($data[$purchaseitem->stock_id])) {
                        $data[$purchaseitem->stock_id]['qty'] += $purchaseitem->qty;
                    } else {
                        $data[$purchaseitem->stock_id] = [
                            'stock_id' => $purchaseitem->stock_id,
                            'po_id' => $purchaseitem->purchase->id,
                            'qty' => $purchaseitem->qty,
                        ];
//                        dispatch(new PushLocalDataServer(['action' => 'update', 'table' => 'new_arrivals', 'data' => [
//                            'stock_id' => $purchaseitem->stock_id,
//                            'po_id' => $purchaseitem->purchase->id,
//                            'qty' => $purchaseitem->qty,
//                        ]]));
                    }
                }
            }

            $store = $this->department == "quantity" ? 5 : 6;
            dispatch(new PushDataServer(['KAFKA_ACTION' => KafkaAction::NEW_ARRIVAL, 'KAFKA_TOPICS' => KafkaTopics::GENERAL, 'store' => $store, 'action' => 'update', 'endpoint' => 'new_arrivals', 'table' => 'new_arrivals', 'data' => $data]));

        }
    }

    public static function pushMissedToLocalServer() {
        $purchases = Purchase::with(['purchaseitems'])->whereIn('id', range(23080, 23080))->get();
        foreach ($purchases as $purchase) {
            foreach ($purchase->purchaseitems as  $purchaseitem) {
                if ($purchaseitem->stock->bulk_price > 0 || $purchaseitem->stock->retail_price > 0) {
//                    dispatch(new PushLocalDataServer(['action' => 'update', 'table' => 'new_arrivals', 'data' => [
//                        'stock_id' => $purchaseitem->stock_id,
//                        'po_id' => $purchaseitem->purchase->id,
//                        'qty' => $purchaseitem->qty,
//                    ]]));
                }
            }
        }
    }

}
