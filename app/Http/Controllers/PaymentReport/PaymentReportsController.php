<?php

namespace App\Http\Controllers\PaymentReport;

use App\Http\Controllers\Controller;
use App\Models\Invoiceitem;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use DataTables;
use Illuminate\Support\Facades\DB;

class PaymentReportsController extends Controller
{

    public function index(Request $request)
    {
        $data = [
            'title' => 'Payment Report By Date',
            'subtitle' => 'View Payment Report By Date Range',
            'filters' => [
                'from' =>monthlyDateRange()[0],
                'to'=>monthlyDateRange()[1],
                'filters' => [
                    'between.payment_date' => monthlyDateRange()
                ]
            ]
        ];
        if($request->get('filter'))
        {
            $data['filters'] = $request->get('filter');
            $data['filters']['filters']['between.payment_date'] = Arr::only(array_values( $request->get('filter')), [0,1]);
        }
        return setPageContent('reports.payment.index', $data);
    }


    public function by_customer(Request $request)
    {
        $data = [
            'title' => 'Payment Report By Customer',
            'subtitle' => 'View Payment Report By Customer',
            'filters' => [
                'from' =>monthlyDateRange()[0],
                'to'=>monthlyDateRange()[1],
                'customer_id' => 1,
                'filters' => [
                    'between.payment_date' => monthlyDateRange(),
                    'customer_id' => 1
                ]
            ]
        ];
        if($request->get('filter'))
        {
            $data['filters'] = $request->get('filter');
            $data['filters']['filters']['between.payment_date'] = Arr::only(array_values( $request->get('filter')), [0,1]);
            $data['filters']['filters']['customer_id'] = $data['filters']['customer_id'];
        }
        return setPageContent('reports.payment.index', $data);
    }


    public function  by_system_user(Request $request)
    {
        $data = [
            'title' => 'Payment Report By User',
            'subtitle' => 'View Payment Report By System User',
            'filters' => [
                'from' =>monthlyDateRange()[0],
                'to'=>monthlyDateRange()[1],
                'user_id' => 1,
                'filters' => [
                    'between.payment_date' => monthlyDateRange(),
                    'user_id' => 1
                ]
            ]
        ];
        if($request->get('filter'))
        {
            $data['filters'] = $request->get('filter');
            $data['filters']['filters']['between.payment_date'] = Arr::only(array_values( $request->get('filter')), [0,1]);
            $data['filters']['filters']['user_id'] = $data['filters']['user_id'];
        }
        return setPageContent('reports.payment.index', $data);
    }


    public function by_payment_method(Request $request)
    {
        $data = [
            'title' => 'Payment Report By Method',
            'subtitle' => 'View Payment Report By Payment Method',
            'filters' => [
                'from' =>monthlyDateRange()[0],
                'to'=>monthlyDateRange()[1],
                'paymentmethod_id'=> 1,
                'filters' => [
                    'between.payment_date' => monthlyDateRange(),
                    'paymentmethod_id' => 1
                ]
            ]
        ];
        if($request->get('filter'))
        {
            $data['filters'] = $request->get('filter');
            $data['filters']['filters']['between.payment_date'] = Arr::only(array_values( $request->get('filter')), [0,1]);
            $data['filters']['filters']['paymentmethod_id'] = $data['filters']['paymentmethod_id'];
        }
        return setPageContent('reports.payment.paymentmethoditemlist', $data);
    }



    public function profit_and_loss(Request $request)
    {
        $data = [
            'title' => 'Profit And Loss Analysis',
            'subtitle' => 'View Profit or Loss for each product sold withing a selected date',
            'filters' => [
                'from' =>monthlyDateRange()[0],
                'to'=>monthlyDateRange()[1],
                'filters' => [
                    'between.invoice_date' => monthlyDateRange()
                ]
            ]
        ];
        if($request->get('filter'))
        {
            $data['filters'] = $request->get('filter');
            $data['filters']['filters']['between.invoice_date'] = Arr::only(array_values( $request->get('filter')), [0,1]);
        }
        return setPageContent('reports.payment.profitandloss', $data);
    }

    public function profit_and_loss_by_department(Request $request)
    {
        $data = [
            'title' => 'Profit And Loss Analysis',
            'subtitle' => 'View Profit or Loss for each product sold withing a selected date',
            'filters' => [
                'from' =>monthlyDateRange()[0],
                'to'=>monthlyDateRange()[1],
                'department'=> 'quantity',
                'filters' => [
                    'between.invoice_date' => monthlyDateRange(),
                    'department'=> 'quantity',
                ]
            ]
        ];
        if($request->get('filter'))
        {
            $data['filters'] = $request->get('filter');
            $data['filters']['filters']['between.invoice_date'] = Arr::only(array_values( $request->get('filter')), [0,1]);
            $data['filters']['filters']['department'] = $data['filters']['department'];
        }
        return view('reports.payment.profitandlossbydepartment', $data);
    }



    public function payment_method(Request $request)
    {
        $data = [
            'title' => 'Payment Report By All Payment Method',
            'subtitle' => 'View Payment Report by all Payment Method',
            'filters' => [
                'from' =>dailyDate(),
                'filters' => [
                    'payment_date' => dailyDate()
                ]
            ]
        ];
        if($request->get('filter'))
        {
            $data['filters'] = $request->get('filter');
            $data['filters']['filters']['payment_date'] = $request->get('filter')['from'];
        }
        return setPageContent('reports.payment.payment_methods', $data);
    }

    public function payment_method_by_user(Request $request)
    {
        $data = [
            'title' => 'Payment Report By All Payment Method By Users',
            'subtitle' => 'View Payment Report by all Payment Method By Users',
            'filters' => [
                'from' =>todaysDate(),
                'user_id' => 1,
                'filters' => [
                    'payment_date' =>todaysDate(),
                    'user_id' => 1
                ]
            ]
        ];
        if($request->get('filter'))
        {
            $data['filters'] = $request->get('filter');
            $data['filters']['filters']['payment_date'] = $request->get('filter')['from'];
            $data['filters']['filters']['user_id'] = $data['filters']['user_id'];
        }
        return view('reports.payment.payment_methods', $data);
    }


    public function credit_report(Request $request)
    {
        $data = [
            'title' => 'Payment Report By Date',
            'subtitle' => 'View Payment Report By Date Range',
            'filters' => [
                'from' =>todaysDate(),
                'to'=>todaysDate(),
                'filters' => [
                    'between.payment_date' =>[todaysDate(), todaysDate()]
                ]
            ]
        ];
        if($request->get('filter'))
        {
            $data['filters'] = $request->get('filter');
            $data['filters']['filters']['between.payment_date'] = Arr::only(array_values( $request->get('filter')), [0,1]);
        }
        return view('reports.payment.creditreport', $data);
    }


    public function credit_payment_report(Request $request)
    {
        $data = [
            'title' => 'Payment Report By Date',
            'subtitle' => 'View Payment Report By Date Range',
            'filters' => [
                'from' =>todaysDate(),
                'to'=>todaysDate(),
                'filters' => [
                    'between.payment_date' =>[todaysDate(), todaysDate()]
                ]
            ]
        ];
        if($request->get('filter'))
        {
            $data['filters'] = $request->get('filter');
            $data['filters']['filters']['between.payment_date'] = Arr::only(array_values( $request->get('filter')), [0,1]);
        }
        return view('reports.payment.creditpaymentreport', $data);
    }

}
