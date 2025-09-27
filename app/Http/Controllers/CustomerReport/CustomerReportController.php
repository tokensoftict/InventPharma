<?php

namespace App\Http\Controllers\CustomerReport;

use App\Http\Controllers\Controller;
use App\Models\Creditpaymentlog;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Department;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CustomerReportController extends Controller
{

    public function balance_sheet(Request $request)
    {
        $data = [
            'title' => 'Customer Balance Sheet Report',
            'subtitle' => 'View Customer Balance Sheet Report By Date Range and customer',
            'filters' => [
                'customer' => Customer::find(2),
                'from' =>monthlyDateRange()[0],
                'to'=>monthlyDateRange()[1],
                'customer_id' => 2,
                'filters' => [
                    'between.return_date' => monthlyDateRange(),
                    'customer_id' => 2
                ]
            ]
        ];
        if($request->get('filter'))
        {
            $data['filters'] = $request->get('filter');
            $data['filters']['customer'] = Customer::find($data['filters']['customer_id']);
            $data['filters']['filters']['between.return_date'] = Arr::only(array_values( $request->get('filter')), [0,1]);
            $data['filters']['filters']['customer_id'] = $data['filters']['customer_id'];
        }

        $data['opening'] = CreditPaymentLog::where('customer_id',  $data['filters']['filters']['customer_id'])->where('payment_date','<',  $data['filters']['filters']['between.return_date'][0])->sum('amount');

        $data['histories'] = CreditPaymentLog::where('customer_id', $data['filters']['filters']['customer_id'])->whereBetween('payment_date',  $data['filters']['filters']['between.return_date'])->get();


        return view('reports.customer.balance_sheet', $data);

    }

    public function customer_ledger(Request $request)
    {
        $data = [
            'title' => 'Customer Ledger  Report',
            'subtitle' => 'View Customer  Ledger Report By Date Range and customer',
            'filters' => [
                'customer' => Customer::find(2),
                'from' =>monthlyDateRange()[0],
                'to'=>monthlyDateRange()[1],
                'customer_id' => 2,
                'filters' => [
                    'between.return_date' => monthlyDateRange(),
                    'customer_id' => 2
                ]
            ]
        ];
        if($request->get('filter'))
        {
            $data['filters'] = $request->get('filter');
            $data['filters']['customer'] = Customer::find($data['filters']['customer_id']);
            $data['filters']['filters']['between.return_date'] = Arr::only(array_values( $request->get('filter')), [0,1]);
            $data['filters']['filters']['customer_id'] = $data['filters']['customer_id'];
        }


        $data['opening'] = CustomerLedger::where('customer_id',  $data['filters']['filters']['customer_id'])->where('transaction_date','<',  $data['filters']['filters']['between.return_date'][0])->sum('amount');

        $data['histories'] = CustomerLedger::where('customer_id', $data['filters']['filters']['customer_id'])->whereBetween('transaction_date',  $data['filters']['filters']['between.return_date'])->get();



        return view('reports.customer.customer_ledger', $data);
    }



    public function customer_ranking(Request $request)
    {
        $items = [
            [
                'id' =>2,
                'name' => "Only Wholesales",
            ],
            [
                'id' =>3,
                'name' => "Only Bulksales",
            ],
            [
                'id' =>4,
                'name' =>"Only Retail",
            ],
            [
                'id' =>"2-3",
                'name' => "Wholesales and Bulksales"
            ],
            [
                'id' =>"2-3-4",
                'name' => "All Departments"
            ]
        ];
        $data = [
            'title' => 'Customer Ranking  Report',
            'subtitle' => 'View Customer  Ranking Report By Date Range and department',
            'filters' => [
                'custom_dropdown_id' => 2,
                'label_name' => 'Departments',
                'items' => $items,
                'from' =>monthlyDateRange()[0],
                'to'=>monthlyDateRange()[1],
                'items' => $items ,
                'label_name' => 'Departments',
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

        $reports =  Invoice::with(['customer'])->select(
            'customer_id',
            DB::raw('count(customer_id) as invoice_count'),
            DB::raw('SUM(total_amount_paid) as total_invoice_amount')
        )
            ->whereIn('in_department', Department::whereIn("id", $department)->pluck('quantity_column')->toArray())
            ->whereBetween('invoice_date', $data['filters']['filters']['between.invoice_date'])
            ->where(function($qq){
                $qq->orWhere("invoices.status_id",status("Paid"))
                    ->orWhere('invoices.status_id',status("Complete"));
            })
            ->orderBy('total_invoice_amount','DESC')
            ->groupBy('invoices.customer_id')->get();

        $data['reports'] = $reports;
        return view('reports.customer.customer_ranking_report', $data);
    }


    public function customer_sales_report(Request $request)
    {
        $data = [
            'title' => 'Customer Sales Report',
            'subtitle' => 'View Customer Sales Report By Date Range',
            'filters' => [
                'start_from' =>date("Y-m-d", strtotime("first day of this month last year")),
                'start_to'=>date("Y-m-d", strtotime("last day of this month last year")),
                'end_from' =>date("Y-m-d", strtotime("first day of this month this year")),
                'end_to'=>date("Y-m-d", strtotime("last day of this month this year")),
            ]
        ];

        if($request->has("start_from")){
            $data['filters'] = [
                'start_from' =>$request->start_from,
                'start_to'=>$request->start_to,
                'end_from' =>$request->end_from,
                'end_to'=>$request->end_to
            ];
        }

        $startInvoiceQuery = DB::table('invoices')
            ->select('customer_id', DB::raw('SUM(sub_total) as start_invoice_amount'))
            ->whereIn('status_id', [status('Paid'), status('Complete')])
            ->whereBetween('invoice_date', [$data['filters']['start_from'], $data['filters']['start_to']])
            ->groupBy('customer_id');

        $endInvoiceQuery = DB::table('invoices')
            ->select('customer_id', DB::raw('SUM(sub_total) as end_invoice_amount'))
            ->whereIn('status_id', [status('Paid'), status('Complete')])
            ->whereBetween('invoice_date', [$data['filters']['end_from'], $data['filters']['end_to']])
            ->groupBy('customer_id');

        $data['reports'] = Customer::query()
            ->select('customers.id as customer_id',
                DB::raw("CONCAT_WS(' ', customers.firstname, customers.lastname) as customer_name"),
                'customers.phone_number as phone_number',
                'startInvoices.start_invoice_amount',
                'endInvoices.end_invoice_amount',
                DB::raw('(startInvoices.start_invoice_amount - endInvoices.end_invoice_amount) as difference'))
            ->leftJoinSub($startInvoiceQuery, 'startInvoices', 'startInvoices.customer_id', '=', 'customers.id')
            ->leftJoinSub($endInvoiceQuery, 'endInvoices', 'endInvoices.customer_id', '=', 'customers.id')
            ->where("customers.id", ">", '1')
            ->orderBy( DB::raw('(startInvoices.start_invoice_amount - endInvoices.end_invoice_amount)'))
            ->get();

        return view('reports.customer.customer_sales_report', $data);
    }

}
