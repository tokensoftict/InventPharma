<?php

namespace App\Http\Controllers\StaffPerformanceReport;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class StaffPerformanceReportController extends Controller
{

    public function sales_order_performancereport(Request $request){
        $items = [
            [
                'id' =>"retail",
                'name' => "Retail"
            ],
            [
                'id' =>"wholesales",
                'name' => "Wholesales"
            ],
            [
                'id' =>"bulksales",
                'name' => "Bulksales"
            ]
        ];

        $data = [
            'title' => 'Sales Order Performance Report',
            'subtitle' => 'Sales Order Performance Report',
            'filters' => [
                'custom_dropdown_id' => "retail",
                'label_name' => 'Departments',
                'items' => $items,
                'from' =>monthlyDateRange()[0],
                'to'=>monthlyDateRange()[1],
                'filters' => [
                    'custom_dropdown_id' => "retail",
                    'between.invoice_date' => monthlyDateRange()
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

        return view('reports.staffperformancereport.sales_order_performancereport', $data);
    }



    public function picker_and_packer(Request $request)
    {
        $data = [
            'title' => 'Picker and Packer Performance Report',
            'subtitle' => 'View Picker and Packer Performance Report',
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

        return view('reports.staffperformancereport.picker_report', $data);
    }

}
