<?php

namespace App\Http\Controllers\CustomerManager;

use App\Http\Controllers\Controller;
use App\Models\Customer;

class PurchaseLimitController extends Controller
{
    public function index()
    {
        return view('purchaselimit.index');
    }

    public function customerUsage(Customer $customer)
    {
        return view('purchaselimit.customer-usage', [
            'customer' => $customer,
        ]);
    }
}
