<?php

namespace App\Services;

use App\Models\Stock;
use App\Services\PurchaseLimitService;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class InvoiceValidationService
{
    protected PurchaseLimitService $purchaseLimitService;

    public function __construct(?PurchaseLimitService $purchaseLimitService = null)
    {
        $this->purchaseLimitService = $purchaseLimitService ?? new PurchaseLimitService();
    }

    /**
     * Resolve tier/custom pricing by quantity brackets.
     *
     * @param int|float $quantity
     * @param float $defaultSellingPrice
     * @param array $customPrices
     * @param Stock $stock
     * @param string $department
     * @return float
     */
    public function resolvePriceByQuantity(int|float $quantity, float $defaultSellingPrice, array $customPrices, Stock $stock, string $department): float
    {
        foreach ($customPrices as $priceRule) {
            $min = (int) $priceRule['min_qty'];
            $max = (int) $priceRule['max_qty'];

            if (in_array($department, ['retail', 'retail_store'])) {
                if ($quantity >= $min && $quantity < $max) {
                    return (float) $priceRule['price'];
                }
            } else {
                $carton = ($stock->carton && (int)$stock->carton > 0) ? (int)$stock->carton : 1;
                $cartonQty = $quantity / $carton;
                if ($cartonQty >= $min && $cartonQty < $max) {
                    return (float) $priceRule['price'];
                }
            }
        }

        return (float) $defaultSellingPrice;
    }

    /**
     * Compute additional amount from product option selections.
     *
     * @param array $selectedOptions
     * @return float
     */
    public function getOptionsTotalAmount(array $selectedOptions): float
    {
        $total = 0.0;

        foreach ($selectedOptions as $option) {
            $amount = isset($option['amount']) ? (float) $option['amount'] : 0.0;

            if (($option['sign'] ?? '+') === '-') {
                $amount *= -1;
            }

            $total += $amount;
        }

        return $total;
    }

    /**
     * Validate items against stock quantity, active saleable batches, minimum quantity,
     * and resolve selling price and cost price.
     *
     * @param array $items Array of items, each with at least 'stock_id' and 'quantity'
     * @param string $from Department quantity column (e.g. 'retail', 'wholesales', 'bulksales', 'quantity')
     * @return array ['status' => bool, 'errors' => array, 'results' => array]
     */
    public function validateItems(array $items, string $from): array
    {
        $from = strtolower(trim($from));
        $itemsCollection = collect($items);

        $stocks = [];
        $errors = [];

        $itemsCollection->each(function ($item) use (&$stocks) {
            $item = (array) $item;
            if (isset($item['stock_id'])) {
                $stocks[$item['stock_id']]['item'] = $item;
            }
        });

        if (empty($stocks)) {
            return [
                'status' => false,
                'errors' => ['items' => 'No valid invoice items provided for validation.'],
                'results' => [],
            ];
        }

        $requestedStockIds = array_keys($stocks);
        $products = Stock::with(['activeBatches', 'stockquantityprices'])
            ->whereIn('id', $requestedStockIds)
            ->get()
            ->keyBy('id');

        // Check if any requested stock ID does not exist in inventory
        foreach ($requestedStockIds as $stockId) {
            if (!$products->has($stockId)) {
                $errors[$stockId] = "Product with ID {$stockId} was not found in inventory.";
            }
        }

        $dept = department_by_quantity_column($from);
        $deptId = $dept ? $dept->id : ($from === 'retail' ? 4 : 2);

        $products->each(function (Stock $product) use (&$stocks, &$errors, $from, $deptId) {
            $requestedQty = (int) ($stocks[$product->id]['item']['quantity'] ?? 0);

            // Populate fallback fields from Stock model if not present in input
            $stocks[$product->id]['item']['name'] = $stocks[$product->id]['item']['name'] ?? $product->name;
            $stocks[$product->id]['item']['box'] = $stocks[$product->id]['item']['box'] ?? $product->box;
            $stocks[$product->id]['item']['carton'] = $stocks[$product->id]['item']['carton'] ?? $product->carton;
            $stocks[$product->id]['item']['av_qty'] = $stocks[$product->id]['item']['av_qty'] ?? ($product->{$from} ?? 0);

            // Ping saleable batches in the specified department
            $batch = $product->pingSaleableBatches($from, $requestedQty, $product->activeBatches);

            // Check if minimum inventory quantity threshold would be violated
            $status = $product->pingIfQuantityHasNotExceededTheMinimumQuantity($from, $requestedQty);
            if ($status === true && $batch !== false) {
                $errors[$product->id] = $product->name . " has exceeded the minimum quantity of " . $product->minimum_quantity . " set by the administrator";
            }

            if ($batch === false) {
                $available = $product->{$from} ?? 0;
                if($available > 0) {
                    $errors[$product->id] = "Only $available units of " . $product->name . ($available == 1 ? ' is' : ' are') . " available";
                } else {
                    $errors[$product->id] = "$product->name is currently out of stock";
                }
            } else {
                $total_cost_batch = collect($batch)->sum('cost_price');

                if (in_array($from, ['retail', 'retail_store'])) {
                    $defaultSellingPrice = (float) $product->{selling_price_column(4)};
                    if ($product->stockquantityprices->count() > 0) {
                        $stocks[$product->id]['item']['selling_price'] = $this->resolvePriceByQuantity(
                            quantity: $requestedQty,
                            defaultSellingPrice: $defaultSellingPrice,
                            customPrices: $product->stockquantityprices()->where('department', $from)->get()->toArray(),
                            stock: $product,
                            department: $from,
                        );
                    } else {
                        $stocks[$product->id]['item']['selling_price'] = $defaultSellingPrice;
                    }
                } else {
                    $defaultSellingPrice = (float) $product->{selling_price_column($deptId)};
                    if ($product->stockquantityprices->count() > 0) {
                        $stocks[$product->id]['item']['selling_price'] = $this->resolvePriceByQuantity(
                            quantity: $requestedQty,
                            defaultSellingPrice: $defaultSellingPrice,
                            customPrices: $product->stockquantityprices()->whereIn('department', [$from, "wholesale"])->get()->toArray(),
                            stock: $product,
                            department: $from,
                        );
                    } else {
                        $stocks[$product->id]['item']['selling_price'] = $defaultSellingPrice;
                    }
                }

                $optionsAmount = $this->getOptionsTotalAmount($stocks[$product->id]['item']['selectedOptions'] ?? []);
                $stocks[$product->id]['item']['selling_price'] += $optionsAmount;

                $batchCount = count($batch);
                $stocks[$product->id]['item']['cost_price'] = $batchCount > 0 ? abs($total_cost_batch / $batchCount) : 0;
                $stocks[$product->id]['batches'] = $batch;
            }
        });

        if (count($errors) > 0) {
            return [
                'status' => false,
                'errors' => $errors,
                'results' => $stocks,
            ];
        }

        return [
            'status' => true,
            'errors' => [],
            'results' => $stocks,
        ];
    }

    /**
     * Validate purchase limits for wholesale customers.
     *
     * @param int|string|array|null $customerId
     * @param array $items
     * @param string $department
     * @return array ['status' => bool, 'errors' => array]
     */
    public function validatePurchaseLimits(int|string|array|null $customerId, array $items, string $department): array
    {
        $department = strtolower(trim($department));

        // Purchase limit validation applies to wholesale and retail departments
        if (!in_array($department, ['wholesales', 'bulksales', 'retail', 'retail_store'])) {
            return ['status' => true, 'errors' => []];
        }

        if (is_array($customerId)) {
            $customerId = $customerId['id'] ?? null;
        }

        // Skip walk-in customer (ID 1) or empty / non-numeric customer ID
        if (!$customerId || !is_numeric($customerId) || (int)$customerId === 1) {
            return ['status' => true, 'errors' => []];
        }

        return $this->purchaseLimitService->validateInvoiceItems((int)$customerId, $items, $department);
    }

    /**
     * Complete validation pipeline: runs stock/batch/pricing validation AND purchase limit validation.
     *
     * @param array $items
     * @param string $department
     * @param int|string|array|null $customerId
     * @param bool $checkPurchaseLimits
     * @return array
     */
    public function validateInvoice(array $items, string $department, int|string|array|null $customerId = null, bool $checkPurchaseLimits = true): array
    {
        $department = strtolower(trim($department));

        // 1. Validate stock availability, batches, minimum quantity & pricing
        $itemValidation = $this->validateItems($items, $department);
        $allErrors = $itemValidation['errors'] ?? [];

        // 2. Validate customer purchase limits
        $limitValidation = ['status' => true, 'errors' => []];
        $limitChecked = false;

        if ($checkPurchaseLimits) {
            $parsedCustomerId = is_array($customerId) ? ($customerId['id'] ?? null) : $customerId;
            if (in_array($department, ['wholesales', 'bulksales', 'retail', 'retail_store']) && $parsedCustomerId && is_numeric($parsedCustomerId) && (int)$parsedCustomerId !== 1) {
                $limitChecked = true;
                $limitValidation = $this->validatePurchaseLimits($parsedCustomerId, $items, $department);
                if (($limitValidation['status'] ?? false) === false && !empty($limitValidation['errors'])) {
                    foreach ($limitValidation['errors'] as $errStockId => $errMsg) {
                        $allErrors[$errStockId] = $errMsg;
                    }
                }
            }
        }

        $isValid = count($allErrors) === 0;
        $results = $itemValidation['results'] ?? [];

        // Build clean summary data for API responses and consumers
        $formattedItems = [];
        $subTotal = 0.0;

        foreach ($results as $stockId => $data) {
            $item = $data['item'] ?? [];
            $qty = (int) ($item['quantity'] ?? 0);
            $sellingPrice = (float) ($item['selling_price'] ?? 0);
            $costPrice = (float) ($item['cost_price'] ?? 0);
            $itemSubTotal = round($qty * $sellingPrice, 2);
            $subTotal += $itemSubTotal;

            $formattedItems[] = [
                'stock_id' => (int) $stockId,
                'name' => $item['name'] ?? null,
                'quantity' => $qty,
                'selling_price' => $sellingPrice,
                'cost_price' => $costPrice,
                'sub_total' => $itemSubTotal,
                'available_qty' => $item['av_qty'] ?? null,
                'batches' => $data['batches'] ?? [],
                'carton' => $item['carton'] ?? null,
                'box' => $item['box'] ?? null,
            ];
        }

        return [
            'status' => $isValid,
            'valid' => $isValid,
            'errors' => $allErrors,
            'results' => $results,
            'data' => [
                'department' => $department,
                'customer_id' => is_array($customerId) ? ($customerId['id'] ?? null) : $customerId,
                'purchase_limit_checked' => $limitChecked,
                'sub_total' => round($subTotal, 2),
                'items' => $formattedItems,
            ],
        ];
    }





    /**
     * Validate items against stock quantity, active saleable batches, minimum quantity,
     * and resolve selling price and cost price.
     *
     * @param array $items Array of items, each with at least 'stock_id' and 'quantity'
     * @param string $from Department quantity column (e.g. 'retail', 'wholesales', 'bulksales', 'quantity')
     * @return array ['status' => bool, 'errors' => array, 'results' => array]
     */
    public function validateOnlineItems(array $items, string $from): array
    {
        $from = strtolower(trim($from));
        $itemsCollection = collect($items);

        $stocks = [];
        $errors = [];

        $itemsCollection->each(function ($item) use (&$stocks) {
            $item = (array) $item;
            if (isset($item['stock_id'])) {
                $stocks[$item['stock_id']]['item'] = $item;
            }
        });

        if (empty($stocks)) {
            return [
                'status' => false,
                'errors' => ['items' => 'No valid invoice items provided for validation.'],
                'results' => [],
            ];
        }

        $requestedStockIds = array_keys($stocks);
        $products = Stock::with(['activeBatches', 'stockquantityprices'])
            ->whereIn('id', $requestedStockIds)
            ->get()
            ->keyBy('id');

        // Check if any requested stock ID does not exist in inventory
        foreach ($requestedStockIds as $stockId) {
            if (!$products->has($stockId)) {
                $errors[$stockId] = "Product with ID {$stockId} was not found in inventory.";
            }
        }

        $dept = department_by_quantity_column($from);
        $deptId = $dept ? $dept->id : ($from === 'retail' ? 4 : 2);

        $products->each(function (Stock $product) use (&$stocks, &$errors, $from, $deptId) {
            $requestedQty = (int) ($stocks[$product->id]['item']['quantity'] ?? 0);

            // Populate fallback fields from Stock model if not present in input
            $stocks[$product->id]['item']['name'] = $stocks[$product->id]['item']['name'] ?? $product->name;
            $stocks[$product->id]['item']['box'] = $stocks[$product->id]['item']['box'] ?? $product->box;
            $stocks[$product->id]['item']['carton'] = $stocks[$product->id]['item']['carton'] ?? $product->carton;
            $stocks[$product->id]['item']['av_qty'] = $stocks[$product->id]['item']['av_qty'] ?? ($product->{$from} ?? 0);

            // Ping saleable batches in the specified department
            $batch = $product->pingStockLocation($requestedQty, $product->activeBatches, ($from == "wholesales" ? ProcessOrderService::$onlineWholeSalesDepartment : ProcessOrderService::$onlineRetailSalesDepartment));

            // Check if minimum inventory quantity,  threshold would be violated
            $status = $product->pingIfQuantityHasNotExceededTheMinimumQuantity($from, $requestedQty);
            if ($status === true && $batch !== false) {
                $errors[$product->id] = $product->name . " has exceeded the minimum quantity of " . $product->minimum_quantity . " set by the administrator";
            }

            if ($batch === false) {
                if($from == "wholesales") {
                    $available = $product->getOnlineQuantity() ?? 0;
                } else{
                    $available = $product->getCurrentlevel($from) ?? 0;
                }

                if($available > 0) {
                    $errors[$product->id] = "Only $available units of " . $product->name . ($available == 1 ? ' is' : ' are') . " available";
                } else {
                    $errors[$product->id] = "$product->name is currently out of stock";
                }
            } else {
                $total_cost_batch = collect($batch)->sum('cost_price');

                if (in_array($from, ['retail', 'retail_store'])) {
                    $defaultSellingPrice = (float) $product->{selling_price_column(4)};
                    if ($product->stockquantityprices->count() > 0) {
                        $stocks[$product->id]['item']['selling_price'] = $this->resolvePriceByQuantity(
                            quantity: $requestedQty,
                            defaultSellingPrice: $defaultSellingPrice,
                            customPrices: $product->stockquantityprices()->where('department', $from)->get()->toArray(),
                            stock: $product,
                            department: $from,
                        );
                    } else {
                        $stocks[$product->id]['item']['selling_price'] = $defaultSellingPrice;
                    }
                } else {
                    $defaultSellingPrice = (float) $product->{selling_price_column($deptId)};
                    if ($product->stockquantityprices->count() > 0) {
                        $stocks[$product->id]['item']['selling_price'] = $this->resolvePriceByQuantity(
                            quantity: $requestedQty,
                            defaultSellingPrice: $defaultSellingPrice,
                            customPrices: $product->stockquantityprices()->whereIn('department', [$from, "wholesale"])->get()->toArray(),
                            stock: $product,
                            department: $from,
                        );
                    } else {
                        $stocks[$product->id]['item']['selling_price'] = $defaultSellingPrice;
                    }
                }

                $optionsAmount = $this->getOptionsTotalAmount($stocks[$product->id]['item']['selectedOptions'] ?? []);
                $stocks[$product->id]['item']['selling_price'] += $optionsAmount;

                $batchCount = count($batch);
                $stocks[$product->id]['item']['cost_price'] = $batchCount > 0 ? abs($total_cost_batch / $batchCount) : 0;
                $stocks[$product->id]['batches'] = $batch;
            }
        });

        if (count($errors) > 0) {
            return [
                'status' => false,
                'errors' => $errors,
                'results' => $stocks,
            ];
        }

        return [
            'status' => true,
            'errors' => [],
            'results' => $stocks,
        ];
    }



    /**
     * Complete validation pipeline: runs stock/batch/pricing validation AND purchase limit validation.
     *
     * @param array $items
     * @param string $department
     * @param int|string|array|null $customerId
     * @param bool $checkPurchaseLimits
     * @return array
     */
    public function validateOnlineInvoice(array $items, string $department, int|string|array|null $customerId = null, bool $checkPurchaseLimits = true): array
    {
        $department = strtolower(trim($department));

        // 1. Validate stock availability, batches, minimum quantity & pricing
        $itemValidation = $this->validateItems($items, $department);
        $allErrors = $itemValidation['errors'] ?? [];

        // 2. Validate customer purchase limits
        $limitValidation = ['status' => true, 'errors' => []];
        $limitChecked = false;

        if ($checkPurchaseLimits) {
            $parsedCustomerId = is_array($customerId) ? ($customerId['id'] ?? null) : $customerId;
            if (in_array($department, ['wholesales', 'bulksales', 'retail', 'retail_store']) && $parsedCustomerId && is_numeric($parsedCustomerId) && (int)$parsedCustomerId !== 1) {
                $limitChecked = true;
                $limitValidation = $this->validatePurchaseLimits($parsedCustomerId, $items, $department);
                if (($limitValidation['status'] ?? false) === false && !empty($limitValidation['errors'])) {
                    foreach ($limitValidation['errors'] as $errStockId => $errMsg) {
                        $allErrors[$errStockId] = $errMsg;
                    }
                }
            }
        }

        $isValid = count($allErrors) === 0;
        $results = $itemValidation['results'] ?? [];

        // Build clean summary data for API responses and consumers
        $formattedItems = [];
        $subTotal = 0.0;

        foreach ($results as $stockId => $data) {
            $item = $data['item'] ?? [];
            $qty = (int) ($item['quantity'] ?? 0);
            $sellingPrice = (float) ($item['selling_price'] ?? 0);
            $costPrice = (float) ($item['cost_price'] ?? 0);
            $itemSubTotal = round($qty * $sellingPrice, 2);
            $subTotal += $itemSubTotal;

            $formattedItems[] = [
                'stock_id' => (int) $stockId,
                'name' => $item['name'] ?? null,
                'quantity' => $qty,
                'selling_price' => $sellingPrice,
                'cost_price' => $costPrice,
                'sub_total' => $itemSubTotal,
                'available_qty' => $item['av_qty'] ?? null,
                'batches' => $data['batches'] ?? [],
                'carton' => $item['carton'] ?? null,
                'box' => $item['box'] ?? null,
            ];
        }

        return [
            'status' => $isValid,
            'valid' => $isValid,
            'errors' => $allErrors,
            'results' => $results,
            'data' => [
                'department' => $department,
                'customer_id' => is_array($customerId) ? ($customerId['id'] ?? null) : $customerId,
                'purchase_limit_checked' => $limitChecked,
                'sub_total' => round($subTotal, 2),
                'items' => $formattedItems,
            ],
        ];
    }

}
