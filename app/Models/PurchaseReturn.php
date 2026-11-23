<?php

namespace App\Models;

use App\Traits\ModelFilterTraits;
use Illuminate\Database\Eloquent\Model;

class PurchaseReturn extends Model
{
    use ModelFilterTraits;

    protected $table = 'purchase_returns';

    protected $casts = [
        'status_id' => 'int',
        'supplier_id' => 'int',
        'purchase_id' => 'int',
        'user_id' => 'int',
        'created_by' => 'int',
        'completed_by' => 'int',
        'date_created' => 'datetime',
        'date_completed' => 'datetime',
    ];

    protected $fillable = [
        'status_id', 'supplier_id', 'purchase_id', 'department', 'date_created', 'date_completed',
        'remark', 'notes', 'user_id', 'created_by', 'completed_by',
    ];

    public function user() { return $this->belongsTo(User::class, 'user_id'); }

    public function complete_by() { return $this->belongsTo(User::class, 'completed_by'); }

    public function completedBy() { return $this->belongsTo(User::class, 'completed_by'); }

    public function status() { return $this->belongsTo(Status::class); }

    public function supplier() { return $this->belongsTo(Supplier::class); }

    public function purchase() { return $this->belongsTo(Purchase::class); }

    public function items() { return $this->hasMany(PurchaseReturnItem::class); }

    public function ledgerEntries() { return $this->hasMany(SupplierCreditPaymentHistory::class, 'purchase_return_id'); }

    public function calculateTotalAmount(): float
    {
        return round((float) $this->items()->selectRaw('COALESCE(SUM(qty * COALESCE(cost_price,0)),0) as t')->value('t'), 5);
    }

    public function getTotalAmountAttribute(): float
    {
        return $this->calculateTotalAmount();
    }

    public function isComplete(): bool
    {
        return (int) $this->status_id === (int) status('Complete');
    }
}
