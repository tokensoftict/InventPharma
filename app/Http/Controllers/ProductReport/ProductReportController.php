<?php

namespace App\Http\Controllers\ProductReport;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ProductReportController extends Controller
{

    public function bincard_report(Request $request)
    {
        $data = [
            'title' => 'Product Bincard Report',
            'subtitle' => 'View Product Movement from different department',
            'filters' => [
                'stock' => Stock::find(1),
                'department'=> 'quantity',
                'from' =>monthlyDateRange()[0],
                'to'=>monthlyDateRange()[1],
                'stock_id' => 1,
                'filters' => [
                    'between.stockbincards.bin_card_date' => monthlyDateRange(),
                    'stock_id' => 1,
                    'department'=> 'quantity',
                ]
            ]
        ];
        if($request->get('filter'))
        {

            $data['filters'] = $request->get('filter');
            $data['filters']['stock'] = Stock::find($data['filters']['stock_id']);
            $data['filters']['filters']['between.stockbincards.bin_card_date'] = Arr::only(array_values( $request->get('filter')), [0,1]);
            $data['filters']['filters']['stock_id'] = $data['filters']['stock_id'];
            $data['filters']['filters']['department'] = $data['filters']['department'];

        }
        return view('reports.product.bincard', $data);
    }


    public function nearoutofstock()
    {
        $data = [
            'title' => 'Product Near OS Report',
            'subtitle' => 'View Product that is almost out of stock and require purchasing',
        ];

        return view('reports.product.nearos', $data);
    }


    public function retailnearoutofstock()
    {
        $data = [
            'title' => 'Retail Product Near OS Report',
            'subtitle' => 'View Retail Product that is almost out of stock and require purchasing',
        ];

        return view('reports.product.retailnearos', $data);
    }


    public function stockpriceanalysis()
    {
        $data = [
            'title' => 'Stock Price Analysis Report',
            'subtitle' => 'Stock Price Analysis Report',
        ];

        return view('reports.product.stockpriceanalysis', $data);
    }


    public function movingstocksreport()
    {
        $data = [
            'title' => 'Moving Stocks Report',
            'subtitle' => 'View Fast Selling Product Report',
        ];

        return view('reports.product.movingstocks', $data);
    }


    public function view_stock_batch_product(Request $request)
    {
        $data = [
            'title' => 'Stock Batch Update Report',
            'subtitle' => 'View Stock Batch Update Report',
            'filters' => [
                'from' =>monthlyDateRange()[0],
                'to'=>monthlyDateRange()[1],
                'filters' => [
                    'between.stockbincards.bin_card_date' => monthlyDateRange(),
                    'stock_id' => 1,
                ]
            ]
        ];
        if($request->get('filter'))
        {

            $data['filters'] = $request->get('filter');
            $data['filters']['filters']['between.stockbincards.bin_card_date'] = Arr::only(array_values( $request->get('filter')), [0,1]);
        }

        return view('reports.product.stockbatchreport', $data);
    }



    public function balance_stock_worth(Request $request)
    {
        $data = [
            'title' => 'Balance Stock Worth Report',
            'subtitle' => 'View Total Stock Transfer,Sold,Purchase Report',
            'filters' => [
                'department'=> 'quantity',
                'from' =>monthlyDateRange()[0],
                'to'=>monthlyDateRange()[1],
                'filters' => [
                    'filter_date' => monthlyDateRange(),
                    'department'=> 'quantity',
                ]
            ]
        ];
        if($request->get('filter'))
        {

            $data['filters'] = $request->get('filter');
            $data['filters']['filters']['filter_date'] = Arr::only(array_values( $request->get('filter')), [0,1]);
            $data['filters']['filters']['department'] = $data['filters']['department'];
        }

        return view('reports.product.balancestockworth', $data);
    }


    public function opening_stock(Request $request)
    {
        $data = [
            'title' => 'Stock Opening Report',
            'subtitle' => 'View Stock Opening Report',
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


        return view('reports.product.stockopeningreport', $data);
    }



    public function product_price_change_history(Request $request)
    {
        $data = [
            'title' => 'Product Price Change History Report',
            'subtitle' => 'Product Price Change History Report',
            'filters' => [
                'stock' => Stock::where('status', 1)->first(),
                'department'=> 'wholesales',
                'from' =>monthlyDateRange()[0],
                'to'=>monthlyDateRange()[1],
                'stock_id' => Stock::where('status', 1)->first()->id,
                'filters' => [
                    'between.pricechangehistories.change_date' => monthlyDateRange(),
                    'stock_id' => Stock::where('status', 1)->first(),
                    'department'=> 'wholesales',
                ]
            ]
        ];

        if($request->get('filter'))
        {
            $data['filters'] = $request->get('filter');
            $data['filters']['stock'] = Stock::find($data['filters']['stock_id']);
            $data['filters']['filters']['between.pricechangehistories.change_date'] = Arr::only(array_values( $request->get('filter')), [0,1]);
            $data['filters']['filters']['stock_id'] = $data['filters']['stock_id'];
            $data['filters']['filters']['department'] = $data['filters']['department'];
        }

        return view('reports.product.product_price_change_history', $data);
    }



    public function supplierDBOverviewReport(Request $request)
    {
        $data = [
            'title' => 'Supplier DB Overview Report',
            'subtitle' => 'Supplier DB Overview Report',
            'filters' => [
                'from' =>dailyDate(),
                'filters' => [
                    'payment_date' => dailyDate(),
                ]
            ]
        ];
        if($request->get('filter'))
        {
            $data['filters'] = $request->get('filter');
            $data['filters']['filters']['payment_date'] = $request->get('filter')['from'];
        }


        return view('reports.product.supplierdboverviewreport', $data);
    }

}
