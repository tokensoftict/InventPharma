<?php

namespace App\Http\Controllers\PurchaseReport;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Purchase;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\SupplierCreditPaymentHistory;
use App\Exports\ChequeScheduleExport;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class PurchaseReportsController extends Controller
{

    public function index(Request $request)
    {
        $data = [
            'title' => 'Purchase Report By Date',
            'subtitle' => 'View Report By Date Range',
            'filters' => [
                'from' =>monthlyDateRange()[0],
                'to'=>monthlyDateRange()[1],
                'filters' => [
                    'between.date_created' => monthlyDateRange()
                ]
            ]
        ];
        if($request->get('filter'))
        {
            $data['filters'] = $request->get('filter');
            $data['filters']['filters']['between.date_created'] = Arr::only(array_values( $request->get('filter')), [0,1]);
        }
        return setPageContent('reports.purchases.index', $data);
    }

    public function by_supplier(Request $request)
    {
        $data = [
            'title' => 'Purchase Report By Date and Supplier',
            'subtitle' => 'View Report By Date Range and Supplier',
            'filters' => [
                'from' =>monthlyDateRange()[0],
                'to'=>monthlyDateRange()[1],
                'supplier_id' => 1,
                'filters' => [
                    'between.date_created' => monthlyDateRange(),
                    'supplier_id' => 1
                ]
            ]
        ];
        if(isset($request->from) && isset($request->to) && isset($request->supplier_id) && isset($request->department)) {
            $data['filters'] = [
                'from' => $request->from,
                'to' => $request->to,
                'supplier_id' => $request->supplier_id,
                'filters' => [
                    'between.date_created' =>  [$request->from, $request->to],
                    'supplier_id' =>  $request->supplier_id,
                    'status_id' => status("Complete"),
                ]
            ];

            $department = Department::whereIn('id', explode('-', $request->department))->get();
            $data['filters']['filters']['department'] = $department->pluck('quantity_column')->toArray();

        }
        else if($request->get('filter'))
        {
            $data['filters'] = $request->get('filter');
            $data['filters']['filters']['between.date_created'] = Arr::only(array_values( $request->get('filter')), [0,1]);
            $data['filters']['filters']['supplier_id'] = $data['filters']['supplier_id'];

        }
        return setPageContent('reports.purchases.index', $data);
    }

    public function by_system_user(Request $request)
    {
        $data = [
            'title' => 'Purchase Report By System User',
            'subtitle' => 'View Report By Date Range and System User',
            'filters' => [
                'from' =>monthlyDateRange()[0],
                'to'=>monthlyDateRange()[1],
                'user_id' => 1,
                'filters' => [
                    'between.date_created' => monthlyDateRange(),
                    'user_id' => 1
                ]
            ]
        ];
        if($request->get('filter'))
        {
            $data['filters'] = $request->get('filter');
            $data['filters']['filters']['between.date_created'] = Arr::only(array_values( $request->get('filter')), [0,1]);
            $data['filters']['filters']['user_id'] = $data['filters']['user_id'];

        }
        return setPageContent('reports.purchases.index', $data);
    }

    public function by_stock(Request $request)
    {
        $data = [
            'title' => 'Purchase Report By Date and Stock',
            'subtitle' => 'View Report By Date Range and Stock',
            'filters' => [
                'stock' => Stock::find(1),
                'from' =>monthlyDateRange()[0],
                'to'=>monthlyDateRange()[1],
                'stock_id' => 1,
                'filters' => [
                    'between.purchases.date_created' => monthlyDateRange(),
                    'stock_id' => 1
                ]
            ]
        ];
        if($request->get('filter'))
        {
            $data['filters'] = $request->get('filter');
            $data['filters']['stock'] = Stock::find($data['filters']['stock_id']);
            $data['filters']['filters']['between.purchases.date_created'] = Arr::only(array_values( $request->get('filter')), [0,1]);
            $data['filters']['filters']['stock_id'] = $data['filters']['stock_id'];

        }
        return view('reports.purchases.purchaseorderbymaterial', $data);
    }

    public function by_status(Request $request)
    {
        $data = [
            'title' => 'Purchase Report By Date',
            'subtitle' => 'View Report By Date Range and System Status',
            'filters' => [
                'from' =>monthlyDateRange()[0],
                'to'=>monthlyDateRange()[1],
                'status_id' => 1,
                'filters' => [
                    'between.date_created' => monthlyDateRange(),
                    'status_id' => 1
                ]
            ]
        ];
        if($request->get('filter'))
        {
            $data['filters'] = $request->get('filter');
            $data['filters']['filters']['between.date_created'] = Arr::only(array_values( $request->get('filter')), [0,1]);
            $data['filters']['filters']['status_id'] = $data['filters']['status_id'];

        }
        return setPageContent('reports.purchases.index', $data);
    }

    public function by_department(Request $request)
    {
        $data = [
            'title' => 'Purchase Report By Date',
            'subtitle' => 'View Report By Date Range and Department',
            'filters' => [
                'from' =>monthlyDateRange()[0],
                'to'=>monthlyDateRange()[1],
                'department' => 'quantity',
                'filters' => [
                    'between.date_created' => monthlyDateRange(),
                    'department' => 'quantity'
                ]
            ]
        ];
        if($request->get('filter'))
        {
            $data['filters'] = $request->get('filter');
            $data['filters']['filters']['between.date_created'] = Arr::only(array_values( $request->get('filter')), [0,1]);
            $data['filters']['filters']['department'] = $data['filters']['department'];

        }
        return setPageContent('reports.purchases.index', $data);
    }



    public function supplier_payment(Request $request)
    {
        $data = [
            'title' => 'Supplier Payment Report By Date',
            'subtitle' => 'View Supplier Payment By Date Range',
            'filters' => [
                'from' =>monthlyDateRange()[0],
                'to'=>monthlyDateRange()[1],
                'filters' => [
                    'between.payment_date' => monthlyDateRange(),
                    'type' => 'PAYMENT'
                ]
            ]
        ];
        if($request->get('filter'))
        {
            $data['filters'] = $request->get('filter');
            $data['filters']['filters']['between.payment_date'] = Arr::only(array_values( $request->get('filter')), [0,1]);
            $data['filters']['filters']['type'] = 'PAYMENT';
        }
        return view('reports.purchases.supplier.payment.index', $data);
    }


    public function supplier_credit(Request $request)
    {
        $data = [
            'title' => 'Supplier Payment Report By Date',
            'subtitle' => 'View Supplier Payment By Date Range',
            'filters' => [
                'from' =>monthlyDateRange()[0],
                'to'=>monthlyDateRange()[1],
                'filters' => [
                    'between.payment_date' => monthlyDateRange(),
                    'type' => 'CREDIT'
                ]
            ]
        ];
        if($request->get('filter'))
        {
            $data['filters'] = $request->get('filter');
            $data['filters']['filters']['between.payment_date'] = Arr::only(array_values( $request->get('filter')), [0,1]);
            $data['filters']['filters']['type'] = 'CREDIT';
        }
        return view('reports.purchases.supplier.credit.index', $data);
    }



    public function balance_sheet(Request $request)
    {
        $data = [
            'title' => 'Supplier Balance Sheet Report',
            'subtitle' => 'View Supplier Balance Sheet Report By Date Range and supplier',
            'filters' => [
                'from' =>monthlyDateRange()[0],
                'to'=>monthlyDateRange()[1],
                'supplier_id' => 1,
                'filters' => [
                    'between.return_date' => monthlyDateRange(),
                    'supplier_id' => 1
                ]
            ]
        ];
        if($request->get('filter'))
        {
            $data['filters'] = $request->get('filter');
            $data['filters']['filters']['between.return_date'] = Arr::only(array_values( $request->get('filter')), [0,1]);
            $data['filters']['filters']['supplier_id'] = $data['filters']['supplier_id'];
        }

        $data['opening'] = SupplierCreditPaymentHistory::query()
            ->where('supplier_id', $data['filters']['filters']['supplier_id'])
            ->where('payment_date', '<', $data['filters']['filters']['between.return_date'][0])
            ->get()
            ->filter(function($item){
                if($item->isReversed()) {
                    return false;
                }

                if($item->paymentmethod_id === 8 && isset($item->payment_info['status']) && $item->payment_info['status'] === 'Pending') {
                    return false;
                }

                if($item->paymentmethod_id === 8 && isset($item->payment_info['status']) && $item->payment_info['status'] === 'Declined') {
                    return false;
                }

                if($item->paymentmethod_id === 8 && isset($item->payment_info['going_out_date']) && (new Carbon($item->payment_info['going_out_date']) > now()->format("Y-m-d"))) {
                    return false;
                }

                return true;
            })
            ->sum('amount');

        $data['histories'] = SupplierCreditPaymentHistory::query()
            ->where('supplier_id', $data['filters']['filters']['supplier_id'])
            ->whereBetween('payment_date', $data['filters']['filters']['between.return_date'])
            ->get()->filter(function($item){
                if($item->isReversed()) {
                    return false;
                }

                if($item->paymentmethod_id === 8 && isset($item->payment_info['status']) && $item->payment_info['status'] === 'Pending') {
                    return false;
                }

                if($item->paymentmethod_id === 8 && isset($item->payment_info['status']) && $item->payment_info['status'] === 'Declined') {
                    return false;
                }

                if($item->paymentmethod_id === 8 && isset($item->payment_info['going_out_date']) && (new Carbon($item->payment_info['going_out_date']) > now()->format("Y-m-d"))) {
                    return false;
                }

                return true;
            });

        return view('reports.purchases.supplier.balance_sheet', $data);

    }

    /**
     * Filter predicate shared with balance_sheet: excludes reversed entries and
     * Pending / Declined / post-dated cheques.
     */
    private function isEffectiveSupplierLedgerEntry(SupplierCreditPaymentHistory $item): bool
    {
        if ($item->isReversed()) {
            return false;
        }

        if ($item->paymentmethod_id === 8 && isset($item->payment_info['status']) && $item->payment_info['status'] === 'Pending') {
            return false;
        }

        if ($item->paymentmethod_id === 8 && isset($item->payment_info['status']) && $item->payment_info['status'] === 'Declined') {
            return false;
        }

        if ($item->paymentmethod_id === 8 && isset($item->payment_info['going_out_date']) && (new Carbon($item->payment_info['going_out_date']) > now()->format('Y-m-d'))) {
            return false;
        }

        return true;
    }

    /**
     * Supplier Account Statement — combines Purchase Orders (Supplies), Purchase Returns,
     * and Supplier Payments into a unified chronological statement inherited from Balance Sheet.
     */
    public function supplier_account_statement(Request $request)
    {
        abort_unless(
            userCanView('reports.purchase.balance_sheet') || userCanView('reports.purchase.supplier_account_statement'),
            403
        );

        $defaultSupplierId = suppliers(true)->first()->id ?? 1;
        $range = monthlyDateRange();

        $from = $range[0];
        $to = $range[1];
        $supplierId = $defaultSupplierId;

        if ($request->get('filter')) {
            $filterInput = $request->get('filter');
            $from = $filterInput['from'] ?? ($filterInput[0] ?? $from);
            $to = $filterInput['to'] ?? ($filterInput[1] ?? $to);
            $supplierId = $filterInput['supplier_id'] ?? $supplierId;
        }

        $data = [
            'title' => 'Supplier Account Statement',
            'subtitle' => 'Combined statement of Purchase Orders, Supplier Payments, and Purchase Returns',
            'filters' => [
                'from' => $from,
                'to' => $to,
                'supplier_id' => $supplierId,
                'filters' => [
                    'between.payment_date' => [$from, $to],
                    'supplier_id' => $supplierId,
                ],
            ],
        ];

        $opening = (float) SupplierCreditPaymentHistory::query()
            ->where('supplier_id', $supplierId)
            ->where('payment_date', '<', $from)
            ->get()
            ->filter(fn (SupplierCreditPaymentHistory $item) => $this->isEffectiveSupplierLedgerEntry($item))
            ->sum('amount');

        $histories = SupplierCreditPaymentHistory::query()
            ->with(['paymentmethod', 'purchase', 'purchaseReturn', 'invoice', 'supplier', 'user'])
            ->where('supplier_id', $supplierId)
            ->whereBetween('payment_date', [$from, $to])
            ->orderBy('payment_date', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->filter(fn (SupplierCreditPaymentHistory $item) => $this->isEffectiveSupplierLedgerEntry($item))
            ->values();

        $runningBalance = $opening;
        $totalSupplied = 0.0;
        $totalPayments = 0.0;
        $totalReturns = 0.0;

        $statementRows = $histories->map(function (SupplierCreditPaymentHistory $history) use (
            &$runningBalance,
            &$totalSupplied,
            &$totalPayments,
            &$totalReturns
        ) {
            $amount = (float) $history->amount;
            $runningBalance = round($runningBalance + $amount, 5);
            $info = is_array($history->payment_info) ? $history->payment_info : [];

            $isReturn = strtoupper((string) $history->type) === 'RETURN' || !is_null($history->purchase_return_id);
            $isSupply = !$isReturn && ($amount < 0 || strtoupper((string) $history->type) === 'CREDIT');
            $isPayment = !$isReturn && !$isSupply;

            $suppliedTotal = null;
            $paymentAmount = null;
            $returnWorth = null;
            $paymentMethod = '—';
            $descriptionParts = [];
            $linkUrl = null;
            $accountBank = '—';
            $cashReceiver = '—';

            if ($isSupply) {
                $suppliedTotal = abs($amount);
                $totalSupplied += $suppliedTotal;
                $paymentMethod = 'Supply / Credit';
                if ($history->purchase_id) {
                    $descriptionParts[] = 'Purchase Order #' . $history->purchase_id;
                    if (userCanView('purchase.show')) {
                        $linkUrl = route('purchase.show', $history->purchase_id);
                    }
                } else {
                    $descriptionParts[] = 'Stock Supply';
                }
            } elseif ($isReturn) {
                $returnWorth = abs($amount);
                $totalReturns += $returnWorth;
                $paymentMethod = 'Purchase Return';
                if ($history->purchase_return_id) {
                    $descriptionParts[] = 'Purchase Return #PRT-' . $history->purchase_return_id;
                    if (userCanView('purchasereturn.show')) {
                        $linkUrl = route('purchasereturn.show', $history->purchase_return_id);
                    }
                } elseif (!empty($history->reference)) {
                    $descriptionParts[] = 'Purchase Return ' . $history->reference;
                } else {
                    $descriptionParts[] = 'Purchase Return';
                }
            } else {
                $paymentAmount = $amount;
                $totalPayments += $paymentAmount;
                $paymentMethod = $history->isStockForStock()
                    ? 'Stock for Stock'
                    : ($history->paymentmethod->name ?? 'Payment');

                if ($history->isStockForStock()) {
                    $invNo = $history->invoice->invoice_number ?? ($info['invoice_number'] ?? null);
                    $descriptionParts[] = $invNo ? 'Stock Exchange Invoice #' . $invNo : 'Stock for Stock Payment';
                } elseif ($history->purchase_id) {
                    $descriptionParts[] = 'Payment for PO #' . $history->purchase_id;
                } elseif ($history->paymentmethod_id === 8 && !empty($info['cheque_date'])) {
                    $descriptionParts[] = 'Cheque (Due: ' . $info['cheque_date'] . ')';
                } else {
                    $descriptionParts[] = 'Supplier Payment';
                }

                if (!empty($history->reference)) {
                    $descriptionParts[] = 'Ref: ' . $history->reference;
                }

                // Transfer Account Name (Bank Name) + Account Number
                $accName = trim((string) ($info['account_name'] ?? ''));
                $bankName = trim((string) ($info['bank_name'] ?? ''));
                $accNumber = trim((string) ($info['account_number'] ?? ''));

                if ($accName !== '' || $bankName !== '' || $accNumber !== '') {
                    $label = $accName !== '' ? $accName : '';
                    if ($bankName !== '') {
                        $label = $label !== '' ? "{$label} ({$bankName})" : $bankName;
                    }
                    if ($accNumber !== '') {
                        $label = $label !== '' ? "{$label} — {$accNumber}" : $accNumber;
                    }
                    $accountBank = $label;
                }

                // Cash Receiver
                $receiver = trim((string) ($info['cash_received_by'] ?? ''));
                if ($receiver !== '') {
                    $cashReceiver = $receiver;
                }
            }

            $remark = trim((string) ($history->remark ?? ''));
            if ($remark !== '') {
                // Avoid duplicating e.g. "Purchase Return #12" if already in description
                $joinedSoFar = implode(' | ', $descriptionParts);
                if (!str_contains(strtolower($joinedSoFar), strtolower($remark))) {
                    $descriptionParts[] = $remark;
                }
            }

            return [
                'id' => $history->id,
                'kind' => $isReturn ? 'return' : ($isSupply ? 'supply' : 'payment'),
                'supplied_total' => $suppliedTotal,
                'payment_amount' => $paymentAmount,
                'payment_method' => $paymentMethod,
                'payment_date' => $history->payment_date,
                'description' => implode(' — ', array_filter($descriptionParts)),
                'link_url' => $linkUrl,
                'account_bank' => $accountBank,
                'cash_receiver' => $cashReceiver,
                'return_worth' => $returnWorth,
                'balance' => $runningBalance,
            ];
        });

        $data['supplier'] = \App\Models\Supplier::find($supplierId);
        $data['opening'] = $opening;
        $data['rows'] = $statementRows;
        $data['totalSupplied'] = round($totalSupplied, 5);
        $data['totalPayments'] = round($totalPayments, 5);
        $data['totalReturns'] = round($totalReturns, 5);
        $data['closingBalance'] = round($runningBalance, 5);

        return view('reports.purchases.supplier.account_statement', $data);
    }

    public function supplier_ranking(Request $request)
    {
        $items = [
            [
                'id' =>4,
                'name' => "Retail",
            ],
            [
                'id' =>6,
                'name' => "SuperMarket Store",
            ],
            [
                'id' =>1,
                'name' => "Main Store",
            ],
            [
                'id' =>"1-4-6",
                'name' => "Retail, SuperMarket Store And Main Store"
            ]
        ];
        $data = [
            'title' => 'Supplier Ranking  Report',
            'subtitle' => 'View Supplier  Ranking Report By Date Range and department',
            'filters' => [
                'custom_dropdown_id' => 2,
                'label_name' => 'Departments',
                'items' => $items,
                'from' =>monthlyDateRange()[0],
                'to'=>monthlyDateRange()[1],
                'filters' => [
                    'between.invoice_date' => monthlyDateRange(),
                    'custom_dropdown_id' => 2,
                ]
            ]
        ];

        if($request->get('filter'))
        {
            $data['filters'] = $request->get('filter');
            $data['filters']['items'] = $items;
            $data['filters']['label_name'] = 'Departments';
            $data['filters']['custom_dropdown_id'] = $data['filters']['custom_dropdown_id'];
            $data['filters']['filters']['between.invoice_date'] = Arr::only(array_values( $request->get('filter')), [0,1]);
            $data['filters']['filters']['custom_dropdown_id'] = $data['filters']['custom_dropdown_id'];
            $data['filters']['filters']['items'] = $items;
            $data['filters']['filters']['label_name'] = 'Departments';
        }

        $department = explode("-",  $data['filters']['filters']['custom_dropdown_id']);

        $reports =  Purchase::with(['supplier'])->select(
            'purchases.supplier_id',
            DB::raw('COUNT(DISTINCT purchases.id) as purchase_count'),
            DB::raw('SUM(purchaseitems.cost_price * purchaseitems.qty) as purchase_total_amount')
        )->join('purchaseitems', "purchaseitems.purchase_id","=","purchases.id")
            ->whereIn('purchases.department', Department::whereIn("id", $department)->pluck('quantity_column')->toArray())
            ->whereBetween('purchases.date_completed', $data['filters']['filters']['between.invoice_date'])
            ->where("purchases.status_id", status("Complete"))
            ->orderBy('purchase_total_amount','DESC')
            ->groupBy('purchases.supplier_id')->get();

        $data['reports'] = $reports;
        return view('reports.purchases.supplier_ranking_report', $data);
    }


    public function supplier_sales_analysis(Request $request)
    {
        $items = [
            [
                'id' =>"Retail",
                'name' => "Retail",
            ],
            [
                'id' =>"1-4",
                'name' => "Wholesales, Bulk Sales, Main Store",
            ]
        ];
        $data = [
            'title' => 'Supplier Sales Analysis',
            'subtitle' => 'View Supplier Sales Analysis By Date Range',
            'filters' => [
                'custom_dropdown_id' => "1-4",
                'label_name' => 'Departments',
                'items' => $items,
                'from' =>monthlyDateRange()[0],
                'to'=>monthlyDateRange()[1],
                'items' => $items ,
                'label_name' => 'Departments',
                'filters' => [
                    'between.invoice_date' => monthlyDateRange(),
                    'custom_dropdown_id' => "1-4",
                ]
            ]
        ];
        if($request->get('filter'))
        {
            $data['filters'] = $request->get('filter');
            $data['filters']['items'] = $items;
            $data['filters']['label_name'] = 'Departments';
            $data['filters']['custom_dropdown_id'] = $data['filters']['custom_dropdown_id'];
            $data['filters']['filters']['between.invoice_date'] = Arr::only(array_values( $request->get('filter')), [0,1]);
            $data['filters']['filters']['custom_dropdown_id'] = $data['filters']['custom_dropdown_id'];
            $data['filters']['filters']['items'] = $items;
            $data['filters']['filters']['label_name'] = 'Departments';
        }
        return view('reports.purchases.supplier.sales_analysis', $data);
    }

    public function cheque_schedule(Request $request)
    {
        $period = $request->input('filter.period', 'this_month');
        $from = todaysDate();
        $to = todaysDate();

        if ($period == 'today') {
            $from = todaysDate();
            $to = todaysDate();
        } elseif ($period == 'this_week') {
            $range = weeklyDateRange();
            $from = $range[0];
            $to = $range[1];
        } elseif ($period == 'this_month') {
            $range = monthlyDateRange();
            $from = $range[0];
            $to = $range[1];
        } elseif ($period == 'custom') {
            $from = $request->input('filter.from', monthlyDateRange()[0]);
            $to = $request->input('filter.to', monthlyDateRange()[1]);
        }

        $data = [
            'title' => 'Supplier Payment Cheque Schedule',
            'subtitle' => 'View Cheque Payment Schedule By Cheque Date Range',
            'filters' => [
                'from' => $from,
                'to' => $to,
                'period' => $period,
            ]
        ];

        $cheques = SupplierCreditPaymentHistory::where('paymentmethod_id', 8)
            ->whereBetween('payment_info->cheque_date', [$from, $to])
            ->with(['supplier', 'user'])
            ->get();

        $processedCheques = [];
        $departmentTotals = [];

        foreach (\App\Classes\Settings::$department as $key => $name) {
            if ($name !== '') {
                $departmentTotals[$name] = 0;
            }
        }
        $departmentTotals['Unknown'] = 0;

        foreach ($cheques as $cheque) {
            $lastPurchase = Purchase::where('supplier_id', $cheque->supplier_id)
                ->where('status_id', status('Complete'))
                ->orderBy('date_completed', 'desc')
                ->first();

            $lastPurchaseDate = $lastPurchase ? mysql_str_date($lastPurchase->date_completed) : 'N/A';
            $lastDeptKey = $lastPurchase ? $lastPurchase->department : null;
            $lastDeptName = ($lastPurchase && isset(\App\Classes\Settings::$department[$lastDeptKey])) 
                ? \App\Classes\Settings::$department[$lastDeptKey] 
                : 'Unknown';

            $departmentTotals[$lastDeptName] += $cheque->amount;

            $processedCheques[] = [
                'cheque' => $cheque,
                'last_purchase_date' => $lastPurchaseDate,
                'last_dept_name' => $lastDeptName
            ];
        }

        $data['cheques'] = $processedCheques;
        $data['department_totals'] = array_filter($departmentTotals, fn($amount) => $amount > 0);

        if ($request->input('action') === 'export') {
            $file_name = 'Cheque_Schedule_' . $from . '_to_' . $to . '.xlsx';
            return Excel::download(new ChequeScheduleExport($processedCheques, $data['department_totals']), $file_name);
        }

        return view('reports.purchases.supplier.cheque_schedule', $data);
    }

}
