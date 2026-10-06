<?php

namespace App\Models;

use App\Traits\ModelFilterTraits;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Class PurchaseLimit
 *
 * @property int $id
 * @property string|null $name
 * @property string $department
 * @property int $max_quantity
 * @property int $period_value
 * @property string $period_unit
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property bool $is_active
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property User|null $creator
 * @property User|null $updater
 * @property Collection|Stock[] $stocks
 *
 * @package App\Models
 */
class PurchaseLimit extends Model
{
    use ModelFilterTraits;

    protected $table = 'purchase_limits';

    protected $casts = [
        'max_quantity' => 'int',
        'period_value' => 'int',
        'is_active' => 'bool',
        'start_date' => 'date',
        'end_date' => 'date',
        'created_by' => 'int',
        'updated_by' => 'int',
    ];

    protected $fillable = [
        'name',
        'department',
        'max_quantity',
        'period_value',
        'period_unit',
        'start_date',
        'end_date',
        'is_active',
        'created_by',
        'updated_by',
    ];

    /**
     * The user who created the limit.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The user who last updated the limit.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Products associated with this purchase limit.
     */
    public function stocks(): BelongsToMany
    {
        return $this->belongsToMany(Stock::class, 'purchase_limit_products', 'purchase_limit_id', 'stock_id')
            ->withTimestamps();
    }

    /**
     * Scope: only active rules that are within their date range.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('start_date')
                    ->orWhere('start_date', '<=', Carbon::today());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', Carbon::today());
            });
    }

    /**
     * Scope: filter limits by department (retail vs wholesales).
     */
    public function scopeForDepartment($query, string $department)
    {
        $dept = strtolower(trim($department));
        $target = in_array($dept, ['retail', 'retail_store']) ? 'retail' : 'wholesales';
        return $query->where('department', $target);
    }

    /**
     * Convert period_value + period_unit to total days.
     */
    public function getPeriodInDays(): int
    {
        return match ($this->period_unit) {
            'weeks' => $this->period_value * 7,
            'months' => $this->period_value * 30,
            default => $this->period_value, // 'days'
        };
    }

    /**
     * Check if this rule is currently active (considering is_active, start_date, end_date).
     */
    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $today = Carbon::today();

        if ($this->start_date && $today->lt($this->start_date)) {
            return false;
        }

        if ($this->end_date && $today->gt($this->end_date)) {
            return false;
        }

        return true;
    }

    /**
     * Human-readable period string, e.g. "20 units / 14 days".
     */
    public function getPeriodLabelAttribute(): string
    {
        return $this->max_quantity . ' units / ' . $this->period_value . ' ' . $this->period_unit;
    }
}
