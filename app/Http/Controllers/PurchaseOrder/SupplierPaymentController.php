<?php

namespace App\Http\Controllers\PurchaseOrder;

use App\Http\Controllers\Controller;
use App\Models\SupplierCreditPaymentHistory;
use Illuminate\Http\Request;

class SupplierPaymentController extends Controller
{
    public function index(){
        $data = [
            'filters' => ['payment_date'=>todaysDate(), 'type' => 'PAYMENT']
        ];
        return view('purchase.supplier.payment.index', $data);
    }


    public function create(){
        $data = [
            'supplierCreditPaymentHistory'=> new SupplierCreditPaymentHistory(),
            'title'=>'New',
            'subtitle'=>'Create new'
        ];

        return view('purchase.supplier.payment.form',$data);
    }


    public function edit(SupplierCreditPaymentHistory $supplierCreditPaymentHistory){
        $data = [
            'supplierCreditPaymentHistory'=> $supplierCreditPaymentHistory,
            'title'=>'Update',
            'subtitle'=>'Update'
        ];

        return view('purchase.supplier.payment.form',$data);
    }

    public function update(SupplierCreditPaymentHistory $request, $id){}

    public function destroy(SupplierCreditPaymentHistory $supplierCreditPaymentHistory){

        if($supplierCreditPaymentHistory) $supplierCreditPaymentHistory->delete();

        return redirect()->route('purchase.sup')->with('success', 'Payment has been deleted successfully');
    }
}
