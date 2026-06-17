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


    public function move_to_draft()
    {

    }

    public function pre_draft_list()
    {

        $data = [
            'title' => "List Pre-Draft Stock Purchase List",
            'subtitle' => "Pre-Drafted Stock Purchase List",
            'filters' => ['status_id' => status('Pre-Draft'), 'date_created'=>todaysDate()]
        ];

        return view('purchase.index',$data);

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
            'suppliers' => suppliers(true),
            'depertments' => departments(true)->filter(function($item){
                if(in_array(auth()->user()->department_id, [1,2,3,5])){
                    return $item->id === 1 || $item->id == 4 || $item->id == 6;
                }
                return auth()->user()->department_id === 4;
            }),
            'purchase_date' => NULL,
            'department' => NULL,
            'supplier_id' => NULL,
            'status_id' => status('Draft'),
        ];

        if(config('app.PURCHASE_DEPARTMENT') !== false) {
            $data['depertments'] = department_by_ids(explode(",", config('app.PURCHASE_DEPARTMENT')));
        }


        if(isset($request->supplier_id) and isset($request->department) and isset($request->purchase_date)){
            $data['supplier_id'] = $request->supplier_id;
            $data['department'] = $request->department;
            $data['purchase_date'] = $request->purchase_date;
        }

        return view('purchase.form',$data);
    }

    public function pre_draft(Request $request)
    {
        $data = [
            'title' => "New Pre-Draft Purchase",
            'subtitle' => "Create Pre-Draft Purchase",
            'purchase' => new Purchase(),
            'suppliers' => suppliers(true),
            'depertments' => departments(true)->filter(function($item){
                if(in_array(auth()->user()->department_id, [1,2,3,5])){
                    return $item->id === 1 || $item->id == 4 || $item->id == 6;
                }
                return auth()->user()->department_id === 4;
            }),
            'purchase_date' => NULL,
            'department' => NULL,
            'supplier_id' => NULL,
            'status_id' => status('Pre-Draft'),
        ];

        if(config('app.PURCHASE_DEPARTMENT') !== false) {
            $data['depertments'] = department_by_ids(explode(",", config('app.PURCHASE_DEPARTMENT')));
        }

        if(isset($request->supplier_id) and isset($request->department) and isset($request->purchase_date)){
            $data['supplier_id'] = $request->supplier_id;
            $data['department'] = $request->department;
            $data['purchase_date'] = $request->purchase_date;
        }

        return view('purchase.form',$data);
    }

    public function show(Purchase $purchase)
    {
        $data = [
            'title' => "Show Purchase Details",
            'subtitle' => "Show Product Purchase List",
            'purchase' => $purchase
        ];

        return view('purchase.show',$data);
    }

    public function edit(Purchase $purchase, Request $request)
    {
        $data = [
            'title' => "Edit Stock Purchase List",
            'subtitle' => "Edit and Update Stock Purchase",
            'purchase' => $purchase,
            'suppliers' => suppliers(true),
            'depertments' => departments(true)->filter(function($item){
                if(in_array(auth()->user()->department_id, [1,2,3,5])){
                    return $item->id === 1 || $item->id == 4 || $item->id == 6;
                }
                return auth()->user()->department_id === 4;
            }),

        ];

        $data['supplier_id'] = $purchase->supplier_id;
        $data['department'] = $purchase->department;
        $data['purchase_date'] = $purchase->date_created;

        if(isset($request->supplier_id) and isset($request->department) and isset($request->purchase_date)){
            $data['supplier_id'] = $request->supplier_id;
            $data['department'] = $request->department;
            $data['purchase_date'] = $request->purchase_date;
        }


        if(config('app.PURCHASE_DEPARTMENT') !== false) {
            $data['depertments'] = department_by_ids(explode(",", config('app.PURCHASE_DEPARTMENT')));
        }


        return setPageContent('purchase.form',$data);
    }

    public function destroy(Purchase $purchase)
    {

    }

    public function complete(Purchase $purchase)
    {

    }
}
