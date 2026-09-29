<?php

namespace App\Models;

use App\Traits\ModelFilterTraits;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class PurchaseLimitProduct
 *
 * @property int $id
 * @property int $purchase_limit_id
 * @property int $stock_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property PurchaseLimit $purchaseLimit
 * @property Stock $stock
 *
 * @package App\Models
 */
class PurchaseLimitProduct extends Model
{
    use ModelFilterTraits;

    protected $table = 'purchase_limit_products';

    protected $casts = [
        'purchase_limit_id' => 'int',
        'stock_id' => 'int',
    ];

    protected $fillable = [
        'purchase_limit_id',
        'stock_id',
    ];

    /**
     * The purchase limit rule this pivot belongs to.
     */
    public function purchaseLimit(): BelongsTo
    {
        return $this->belongsTo(PurchaseLimit::class);
    }

    /**
     * The product (stock) this pivot refers to.
     */
    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }
}
