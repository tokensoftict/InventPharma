<?php

namespace App\Http\Controllers\RetailSales;

use App\Classes\Settings;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Traits\InvoiceTrait;
use Illuminate\Http\Request;

class RetailSalesController extends Controller
{

    use InvoiceTrait;

    public Settings $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    public function create()
    {
        $data = [];

        $data['title'] = 'New Retail ';
        $data['subtitle'] = 'New Retail ';
        $data['invoice'] = new Invoice();
        $data['department'] = 4;
        return view('invoiceandsales.form', $data);
    }

    public function sales(Request $request)
    {
        $data = [
            'filters' => [
                'invoice_date' =>dailyDate(),
                'filters' => [
                    'invoice_date' => todaysDate()
                ]
            ]
        ];
        if($request->get('filter'))
        {
            $data['filters'] = $request->get('filter');
            $data['filters']['filters']['invoice_date'] = $request->get('filter')['invoice_date'];
        }

        $data['title'] = 'Dispatched Invoice(s)';
        $data['subtitle'] = 'List of Completely Dispatched Invoice - '.$data['filters']['filters']['invoice_date'];

        $data['filters']['filters']['in_department'] = 'retail';

        $data['filters']['filters']['status_id'] = status('Complete');

        return setPageContent('invoiceandsales.index', $data);
    }

    public function draft(Request $request)
    {
        $data = [
            'filters' => [
                'invoice_date' =>dailyDate(),
                'filters' => [
                    'invoice_date' => todaysDate()
                ]
            ]
        ];
        if($request->get('filter'))
        {
            $data['filters'] = $request->get('filter');
            $data['filters']['filters']['invoice_date'] = $request->get('filter')['invoice_date'];
        }

        $data['title'] = 'Dispatched Invoice(s)';
        $data['subtitle'] = 'List of Completely Dispatched Invoice - '.$data['filters']['filters']['invoice_date'];

        $data['filters']['filters']['in_department'] = 'retail';

        $data['filters']['filters']['status_id'] = status('Draft');

        return setPageContent('invoiceandsales.index', $data);
    }


}
