<?php

namespace App\Http\Controllers\PaymentManager;

use App\Classes\Settings;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use PDF;

class PaymentController extends Controller
{


    public Settings $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    public function create()
    {
        $data = [];


        $data['title'] = 'Add New Payment';
        $data['subtitle'] = 'Add invoice / Credit Payment';
        return setPageContent('payment.create', $data);
    }


    public function print_payment(Payment $payment)
    {
        $data = [];
        $data['payment'] = $payment;
        $data['store'] = $this->settings->store();
        $page_size = $data['payment']->paymentmethoditems->count() * 15;
        $page_size += 180;
        $pdf = PDF::loadView('print.pos_payment',$data,[],[
            'format' => [70,$page_size],
            'margin_left'          => 0,
            'margin_right'         => 0,
            'margin_top'           => 0,
            'margin_bottom'        => 0,
            'margin_header'        => 0,
            'margin_footer'        => 0,
            'orientation'          => 'P',
            'display_mode'         => 'fullpage',
            'custom_font_dir'      => '',
            'custom_font_data' 	   => [],
            'default_font_size'    => '14',
        ]);

        $pdf->getMpdf()->SetWatermarkText(money($payment->total_paid));
        $pdf->getMpdf()->showWatermarkText = true;

        return $pdf->stream();
    }

    public function destroy()
    {

    }


    public function list_payment()
    {
        $data = [];

        $data['title'] = 'List Payment(s)';
        $data['subtitle'] = 'Payment received today';
        $data['filters'] = ['payment_date'=>dailyDate()];
        return setPageContent('payment.index', $data);
    }


    public function show(Payment $payment)
    {
        $data = [];
        $data['title'] = 'Show Payment';
        $data['payment'] = $payment;
        $data['subtitle'] = 'Full Payment Information';
        return setPageContent('payment.show', $data);
    }


    public function createInvoicePayment(Request $request)
    {
        if(!$request->get('invoice_number')) return redirect()->route(session()->get(session()->get('past_page')));

        $data = [];
        $data['title'] = "Add Invoice Payment";
        $data['subtitle'] = "Add Invoice Payment then generate receipt";
        $data['invoice'] = Invoice::where('invoice_number', $request->get('invoice_number'))->first();
        if( $data['invoice']->payment_id !== NULL) return redirect()->route('payment.show', $data['invoice']->payment_id);
        if(! $data['invoice']) abort('404');
        return view('payment.addinvoicepayment', $data);
    }

    public function createCreditPayment(Request $request)
    {
        if(!$request->get('customer_id')) return redirect()->route('payment.create');

        $data = [];
        $data['title'] = "Add Credit Payment";
        $data['subtitle'] = "Add Customer credit payment for online invoice etc";
        $data['customer_id'] = Customer::findorfail($request->get('customer_id'));
        $data['amount'] = $request->get('amount');

        return view('payment.addcreditpayment', $data);
    }

    public function createDepositPayment()
    {

    }

}
