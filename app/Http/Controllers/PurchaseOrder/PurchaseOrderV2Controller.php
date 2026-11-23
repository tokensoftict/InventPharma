<?php

namespace App\Http\Controllers\PurchaseOrder;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderV2Controller extends Controller
{
    private function allowedDepartments()
    {
        if (config('app.PURCHASE_DEPARTMENT') !== false) {
            return department_by_ids(explode(',', config('app.PURCHASE_DEPARTMENT')));
        }

        return departments(true)->filter(function ($item) {
            if (in_array(auth()->user()->department_id, [1, 2, 3, 5])) {
                return $item->id === 1 || $item->id == 4 || $item->id == 6;
            }

            return auth()->user()->department_id === 4;
        });
    }

    private function resolveColumnMeta(?string $column): array
    {
        $isRetail = in_array($column, ['retail', 'retail_store'], true);

        return [
            'cost_price' => $isRetail ? 'retail_cost_price' : 'cost_price',
            'highest_qty_sold' => $isRetail ? 'highest_qty_sold_retail' : 'highest_qty_sold',
            'near_os_table' => $isRetail ? 'retailnearoutofstock' : 'nearoutofstocks',
        ];
    }

    private function enrichStockRows($stocks, string $column): array
    {
        if ($stocks->isEmpty()) {
            return [];
        }

        $meta = $this->resolveColumnMeta($column);
        $costCol = $meta['cost_price'];
        $nearOsTable = $meta['near_os_table'];

        $stockIds = $stocks->pluck('id')->all();
        $groupIds = $stocks->pluck('stockgroup_id')->filter()->unique()->values()->all();

        // Batch-fetch latest cost price per stock (single query, no N+1)
        $latestBatchSub = DB::table('stockbatches')
            ->select(DB::raw('MAX(id) as max_id'))
            ->whereIn('stock_id', $stockIds)
            ->whereNotNull($costCol)
            ->groupBy('stock_id');

        $latestPrices = DB::table('stockbatches as sb')
            ->joinSub($latestBatchSub, 'latest', function ($join) {
                $join->on('sb.id', '=', 'latest.max_id');
            })
            ->select('sb.stock_id', "sb.{$costCol} as latest_cost")
            ->get()
            ->keyBy('stock_id');

        // Batch-fetch 1M target quantities by stock_id
        $qty1mByStock = DB::table($nearOsTable)
            ->select('stock_id', 'qty_to_buy_1m', 'qty_to_buy')
            ->whereIn('stock_id', $stockIds)
            ->get()
            ->keyBy('stock_id');

        // Batch-fetch 1M target quantities by stockgroup_id (no N+1!)
        $qty1mByGroup = !empty($groupIds)
            ? DB::table($nearOsTable)
                ->select('stockgroup_id', 'qty_to_buy_1m', 'qty_to_buy')
                ->whereIn('stockgroup_id', $groupIds)
                ->get()
                ->keyBy('stockgroup_id')
            : collect();

        return $stocks->map(function ($stock) use ($latestPrices, $qty1mByStock, $qty1mByGroup) {
            $priceRow = $latestPrices->get($stock->id);
            $costPrice = ($priceRow && $priceRow->latest_cost !== null) ? (float) $priceRow->latest_cost : 0.0;

            $nearRow = $qty1mByStock->get($stock->id);
            $qty1m = $nearRow?->qty_to_buy_1m;

            if (!is_null($stock->stockgroup_id)) {
                $groupRow = $qty1mByGroup->get($stock->stockgroup_id);
                if ($groupRow && $groupRow->qty_to_buy_1m !== null) {
                    $qty1m = $groupRow->qty_to_buy_1m;
                }
            }

            $allqty = (float) ($stock->allqty ?? 0);
            $qty1mFloat = $qty1m !== null ? (float) $qty1m : 0.0;
            $suggested = max(0, (int) ceil($qty1mFloat - $allqty));

            return [
                'id' => (int) $stock->id,
                'name' => $stock->name,
                'barcode' => $stock->barcode ?? '',
                'box' => $stock->box ?? 1,
                'location' => $stock->location ?? '',
                'allqty' => $allqty,
                'cost_price' => $costPrice,
                'bulk_price' => (float) ($stock->bulk_price ?? 0),
                'whole_price' => (float) ($stock->whole_price ?? 0),
                'retail_price' => (float) ($stock->retail_price ?? 0),
                'highest_qty_sold' => (float) ($stock->highest_qty_sold ?? 0),
                'qty_to_buy_1m' => $qty1mFloat,
                'qty_1m_result' => $qty1mFloat - $allqty,
                'suggested_qty' => $suggested > 0 ? $suggested : 1,
                'exact_barcode' => (bool) ($stock->exact_barcode ?? false),
            ];
        })->values()->all();
    }

    public function create(Request $request)
    {
        abort_unless(userCanView('purchase.create'), 403);

        $departments = $this->allowedDepartments()->values();

        return view('purchase.v2.form', [
            'title' => 'Create Purchase Order (Beta)',
            'subtitle' => 'Fast keyboard-first purchase order creation',
            'purchase' => new Purchase(),
            'suppliers' => suppliers(true)->values(),
            'departments' => $departments,
            'status_id' => status('Draft'),
        ]);
    }

    public function pre_draft(Request $request)
    {
        abort_unless(userCanView('purchase.pre_draft') || userCanView('purchase.create'), 403);

        $departments = $this->allowedDepartments()->values();

        return view('purchase.v2.form', [
            'title' => 'Create Pre-Draft Purchase (Beta)',
            'subtitle' => 'Fast keyboard-first pre-draft purchase order',
            'purchase' => new Purchase(),
            'suppliers' => suppliers(true)->values(),
            'departments' => $departments,
            'status_id' => status('Pre-Draft'),
        ]);
    }

    public function edit(Purchase $purchase, Request $request)
    {
        abort_unless(userCanView('purchase.edit'), 403);

        if ($purchase->status_id === status('Complete')) {
            return redirect()->route('purchase.show', $purchase->id);
        }

        $departments = $this->allowedDepartments()->values();

        return view('purchase.v2.form', [
            'title' => 'Edit Purchase Order #' . $purchase->id . ' (Beta)',
            'subtitle' => 'Fast keyboard-first purchase order editor',
            'purchase' => $purchase,
            'suppliers' => suppliers(true)->values(),
            'departments' => $departments,
            'status_id' => $purchase->status_id,
        ]);
    }

    public function print(Purchase $purchase)
    {
        abort_unless(userCanView('purchase.show'), 403);

        $purchase->load(['purchaseitems.stock', 'supplier', 'user', 'complete_by', 'status']);

        try {
            $store = getStoreSettings();
        } catch (\Throwable $e) {
            $store = null;
        }

        return view('print.purchase_order', [
            'purchase' => $purchase,
            'store'    => $store,
        ]);
    }

    /**
     * Fast product search by name words OR barcode (stocks.barcode + stockbarcodes.barcode).
     */
    public function searchProducts(Request $request): JsonResponse
    {
        abort_unless(
            userCanView('purchase.create') || userCanView('purchase.edit') || userCanView('purchase.pre_draft'),
            403
        );

        $term = trim((string) $request->get('q', ''));
        $column = (string) $request->get('column', 'quantity');

        if (mb_strlen($term) < 2) {
            return response()->json([]);
        }

        $meta = $this->resolveColumnMeta($column);
        $highestCol = $meta['highest_qty_sold'];

        // 1. Check exact barcode match first (primary barcode or secondary stockbarcodes)
        $barcodeStockIds = DB::table('stockbarcodes')
            ->where('barcode', $term)
            ->pluck('stock_id')
            ->all();

        $exactMatches = DB::table('stocks')
            ->select(
                'id', 'stockgroup_id', 'whole_price', 'bulk_price', 'retail_price',
                'name', 'barcode', 'box', 'location',
                DB::raw("{$highestCol} as highest_qty_sold"),
                DB::raw('ROUND((((retail/(CASE WHEN box IS NULL OR box <= 0 THEN 1 ELSE box END)) + (retail_store/(CASE WHEN box IS NULL OR box <= 0 THEN 1 ELSE box END)) + wholesales + quantity + bulksales)), 0) as allqty')
            )
            ->where('status', '1')
            ->where(function ($q) use ($term, $barcodeStockIds) {
                $q->where('barcode', $term);
                if (!empty($barcodeStockIds)) {
                    $q->orWhereIn('id', $barcodeStockIds);
                }
            })
            ->limit(5)
            ->get()
            ->map(function ($row) {
                $row->exact_barcode = true;
                return $row;
            });

        if ($exactMatches->isNotEmpty()) {
            return response()->json($this->enrichStockRows($exactMatches, $column));
        }

        // 2. Multi-word name search (or barcode prefix)
        $words = array_values(array_filter(explode(' ', $term), fn ($w) => $w !== ''));

        $stocks = DB::table('stocks')
            ->select(
                'id', 'stockgroup_id', 'whole_price', 'bulk_price', 'retail_price',
                'name', 'barcode', 'box', 'location',
                DB::raw("{$highestCol} as highest_qty_sold"),
                DB::raw('ROUND((((retail/(CASE WHEN box IS NULL OR box <= 0 THEN 1 ELSE box END)) + (retail_store/(CASE WHEN box IS NULL OR box <= 0 THEN 1 ELSE box END)) + wholesales + quantity + bulksales)), 0) as allqty')
            )
            ->where('status', '1')
            ->where(function ($q) use ($words, $term) {
                $q->where(function ($nameQ) use ($words) {
                    foreach ($words as $word) {
                        $nameQ->where('stocks.name', 'LIKE', '%' . $word . '%');
                    }
                })->orWhere('stocks.barcode', 'LIKE', $term . '%');
            })
            ->orderByRaw('CASE WHEN stocks.name LIKE ? THEN 0 ELSE 1 END', [$term . '%'])
            ->orderBy('stocks.name')
            ->limit(25)
            ->get();

        return response()->json($this->enrichStockRows($stocks, $column));
    }

    /**
     * 1-click loader: near-out-of-stock products for the chosen supplier & department.
     */
    public function supplierLowStock(Request $request): JsonResponse
    {
        abort_unless(
            userCanView('purchase.create') || userCanView('purchase.edit') || userCanView('purchase.pre_draft'),
            403
        );

        $supplierId = (int) $request->get('supplier_id', 0);
        $column = (string) $request->get('column', 'quantity');

        if ($supplierId <= 0) {
            return response()->json([]);
        }

        $meta = $this->resolveColumnMeta($column);
        $nearOsTable = $meta['near_os_table'];
        $highestCol = $meta['highest_qty_sold'];

        $nearRows = DB::table($nearOsTable)
            ->where('supplier_id', $supplierId)
            ->whereNotNull('stock_id')
            ->orderByDesc('qty_to_buy_1m')
            ->limit(60)
            ->get()
            ->keyBy('stock_id');

        if ($nearRows->isEmpty()) {
            return response()->json([]);
        }

        $stocks = DB::table('stocks')
            ->select(
                'id', 'stockgroup_id', 'whole_price', 'bulk_price', 'retail_price',
                'name', 'barcode', 'box', 'location',
                DB::raw("{$highestCol} as highest_qty_sold"),
                DB::raw('ROUND((((retail/(CASE WHEN box IS NULL OR box <= 0 THEN 1 ELSE box END)) + (retail_store/(CASE WHEN box IS NULL OR box <= 0 THEN 1 ELSE box END)) + wholesales + quantity + bulksales)), 0) as allqty')
            )
            ->where('status', '1')
            ->whereIn('id', $nearRows->keys()->all())
            ->orderBy('name')
            ->get();

        $enriched = $this->enrichStockRows($stocks, $column);

        foreach ($enriched as &$row) {
            $nr = $nearRows->get($row['id']);
            if ($nr && $nr->qty_to_buy > 0 && $row['suggested_qty'] <= 1) {
                $row['suggested_qty'] = (int) $nr->qty_to_buy;
            }
        }

        return response()->json($enriched);
    }

    /**
     * List recent Purchase Orders for the selected supplier so user can clone in 1 click.
     */
    public function supplierRecentPos(Request $request): JsonResponse
    {
        abort_unless(
            userCanView('purchase.create') || userCanView('purchase.edit') || userCanView('purchase.pre_draft'),
            403
        );

        $supplierId = (int) $request->get('supplier_id', 0);
        if ($supplierId <= 0) {
            return response()->json([]);
        }

        $purchases = Purchase::withCount('purchaseitems')
            ->with(['purchaseitems:id,purchase_id,qty,cost_price'])
            ->where('supplier_id', $supplierId)
            ->orderByDesc('id')
            ->limit(12)
            ->get()
            ->map(function (Purchase $po) {
                $total = $po->purchaseitems->sum(fn ($i) => (float) $i->qty * (float) $i->cost_price);

                return [
                    'id' => $po->id,
                    'date_created' => $po->date_created ? mysql_str_date($po->date_created) : '',
                    'department' => $po->department,
                    'items_count' => $po->purchaseitems_count,
                    'total' => round($total, 2),
                ];
            })
            ->values();

        return response()->json($purchases);
    }

    /**
     * Clone all items from a previous PO, refreshed with current stock levels & latest cost prices.
     */
    public function clonePo(Purchase $purchase, Request $request): JsonResponse
    {
        abort_unless(
            userCanView('purchase.create') || userCanView('purchase.edit') || userCanView('purchase.pre_draft'),
            403
        );

        $column = (string) $request->get('column', $purchase->department ?: 'quantity');
        $meta = $this->resolveColumnMeta($column);
        $highestCol = $meta['highest_qty_sold'];

        $poItems = $purchase->purchaseitems()->get();
        if ($poItems->isEmpty()) {
            return response()->json([]);
        }

        $stockIds = $poItems->pluck('stock_id')->filter()->unique()->values()->all();

        $stocks = DB::table('stocks')
            ->select(
                'id', 'stockgroup_id', 'whole_price', 'bulk_price', 'retail_price',
                'name', 'barcode', 'box', 'location',
                DB::raw("{$highestCol} as highest_qty_sold"),
                DB::raw('ROUND((((retail/(CASE WHEN box IS NULL OR box <= 0 THEN 1 ELSE box END)) + (retail_store/(CASE WHEN box IS NULL OR box <= 0 THEN 1 ELSE box END)) + wholesales + quantity + bulksales)), 0) as allqty')
            )
            ->where('status', '1')
            ->whereIn('id', $stockIds)
            ->get();

        $enrichedById = collect($this->enrichStockRows($stocks, $column))->keyBy('id');

        $result = [];
        foreach ($poItems as $item) {
            $stock = $enrichedById->get((int) $item->stock_id);
            if (!$stock) {
                continue;
            }
            $stock['previous_qty'] = (int) $item->qty;
            $stock['cost_price'] = $stock['cost_price'] > 0 ? $stock['cost_price'] : (float) ($item->cost_price ?? 0);
            $result[] = $stock;
        }

        return response()->json($result);
    }
}
