<?php

namespace App\Http\Controllers\PurchaseOrder;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    public function index()
    {

        $data = [
            'title' => "List Draft Stock Purchase List",
            'subtitle' => "Drafted Stock Purchase List",
            'filters' => ['status_id' => status('Draft'), 'date_created'=>todaysDate()]
        ];

        return setPageContent('purchase.index',$data);

    }

    public function  completed()
    {
        $data = [
            'title' => "List Completed Stock Purchase List",
            'subtitle' => "Completed Stock Purchase List",
            'filters' => ['status_id' => status('Complete'),  'date_created'=>todaysDate()]
        ];

        return setPageContent('purchase.index',$data);
    }

    public function create(Request $request)
    {
        $data = [
            'title' => "New Stock Purchase",
            'subtitle' => "Create New Stock Purchase",
            'purchase' => new Purchase(),
            'departments' => departments()->filter(function($item){
                return $item->quantity_column !== NULL;
            }),
        ];

        return setPageContent('purchase.form',$data);
    }

    public function show(Purchase $purchase)
    {
        $data = [
            'title' => "Show Purchase Details",
            'subtitle' => "Show Product Purchase List",
            'purchase' => $purchase
        ];

        return setPageContent('purchase.show',$data);
    }

    public function edit(Purchase $purchase)
    {
        $data = [
            'title' => "Edit Stock Purchase List",
            'subtitle' => "Edit and Update Stock Purchase",
            'purchase' => $purchase,
            'departments' => departments()->filter(function($item){
                return $item->quantity_column !== NULL;
            }),
        ];

        return setPageContent('purchase.form',$data);
    }

    public function destroy(Purchase $purchase)
    {

    }

    public function complete(Purchase $purchase)
    {

    }
}
