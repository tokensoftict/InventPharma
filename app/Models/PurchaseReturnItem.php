<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturnItem extends Model
{
    protected $table = 'purchase_return_items';

    protected $casts = [
        'purchase_return_id' => 'int',
        'stock_id' => 'int',
        'qty' => 'int',
        'cost_price' => 'float',
        'user_id' => 'int',
        'expiry_date' => 'date',
    ];

    protected $fillable = [
        'purchase_return_id', 'stock_id', 'batch_no', 'expiry_date', 'qty', 'cost_price', 'reason', 'user_id',
    ];

    protected $appends = ['name', 'total'];

    public function getNameAttribute() { return $this->stock->name ?? ''; }

    public function getTotalAttribute() { return $this->qty * $this->cost_price; }

    public function purchaseReturn() { return $this->belongsTo(PurchaseReturn::class); }

    public function stock() { return $this->belongsTo(Stock::class); }

    public function user() { return $this->belongsTo(User::class); }
}
