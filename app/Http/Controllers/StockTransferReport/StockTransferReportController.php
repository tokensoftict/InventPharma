<?php

namespace App\Http\Controllers\StockTransferReport;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class StockTransferReportController extends Controller
{
    public function index(Request $request)
    {
        $data = [
            'title' => 'Stock Transfer Report By Date',
            'subtitle' => 'View Stock Transfer By Date Range',
            'filters' => [
                'from' =>monthlyDateRange()[0],
                'to'=>monthlyDateRange()[1],
                'filters' => [
                    'between.transfer_date' => monthlyDateRange()
                ]
            ]
        ];
        if($request->get('filter'))
        {
            $data['filters'] = $request->get('filter');
            $data['filters']['filters']['between.transfer_date'] = Arr::only(array_values( $request->get('filter')), [0,1]);
        }
        return setPageContent('reports.stocktransfer.index', $data);
    }


    public function by_system_user(Request $request)
    {
        $data = [
            'title' => 'Stock Transfer Report By System User',
            'subtitle' => 'View Stock Transfer By Date Range and System User',
            'filters' => [
                'from' =>monthlyDateRange()[0],
                'to'=>monthlyDateRange()[1],
                'user_id' => 1,
                'filters' => [
                    'between.transfer_date' => monthlyDateRange(),
                    'user_id' => 1
                ]
            ]
        ];
        if($request->get('filter'))
        {
            $data['filters'] = $request->get('filter');
            $data['filters']['filters']['between.transfer_date'] = Arr::only(array_values( $request->get('filter')), [0,1]);
            $data['filters']['filters']['user_id'] = $data['filters']['user_id'];

        }

        return setPageContent('reports.stocktransfer.index', $data);
    }

    public function by_status(Request $request)
    {
        $data = [
            'title' => 'Stock Transfer Report By Date',
            'subtitle' => 'View Stock Transfer Report By Date Range and System Status',
            'filters' => [
                'from' =>monthlyDateRange()[0],
                'to'=>monthlyDateRange()[1],
                'status_id' => 1,
                'filters' => [
                    'between.transfer_date' => monthlyDateRange(),
                    'status_id' => 1
                ]
            ]
        ];
        if($request->get('filter'))
        {
            $data['filters'] = $request->get('filter');
            $data['filters']['filters']['between.transfer_date'] = Arr::only(array_values( $request->get('filter')), [0,1]);
            $data['filters']['filters']['status_id'] = $data['filters']['status_id'];

        }
        return view('reports.stocktransfer.index', $data);
    }


    public function transfer_summary(Request $request)
    {
        $data = [
            'title' => 'Stock Transfer Summary Report',
            'subtitle' => 'View Stock Transfer Summary Report',
            'filters' => [
                'from' =>monthlyDateRange()[0],
                'to'=>monthlyDateRange()[1],
                'status_id' => 1,
                'department'=> 'quantity',
                'filters' => [
                    'between.transfer_date' => monthlyDateRange(),
                    'status_id' => 1,
                    'department'=> 'quantity',
                ]
            ]
        ];
        if($request->get('filter'))
        {
            $data['filters'] = $request->get('filter');
            $data['filters']['filters']['between.transfer_date'] = Arr::only(array_values( $request->get('filter')), [0,1]);
            $data['filters']['filters']['status_id'] = $data['filters']['status_id'];
            $data['filters']['filters']['department'] = $data['filters']['department'];

        }
        return view('reports.stocktransfer.transfer_summary', $data);
    }



    public function by_product(Request $request)
    {
        $data = [
            'title' => 'Stock Transfer Report By Product',
            'subtitle' => 'View Stock Transfer Report By Date Range and Product',
            'filters' => [
                'stock' => Stock::find(1),
                'from' =>todaysDate(),
                'to'=>todaysDate(),
                'stock_id' => 1,
                'status_id' => 1,
                'filters' => [
                    'between.transfer_date' =>[todaysDate(),todaysDate()],
                    'stock_id' => 1,
                    'status_id' => 1
                ]
            ]
        ];

        if($request->get('filter'))
        {
            $data['filters'] = $request->get('filter');
            $data['filters']['stock'] = Stock::find($data['filters']['stock_id']);
            $data['filters']['filters']['between.transfer_date'] = Arr::only(array_values( $request->get('filter')), [0,1]);
            $data['filters']['filters']['stock_id'] = $data['filters']['stock_id'];
            $data['filters']['filters']['status_id'] = $data['filters']['status_id'];
        }
        return view('reports.stocktransfer.stocktransferbyproduct', $data);
    }

}
