<?php

namespace App\Http\Controllers\PurchaseReturns;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseReturnController extends Controller
{
    private const ALLOWED_COLUMNS = ['quantity', 'wholesales', 'bulksales', 'retail', 'retail_store'];

    private function safeDepartmentColumn(?string $column): string
    {
        return in_array($column, self::ALLOWED_COLUMNS, true) ? $column : 'quantity';
    }

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

    public function index()
    {
        $data = [
            'title'    => 'Draft Purchase Returns',
            'subtitle' => 'List of drafted purchase returns waiting to be completed',
            'filters'  => ['status_id' => status('Draft')],
        ];

        return setPageContent('purchasereturn.index', $data);
    }

    public function completed()
    {
        $data = [
            'title'    => 'Completed Purchase Returns',
            'subtitle' => 'List of completed purchase returns',
            'filters'  => ['status_id' => status('Complete')],
        ];

        return setPageContent('purchasereturn.index', $data);
    }

    public function create(Request $request)
    {
        $data = $this->formData(
            new PurchaseReturn(),
            'New Purchase Return',
            'Fast keyboard-first stock return to supplier'
        );

        return view('purchasereturn.form', $data);
    }

    public function edit(PurchaseReturn $purchaseReturn, Request $request)
    {
        if ($purchaseReturn->isComplete()) {
            return redirect()->route('purchasereturn.show', $purchaseReturn->id);
        }

        $data = $this->formData(
            $purchaseReturn,
            'Edit Purchase Return #PRT-' . $purchaseReturn->id,
            'Edit and update a drafted purchase return'
        );

        return view('purchasereturn.form', $data);
    }

    public function show(PurchaseReturn $purchaseReturn)
    {
        $data = [
            'title'          => 'Purchase Return Details',
            'subtitle'       => 'Show purchase return information',
            'purchaseReturn' => $purchaseReturn,
        ];

        return view('purchasereturn.show', $data);
    }

    public function print(PurchaseReturn $purchaseReturn)
    {
        abort_unless(userCanView('purchasereturn.show'), 403);

        $purchaseReturn->load(['items.stock', 'supplier', 'user', 'complete_by', 'ledgerEntries']);

        try {
            $store = getStoreSettings();
        } catch (\Throwable $e) {
            $store = null;
        }

        return view('print.purchase_return', [
            'purchaseReturn' => $purchaseReturn,
            'store'          => $store,
        ]);
    }

    /**
     * Fast product search by name words OR barcode, enriched with department stock,
     * latest cost price, and active batch/expiry so returns auto-fill cleanly.
     */
    public function searchProducts(Request $request): JsonResponse
    {
        abort_unless(
            userCanView('purchasereturn.create') || userCanView('purchasereturn.edit'),
            403
        );

        $term = trim((string) $request->get('q', ''));
        $column = $this->safeDepartmentColumn($request->get('column'));

        if (mb_strlen($term) < 2) {
            return response()->json([]);
        }

        $barcodeStockIds = DB::table('stockbarcodes')
            ->where('barcode', $term)
            ->pluck('stock_id')
            ->all();

        $exactMatches = DB::table('stocks')
            ->select(
                'id', 'name', 'barcode', 'box', 'location',
                DB::raw("COALESCE({$column}, 0) as stock_dept_qty"),
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
            return response()->json($this->enrichReturnStocks($exactMatches, $column));
        }

        $words = array_values(array_filter(explode(' ', $term), fn ($w) => $w !== ''));

        $stocks = DB::table('stocks')
            ->select(
                'id', 'name', 'barcode', 'box', 'location',
                DB::raw("COALESCE({$column}, 0) as stock_dept_qty"),
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

        return response()->json($this->enrichReturnStocks($stocks, $column));
    }

    /**
     * Completed Purchase Orders for the selected supplier (for Linked PO searchable combobox).
     */
    public function supplierPos(Request $request): JsonResponse
    {
        abort_unless(
            userCanView('purchasereturn.create') || userCanView('purchasereturn.edit'),
            403
        );

        $supplierId = (int) $request->get('supplier_id', 0);
        if ($supplierId <= 0) {
            return response()->json([]);
        }

        $purchases = Purchase::withCount('purchaseitems')
            ->with(['purchaseitems:id,purchase_id,qty,cost_price'])
            ->where('supplier_id', $supplierId)
            ->where('status_id', status('Complete'))
            ->orderByDesc('id')
            ->limit(100)
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
     * 1-click load items from a linked Purchase Order into the return table.
     */
    public function loadPoItems(Purchase $purchase, Request $request): JsonResponse
    {
        abort_unless(
            userCanView('purchasereturn.create') || userCanView('purchasereturn.edit'),
            403
        );

        $column = $this->safeDepartmentColumn($request->get('column', $purchase->department));
        $poItems = $purchase->purchaseitems()->get();

        if ($poItems->isEmpty()) {
            return response()->json([]);
        }

        $stockIds = $poItems->pluck('stock_id')->filter()->unique()->values()->all();

        $stocks = DB::table('stocks')
            ->select(
                'id', 'name', 'barcode', 'box', 'location',
                DB::raw("COALESCE({$column}, 0) as stock_dept_qty"),
                DB::raw('ROUND((((retail/(CASE WHEN box IS NULL OR box <= 0 THEN 1 ELSE box END)) + (retail_store/(CASE WHEN box IS NULL OR box <= 0 THEN 1 ELSE box END)) + wholesales + quantity + bulksales)), 0) as allqty')
            )
            ->where('status', '1')
            ->whereIn('id', $stockIds)
            ->get();

        $enrichedById = collect($this->enrichReturnStocks($stocks, $column))->keyBy('id');

        $result = [];
        foreach ($poItems as $item) {
            $stock = $enrichedById->get((int) $item->stock_id);
            if (!$stock) {
                continue;
            }
            $expStr = $item->expiry_date
                ? substr((string) $item->expiry_date, 0, 10)
                : $stock['expiry_date'];
            $stock['po_qty'] = (int) $item->qty;
            $stock['cost_price'] = (float) ($item->cost_price ?: $stock['cost_price']);
            $stock['batch_no'] = $item->batch_no ?: $stock['batch_no'];
            $stock['expiry_date'] = $expStr;
            $result[] = $stock;
        }

        return response()->json($result);
    }

    // Permission-only routes (the actions themselves run from the Livewire components)
    public function destroy(PurchaseReturn $purchaseReturn)
    {
    }

    public function complete(PurchaseReturn $purchaseReturn)
    {
    }

    private function enrichReturnStocks($stocks, string $column): array
    {
        if ($stocks->isEmpty()) {
            return [];
        }

        $isRetail = in_array($column, ['retail', 'retail_store'], true);
        $costCol = $isRetail ? 'retail_cost_price' : 'cost_price';
        $stockIds = $stocks->pluck('id')->all();

        // Available quantity in the chosen department from stockbatches
        $deptBatchTotals = DB::table('stockbatches')
            ->select('stock_id', DB::raw("COALESCE(SUM({$column}), 0) as dept_qty"))
            ->whereIn('stock_id', $stockIds)
            ->where($column, '>', 0)
            ->groupBy('stock_id')
            ->get()
            ->keyBy('stock_id');

        // Earliest-expiring batch with stock in this department (matches complete() deduction order!)
        $activeBatches = DB::table('stockbatches')
            ->whereIn('stock_id', $stockIds)
            ->where($column, '>', 0)
            ->orderBy('expiry_date', 'ASC')
            ->orderByDesc('id')
            ->get()
            ->groupBy('stock_id')
            ->map(fn ($rows) => $rows->first());

        // Latest cost price fallback from any batch
        $latestBatchSub = DB::table('stockbatches')
            ->select(DB::raw('MAX(id) as max_id'))
            ->whereIn('stock_id', $stockIds)
            ->whereNotNull($costCol)
            ->groupBy('stock_id');

        $latestPrices = DB::table('stockbatches as sb')
            ->joinSub($latestBatchSub, 'latest', function ($join) {
                $join->on('sb.id', '=', 'latest.max_id');
            })
            ->select('sb.stock_id', "sb.{$costCol} as latest_cost", 'sb.batch_no', 'sb.expiry_date')
            ->get()
            ->keyBy('stock_id');

        return $stocks->map(function ($stock) use ($deptBatchTotals, $activeBatches, $latestPrices, $costCol) {
            $deptRow = $deptBatchTotals->get($stock->id);
            $deptQty = $deptRow ? (int) $deptRow->dept_qty : (int) ($stock->stock_dept_qty ?? 0);

            $active = $activeBatches->get($stock->id);
            $latest = $latestPrices->get($stock->id);

            $costPrice = 0.0;
            if ($active && isset($active->{$costCol}) && $active->{$costCol} !== null) {
                $costPrice = (float) $active->{$costCol};
            } elseif ($latest && $latest->latest_cost !== null) {
                $costPrice = (float) $latest->latest_cost;
            }

            $batchNo = (string) ($active->batch_no ?? $latest->batch_no ?? '');
            $rawExpiry = (string) ($active->expiry_date ?? $latest->expiry_date ?? '');
            $expiryDate = strlen($rawExpiry) >= 10 ? substr($rawExpiry, 0, 10) : '';

            return [
                'id'            => (int) $stock->id,
                'name'          => $stock->name,
                'barcode'       => $stock->barcode ?? '',
                'box'           => $stock->box ?? 1,
                'location'      => $stock->location ?? '',
                'dept_qty'      => $deptQty,
                'allqty'        => (int) ($stock->allqty ?? 0),
                'cost_price'    => $costPrice,
                'batch_no'      => $batchNo,
                'expiry_date'   => $expiryDate,
                'exact_barcode' => (bool) ($stock->exact_barcode ?? false),
            ];
        })->values()->all();
    }

    private function formData(PurchaseReturn $purchaseReturn, string $title, string $subtitle): array
    {
        return [
            'title'          => $title,
            'subtitle'       => $subtitle,
            'purchaseReturn' => $purchaseReturn,
            'suppliers'      => suppliers(true)->values(),
            'departments'    => $this->allowedDepartments()->values(),
        ];
    }
}
