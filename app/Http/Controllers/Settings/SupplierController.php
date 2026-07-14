<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SupplierController extends Controller
{

    public function index(){

        return setPageContent('settings.supplier.index');
    }


    public function create(){

    }


    public function show($id){
        $supplier = \App\Models\Supplier::findOrFail($id);
        $data = [
            'supplier' => $supplier,
            'title' => 'Supplier Details: ' . $supplier->name,
            'subtitle' => 'View payments, credit history, and manage cheque approvals'
        ];
        return view('settings.supplier.show', $data);
    }


    public function toggle($id){

    }


    public function update(Request $request, $id){

    }


}
