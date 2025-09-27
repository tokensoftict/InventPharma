<?php

namespace App\Http\Controllers\InvoiceReport;

use App\Http\Controllers\Controller;
use App\Models\Production;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class InvoiceReportController extends Controller
{
    public function index(Request $request)
    {
        $data = [
            'title' => 'Invoice Report By Date',
            'subtitle' => 'View Invoice Report By Date Range',
            'filters' => [
                'from' =>todaysDate(),
                'to'=>todaysDate(),
                'filters' => [
                    'between.invoice_date' => [todaysDate(),todaysDate()],
                ]
            ]
        ];
        if($request->get('filter'))
        {
            $data['filters'] = $request->get('filter');
            $data['filters']['filters']['between.invoice_date'] = Arr::only(array_values( $request->get('filter')), [0,1]);
        }
        return setPageContent('reports.invoice.index', $data);
    }

    public function by_customer(Request $request)
    {
        $data = [
            'title' => 'Invoice Report By Customer',
            'subtitle' => 'View Invoice Report By Customer',
            'filters' => [
                'from' =>todaysDate(),
                'to'=>todaysDate(),
                'array.customer_id' => [],
                'filters' => [
                    'between.invoice_date' =>[todaysDate(),todaysDate()],
                    'array.customer_id' => []
                ]
            ]
        ];
        if($request->get('filter'))
        {
            $data['filters'] = $request->get('filter');
            $data['filters']['filters']['between.invoice_date'] = Arr::only(array_values( $request->get('filter')), [0,1]);
            $data['filters']['array.customer_id'] = $data['filters']['customer_id'];
            $data['filters']['filters']['array.customer_id'] = $data['filters']['customer_id'];
            unset( $data['filters']['customer_id']);

        }
        return setPageContent('reports.invoice.index', $data);
    }


    public function by_system_user(Request $request)
    {
        $data = [
            'title' => 'Invoice Report By User',
            'subtitle' => 'View Invoice Report By System User',
            'filters' => [
                'from' =>todaysDate(),
                'to'=>todaysDate(),
                'created_by' => 1,
                'filters' => [
                    'between.invoice_date' => [todaysDate(),todaysDate()],
                    'created_by' => 1
                ]
            ]
        ];
        if($request->get('filter'))
        {
            $data['filters'] = $request->get('filter');
            $data['filters']['filters']['between.invoice_date'] = Arr::only(array_values( $request->get('filter')), [0,1]);
            $data['filters']['filters']['created_by'] = $data['filters']['created_by'];
        }
        return setPageContent('reports.invoice.index', $data);
    }


    public function by_product(Request $request)
    {
        $data = [
            'title' => 'Invoice Report By Product',
            'subtitle' => 'View Report By Date Range and Product',
            'filters' => [
                'stock' => Stock::find(1),
                'from' =>todaysDate(),
                'to'=>todaysDate(),
                'stock_id' => 1,
                'department' => 'quantity',
                'filters' => [
                    'between.invoices.invoice_date' =>[todaysDate(),todaysDate()],
                    'stock_id' => 1,
                   'department' => 'quantity',
                ]
            ]
        ];
        if($request->get('filter'))
        {

            $data['filters'] = $request->get('filter');
            $data['filters']['stock'] = Stock::find($data['filters']['stock_id']);
            $data['filters']['filters']['between.invoices.invoice_date'] = Arr::only(array_values( $request->get('filter')), [0,1]);
            $data['filters']['filters']['stock_id'] = $data['filters']['stock_id'];
            $data['filters']['filters']['department'] = $data['filters']['department'];

        }
        return view('reports.invoice.invoiceitem', $data);
    }


    public function by_status(Request $request)
    {
        $data = [
            'title' => 'Invoice Report By Status',
            'subtitle' => 'View Report By Date Range and System Status',
            'filters' => [
                'from' =>todaysDate(),
                'to'=>todaysDate(),
                'status_id' => 1,
                'filters' => [
                    'between.invoice_date' => [todaysDate(),todaysDate()],
                    'status_id' => 1
                ]
            ]
        ];
        if($request->get('filter'))
        {
            $data['filters'] = $request->get('filter');
            $data['filters']['filters']['between.invoice_date'] = Arr::only(array_values( $request->get('filter')), [0,1]);
            $data['filters']['filters']['status_id'] = $data['filters']['status_id'];

        }
        return view('reports.invoice.index', $data);
    }




    public function print_frequency(Request $request)
    {
        $data = [
            'title' => 'Retail Invoice POS Print Frequency',
            'subtitle' => 'This report generate the total number thermal receipt for retails sales was print',
            'filters' => [
                'from' =>todaysDate(),
                'to'=>todaysDate(),
                'filters' => [
                    'between.invoice_date' => [todaysDate(),todaysDate()],
                ]
            ]
        ];
        if($request->get('filter'))
        {
            $data['filters'] = $request->get('filter');
            $data['filters']['filters']['between.invoice_date'] = Arr::only(array_values( $request->get('filter')), [0,1]);
        }

        return view('reports.invoice.retailprintfrequency', $data);
    }


    public function return_invoice(Request $request)
    {
        $data = [
            'title' => 'Return Invoice Report By Date',
            'subtitle' => 'View Return Invoice Report By Date Range',
            'filters' => [
                'from' =>todaysDate(),
                'to'=>todaysDate(),
                'filters' => [
                    'between.invoice_date' => [todaysDate(),todaysDate()],
                    'is_not_null.void_reason' => "",
                ]
            ]
        ];
        if($request->get('filter'))
        {
            $data['filters'] = $request->get('filter');
            $data['filters']['filters']['between.invoice_date'] = Arr::only(array_values( $request->get('filter')), [0,1]);
            $data['filters']['filters']['is_not_null.void_reason'] = "";
        }
        return view('reports.invoice.return', $data);
    }

}
