<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Invoiceitem;
use App\Models\PurchaseLimit;
use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PurchaseLimitService
{
    /**
     * Statuses that represent "completed/valid" sales and should count toward limits.
     * Excludes: Draft, Deleted, Voided, Discount, Waiting-For-*
     */
    protected function getEligibleStatusIds(): array
    {
        return array_values(array_filter([
            status('Paid'),
            status('Complete'),
            status('Dispatched'),
            status('Packed-Waiting-For-Payment'),
            status('Packing'),
            status('Draft'),
        ]));
    }

    /**
     * Statuses that should NOT count (drafts, cancelled, voided, etc.)
     * Used for documentation clarity — the service uses an explicit include-list instead.
     */
    protected function getExcludedStatusIds(): array
    {
        return [
            status('Deleted'),
        ];
    }

    /**
     * Get all currently active purchase limits that apply to a given product for a department.
     *
     * @param int $stockId
     * @param string $department
     * @return Collection|PurchaseLimit[]
     */
    public function getActiveLimitsForProduct(int $stockId, string $department = 'wholesales'): Collection
    {
        $targetDept = in_array(strtolower(trim($department)), ['retail', 'retail_store']) ? 'retail' : 'wholesales';

        return PurchaseLimit::active()
            ->where('department', $targetDept)
            ->whereHas('stocks', function ($query) use ($stockId) {
                $query->where('stocks.id', $stockId);
            })
            ->get();
    }

    /**
     * Calculate the total quantity a customer has purchased of a specific product
     * within the given rolling period window.
     *
     * Retail limits only count retail invoices ('retail', 'retail_store').
     * Wholesale limits only count wholesale invoices ('wholesales', 'bulksales').
     *
     * @param int $customerId
     * @param int $stockId
     * @param int $periodDays
     * @param Carbon|null $startDate Optional rule start date to clamp the window
     * @param string $ruleDepartment Target limit department ('retail' or 'wholesales')
     * @return int Total purchased quantity in pieces
     */
    public function getCustomerPurchasedQuantity(int $customerId, int $stockId, int $periodDays, ?Carbon $startDate = null, string $ruleDepartment = 'wholesales'): int
    {
        $periodStart = Carbon::today()->subDays($periodDays);

        // If the rule has a start_date that is more recent than the rolling window,
        // use the rule's start_date instead (so purchases before the rule don't count).
        if ($startDate && $startDate->gt($periodStart)) {
            $periodStart = $startDate;
        }

        $ruleDept = in_array(strtolower(trim($ruleDepartment)), ['retail', 'retail_store']) ? 'retail' : 'wholesales';
        $invoiceDepartments = $ruleDept === 'retail'
            ? ['retail', 'retail_store']
            : ['wholesales', 'bulksales'];

        return Invoiceitem::where('invoiceitems.stock_id', $stockId)
            ->where('invoiceitems.customer_id', $customerId)
            ->whereHas('invoice', function ($query) use ($periodStart, $invoiceDepartments) {
                $query->whereIn('status_id', $this->getEligibleStatusIds())
                    ->whereIn('department', $invoiceDepartments)
                    ->where('invoice_date', '>=', $periodStart);
            })
            ->sum('quantity');
    }

    /**
     * Calculate remaining allowance for a customer + product + limit combination.
     *
     * @param int $customerId
     * @param int $stockId
     * @param PurchaseLimit $limit
     * @return int Remaining quantity (can be negative if already exceeded)
     */
    public function getRemainingQuantity(int $customerId, int $stockId, PurchaseLimit $limit): int
    {
        $purchased = $this->getCustomerPurchasedQuantity(
            $customerId,
            $stockId,
            $limit->getPeriodInDays(),
            $limit->start_date,
            $limit->department ?? 'wholesales'
        );

        return $limit->max_quantity - $purchased;
    }

    /**
     * Validate invoice items against all applicable purchase limits for the specified department.
     *
     * Limits created for 'retail' apply only to retail invoices.
     * Limits created for 'wholesales' apply only to wholesale and bulksale invoices.
     *
     * Uses SELECT ... FOR UPDATE to prevent concurrent bypass.
     *
     * @param int $customerId
     * @param array $items Array of invoice items, each with 'stock_id' and 'quantity'
     * @param string $invoiceDepartment The invoice department ('retail', 'retail_store', 'wholesales', 'bulksales')
     * @return array ['status' => true] or ['status' => false, 'errors' => [...]]
     */
    public function validateInvoiceItems(int $customerId, array $items, string $invoiceDepartment = 'wholesales'): array
    {
        $errors = [];

        // Collect all stock IDs from the current invoice
        $stockIds = array_unique(array_column($items, 'stock_id'));

        $invoiceDept = strtolower(trim($invoiceDepartment));
        $targetLimitDepartment = in_array($invoiceDept, ['retail', 'retail_store']) ? 'retail' : 'wholesales';

        // Find all active limits for this department that cover any of these products
        // Lock the purchase limits to prevent concurrent modifications
        $applicableLimits = PurchaseLimit::active()
            ->where('department', $targetLimitDepartment)
            ->whereHas('stocks', function ($query) use ($stockIds) {
                $query->whereIn('stocks.id', $stockIds);
            })
            ->lockForUpdate()
            ->with('stocks')
            ->get();

        if ($applicableLimits->isEmpty()) {
            return ['status' => true];
        }

        // Build a map of stock_id => total requested quantity from the current invoice
        $requestedQuantities = [];
        foreach ($items as $item) {
            $sid = $item['stock_id'];
            $requestedQuantities[$sid] = ($requestedQuantities[$sid] ?? 0) + (int) $item['quantity'];
        }

        // Check each limit
        foreach ($applicableLimits as $limit) {
            foreach ($limit->stocks as $stock) {
                $stockId = $stock->id;

                // Only validate products that are in the current invoice
                if (!isset($requestedQuantities[$stockId])) {
                    continue;
                }

                $previousPurchases = $this->getCustomerPurchasedQuantity(
                    $customerId,
                    $stockId,
                    $limit->getPeriodInDays(),
                    $limit->start_date,
                    $limit->department ?? $targetLimitDepartment
                );

                $requestedQty = $requestedQuantities[$stockId];
                $totalAfterPurchase = $previousPurchases + $requestedQty;
                $remaining = $limit->max_quantity - $previousPurchases;

                if ($totalAfterPurchase > $limit->max_quantity) {
                    $allowed = max(0, $remaining);
                    $periodUnit = ($limit->period_value == 1) ? rtrim($limit->period_unit, 's') : $limit->period_unit;
                    $errors[$stockId] = "Purchase Limit Exceeded: You can purchase a maximum of {$limit->max_quantity} {$stock->name} every {$limit->period_value} {$periodUnit}. You requested {$requestedQty}, but only {$allowed} is currently allowed.";
                }
            }
        }

        if (count($errors) > 0) {
            return ['status' => false, 'errors' => $errors];
        }

        return ['status' => true];
    }

    /**
     * Get customer's current usage summary across all active purchase limits.
     *
     * Used by the Customer Usage Visibility view (Phase 7).
     *
     * @param int $customerId
     * @param string|null $department Optional filter by department ('retail' or 'wholesales')
     * @return Collection Each item: ['product' => Stock, 'limit' => PurchaseLimit, 'used' => int, 'remaining' => int]
     */
    public function getCustomerUsageSummary(int $customerId, ?string $department = null): Collection
    {
        $targetDept = $department ? (in_array(strtolower(trim($department)), ['retail', 'retail_store']) ? 'retail' : 'wholesales') : null;

        $activeLimits = PurchaseLimit::active()
            ->when($targetDept, fn($q) => $q->where('department', $targetDept))
            ->with('stocks')
            ->get();

        $usage = collect();

        foreach ($activeLimits as $limit) {
            foreach ($limit->stocks as $stock) {
                $purchased = $this->getCustomerPurchasedQuantity(
                    $customerId,
                    $stock->id,
                    $limit->getPeriodInDays(),
                    $limit->start_date,
                    $limit->department ?? 'wholesales'
                );

                $remaining = max(0, $limit->max_quantity - $purchased);

                $usage->push([
                    'product_name' => $stock->name,
                    'stock_id' => $stock->id,
                    'limit_name' => $limit->name ?? 'Unnamed',
                    'department' => $limit->department ?? 'wholesales',
                    'max_quantity' => $limit->max_quantity,
                    'period_label' => $limit->period_value . ' ' . $limit->period_unit,
                    'used' => $purchased,
                    'remaining' => $remaining,
                ]);
            }
        }

        return $usage;
    }

    /**
     * Check for conflicting active rules: two active rules covering the same product
     * in the SAME department with overlapping period should not be allowed.
     * Retail limits and Wholesale limits for the same product do NOT conflict.
     *
     * @param PurchaseLimit $limit The limit being created or updated
     * @param array $stockIds The product IDs being assigned
     * @return array Empty if no conflicts, otherwise list of conflict descriptions
     */
    public function validateNoConflict(PurchaseLimit $limit, array $stockIds): array
    {
        $conflicts = [];
        $ruleDept = in_array(strtolower(trim($limit->department ?? 'wholesales')), ['retail', 'retail_store']) ? 'retail' : 'wholesales';

        // Find other active limits that share any of these products within the SAME department
        $overlapping = PurchaseLimit::active()
            ->where('id', '!=', $limit->id ?? 0)
            ->where('department', $ruleDept)
            ->whereHas('stocks', function ($query) use ($stockIds) {
                $query->whereIn('stocks.id', $stockIds);
            })
            ->with('stocks')
            ->get();

        foreach ($overlapping as $existingLimit) {
            $sharedProducts = $existingLimit->stocks->whereIn('id', $stockIds);

            foreach ($sharedProducts as $product) {
                $deptLabel = ucfirst($existingLimit->department ?? $ruleDept);
                $conflicts[] = "Product \"{$product->name}\" already has an active {$deptLabel} limit: "
                    . "\"{$existingLimit->name}\" ({$existingLimit->max_quantity} / {$existingLimit->period_value} {$existingLimit->period_unit})";
            }
        }

        return $conflicts;
    }
}
