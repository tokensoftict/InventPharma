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

    public function approve_supplier_cheque_payment()
    {

    }

    public function decline_supplier_cheque_payment()
    {

    }

    public function list_supplier_payment_by_date()
    {
        return view('purchase.supplier.payment.approve_cheque');
    }

    public function valued_date_cheque_report()
    {
        return view('purchase.supplier.payment.valued_date_cheque_report');
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

    public function show(SupplierCreditPaymentHistory $supplierCreditPaymentHistory)
    {
        $data = [
            'payment' => $supplierCreditPaymentHistory,
            'title' => 'Supplier Payment Details',
            'subtitle' => 'View payment details and manage cheque approvals'
        ];

        return view('purchase.supplier.payment.show', $data);
    }

    public function destroy(SupplierCreditPaymentHistory $supplierCreditPaymentHistory){

        if($supplierCreditPaymentHistory) {
            if ($supplierCreditPaymentHistory->isStockForStock()) {
                if (!$supplierCreditPaymentHistory->isReversed()) {
                    app(\App\Services\StockForStockPaymentService::class)->reverseAllocation($supplierCreditPaymentHistory, 'Reversed via delete action');
                }
            } else {
                $supplierCreditPaymentHistory->delete();
            }
        }

        return redirect()->route('supplier.payment.index')->with('success', 'Payment has been reversed/deleted successfully');
    }

    public function reverse(Request $request, SupplierCreditPaymentHistory $supplierCreditPaymentHistory)
    {
        app(\App\Services\StockForStockPaymentService::class)->reverseAllocation(
            $supplierCreditPaymentHistory,
            $request->input('reason', 'Reversed by user')
        );

        return redirect()->back()->with('success', 'Stock exchange payment allocation has been reversed successfully.');
    }
}
