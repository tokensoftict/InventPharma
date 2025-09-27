<?php

namespace App\Http\Controllers\PurchaseReport;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Purchase;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\SupplierCreditPaymentHistory;
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

        $data['opening'] = SupplierCreditPaymentHistory::where('supplier_id',  $data['filters']['filters']['supplier_id'])->where('payment_date','<',  $data['filters']['filters']['between.return_date'][0])->sum('amount');

        $data['histories'] = SupplierCreditPaymentHistory::where('supplier_id', $data['filters']['filters']['supplier_id'])->whereBetween('payment_date',  $data['filters']['filters']['between.return_date'])->get();


        return view('reports.purchases.supplier.balance_sheet', $data);

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

}
