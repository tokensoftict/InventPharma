<?php

namespace App\Http\Controllers\PurchaseOrder;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SupplierCreditController extends Controller
{

    public function index()
    {
        $data = [
            'filters' => ['payment_date'=>todaysDate(), 'type' => 'CREDIT']
        ];
        return view('purchase.supplier.credit.index', $data);
    }

}
