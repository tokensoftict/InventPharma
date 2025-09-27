<?php

namespace App\Http\Controllers\InvoiceAndSales;

use App\Classes\Settings;
use App\Http\Controllers\Controller;
use App\Jobs\AddCustomerToWaitingList;
use App\Jobs\PushStockUpdateToServer;
use App\Models\Invoice;
use App\Models\WaitingCustomer;
use App\Repositories\InvoiceRepository;
use App\Services\Online\ProcessOrderService;
use App\Traits\InvoiceTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDF;

class InvoiceController extends Controller
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

        $data['title'] = 'New ';
        $data['subtitle'] = 'Generate New ';
        $data['invoice'] = new Invoice();
        $data['department'] = auth()->user()->department_id;
        return view('invoiceandsales.form', $data);
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

        $data['title'] = 'Draft Invoice(s)';
        $data['subtitle'] = 'List of Unpaid Invoice - '.$data['filters']['filters']['invoice_date'];

        if(auth()->user()->department_id !== 5) {
            $dpt = department_by_id(auth()->user()->department_id)->quantity_column;
            $data['filters']['filters']['in_department'] = $dpt;
        }

        $data['filters']['filters']['status_id'] = status('Draft');

        return setPageContent('invoiceandsales.index', $data);
    }


    public function packing(Request $request)
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

        $data['title'] = 'Packing Invoice(s)';
        $data['subtitle'] = 'List of Invoice Currently Being Pack - '.$data['filters']['filters']['invoice_date'];

        if(auth()->user()->department_id !== 5) {
            $dpt = department_by_id(auth()->user()->department_id)->quantity_column;
            $data['filters']['filters']['in_department'] = $dpt;
        }

        $data['filters']['filters']['status_id'] = status('Packing');

        return view('invoiceandsales.index', $data);
    }



    public function alredy_packed(Request $request)
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

        $data['title'] = 'List of Already Packed Invoice(s)';
        $data['subtitle'] = 'List of Invoice(s) already packed and Waiting for Payment - '.$data['filters']['filters']['invoice_date'];

        if(auth()->user()->department_id !== 5) {
            $dpt = department_by_id(auth()->user()->department_id)->quantity_column;
            $data['filters']['filters']['in_department'] = $dpt;
        }

        $data['filters']['filters']['status_id'] = status('Packed-Waiting-For-Payment');

        return setPageContent('invoiceandsales.index', $data);
    }


    public function waiting_for_credit_approval(Request $request)
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

        $data['title'] = 'Waiting Credit Payment Approval Invoice(s)';
        $data['subtitle'] = 'List of Waiting For Credit Payment Approval Invoice(s) - '.$data['filters']['filters']['invoice_date'];

        if(auth()->user()->department_id !== 5) {
            $dpt = department_by_id(auth()->user()->department_id)->quantity_column;
            $data['filters']['filters']['in_department'] = $dpt;
        }

        $data['filters']['filters']['status_id'] = status('Waiting-For-Credit-Approval');

        return setPageContent('invoiceandsales.index', $data);
    }


    public function waiting_for_cheque_approval(Request $request)
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

        $data['title'] = 'Waiting Cheque Approval Invoice(s)';
        $data['subtitle'] = 'List of Waiting For Cheque Approval Invoice(s) - '.$data['filters']['filters']['invoice_date'];

        if(auth()->user()->department_id !== 5) {
            $dpt = department_by_id(auth()->user()->department_id)->quantity_column;
            $data['filters']['filters']['in_department'] = $dpt;
        }

        $data['filters']['filters']['status_id'] = status('Waiting-For-Cheque-Approval');

        return setPageContent('invoiceandsales.index', $data);
    }

    public function discount(Request $request)
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

        $data['title'] = 'Discount Invoice(s)';
        $data['subtitle'] = 'List of Waiting for Discount Invoice - '.$data['filters']['filters']['invoice_date'];

        if(auth()->user()->department_id !== 5) {

            $dpt = department_by_id(auth()->user()->department_id)->quantity_column;
            $data['filters']['filters']['in_department'] = $dpt;

        }

        $data['filters']['filters']['status_id'] = status('Discount');

        return setPageContent('invoiceandsales.index', $data);
    }


    public function waiting(Request $request)
    {
        $data = [
            'filters' => [
                'date_added' =>dailyDate(),
                'filters' => [
                    'date_added' => todaysDate()
                ]
            ]
        ];

        if($request->get('filter'))
        {
            $data['filters'] = $request->get('filter');
            $data['filters']['filters']['date_added'] = $request->get('filter')['date_added'];
        }

        $data['title'] = 'Waiting Customer List invoice(s)';
        $data['subtitle'] = 'List of Customer Waiting Queue invoices - '.$data['filters']['filters']['date_added'];

        return setPageContent('invoiceandsales.waiting', $data);
    }

    public function paid(Request $request)
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

        $data['title'] = 'Paid Invoice(s)';
        $data['subtitle'] = 'List of Paid Invoice - '.$data['filters']['filters']['invoice_date'];

        if(auth()->user()->department_id !== 5) {
            $dpt = department_by_id(auth()->user()->department_id)->quantity_column;
            $data['filters']['filters']['in_department'] = $dpt;
        }

        $data['filters']['filters']['status_id'] = status('Paid');

        return setPageContent('invoiceandsales.index', $data);
    }


    public function dispatched(Request $request)
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

        if(auth()->user()->department_id !== 5) {
            $dpt = department_by_id(auth()->user()->department_id)->quantity_column;
            $data['filters']['filters']['in_department'] = $dpt;
        }

        $data['filters']['filters']['status_id'] = status('Complete');

        return setPageContent('invoiceandsales.index', $data);
    }

    public function deleted(Request $request)
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

        $data['title'] = 'Deleted Invoice(s)';
        $data['subtitle'] = 'List of  Deleted Invoice - '. $data['filters']['filters']['invoice_date'];

        if(auth()->user()->department_id !== 5) {
            $dpt = department_by_id(auth()->user()->department_id)->quantity_column;
            $data['filters']['filters']['in_department'] = $dpt;
        }

        $data['filters']['filters']['status_id'] = status('Deleted');

        return setPageContent('invoiceandsales.index', $data);
    }



    public function return(Invoice $invoice)
    {
        $data = [];

        $data['title'] = 'Return ';
        $data['subtitle'] = 'Return Already Generated ';
        $data['invoice'] = $invoice;
        $data['department'] = department_by_quantity_column($invoice->in_department)->id;
        logActivity($invoice->id, $invoice->invoice_number,'Invoice return page was viewed :'.status_name($invoice->status_id));

        /*
        we need to cache the invoice id because we want to know if they are returning an online order so that we dont set the payment today
        begining of logic
        */
        if(!is_null($invoice->onliner_order_id)) {
            \Cache::remember("ReturnedOnlineOrder",83400, function () use ($invoice){
                $existing = \Cache::get("ReturnedOnlineOrder");
                if(!$existing) $existing = [];
                $existing[] = $invoice->id;
                return $existing;
            });
        }

        return view('invoiceandsales.form', $data);
    }


    public function print_way_bill(Invoice $invoice)
    {
        logActivity($invoice->id, $invoice->invoice_number,'Print Invoice Way Bill Status:'.status_name($invoice->status_id));

        logInvoicePrint(Settings::$printType['waybill'], $invoice);

        $data['invoice'] = $invoice;
        $data['store'] = $this->settings->store();
        $pdf = PDF::loadView("print.pos_afour_waybill",$data);
        $pdf->getMpdf()->SetWatermarkText(strtoupper($invoice->status->name));
        $pdf->getMpdf()->showWatermarkText = true;
        return $pdf->stream('documentwaybill.pdf');
    }

    public function print_afour(Invoice $invoice)
    {
        logActivity($invoice->id, $invoice->invoice_number,'Print Invoice A4 Status:'.status_name($invoice->status_id));

        logInvoicePrint(Settings::$printType['a4'], $invoice);

        $data['invoice'] = $invoice;
        $data['store'] = $this->settings->store();
        $pdf = PDF::loadView("print.pos_afour",$data);
        $pdf->getMpdf()->SetWatermarkText(strtoupper($invoice->status->name));
        $pdf->getMpdf()->showWatermarkText = true;

        return $pdf->stream('documenta4.pdf');
    }

    public function applyInvoiceDiscount(Invoice $invoice)
    {

    }

    public function approve_or_decline_credit_payment(Invoice $invoice)
    {

    }

    public function approve_or_decline_cheque_payment(Invoice $invoice)
    {

    }
    /*
        public function applyForCheque(Invoice $invoice)
        {
            $data = [];

            $data['title'] = 'Cheque Payment Approval';
            $data['subtitle'] = 'Apply For Cheque Payment Approval';
            $data['invoice'] = $invoice;

            logActivity($invoice->id, $invoice->invoice_number,'requested for cheque approval page was viewed');

            return view('invoiceandsales.applychequeapproval', $data);
        }


        public function applyForCredit(Invoice $invoice){
            $data = [];

            $data['title'] = 'Credit Payment Approval';
            $data['subtitle'] = 'Apply For Credit Payment Approval';
            $data['invoice'] = $invoice;

            logActivity($invoice->id, $invoice->invoice_number,'requested for credit approval page was viewed');

            return view('invoiceandsales.applycreditapproval', $data);
        }
    */
    public function applyProductDiscount(Invoice $invoice)
    {
        $data = [];

        $data['title'] = 'Product Discount';
        $data['subtitle'] = 'Apply Product Discount ';
        $data['invoice'] = $invoice;

        logActivity($invoice->id, $invoice->invoice_number,'Apply invoice discount was page was viewed :'.$invoice->status->name);

        return view('invoiceandsales.applyproductdiscount', $data);
    }

    public function destroy(Invoice $invoice)
    {

        InvoiceRepository::returnStock($invoice);

        $invoice->status_id = status('Deleted');

        $invoice->update();

        LogActivity($invoice->id, $invoice->invoice_number,"Invoice payment was deleted ".$invoice->status);

        if($invoice->online_order_status == "1"){

            LogActivity($invoice->id, $invoice->invoice_number,"Online invoice, update sent to server ".$invoice->status);

            ProcessOrderService::sendBackCancelOrderMessage($invoice->onliner_order_id);

        }

        dispatch(new PushStockUpdateToServer(array_column($invoice->invoiceitems->toArray(), 'stock_id')));

        return redirect()->route('invoiceandsales.draft');
    }

    public function update(Invoice $invoice)
    {

    }

    public function dispatchInvoice(Invoice $invoice)
    {

    }


    public function editInvoiceDate()
    {

    }


    public function mergeInvoice(Request $request)
    {
        if($request->method() == "POST"){
            if($request->doc == "invoice"){
                return $this->print_afour_merge($request->main, explode(",",$request->child));
            }else{
                return $this->print_way_bill_merge($request->main, explode(",",$request->child));
            }
        }

        $data = [];

        $data['title'] = 'Marge Invoice';
        $data['subtitle'] = 'Combine Multiple Invoice Together and Print ';

        return view('invoiceandsales.margeInvoice', $data);
    }



    public function processOnlineInvoice(Invoice $invoice)
    {
        DB::transaction(function() use ($invoice){
            $invoice->status_id = status('Packing');
            $invoice->update();
            logActivity($invoice->id, $invoice->invoice_number, "online invoice was updated to Packing");
        });

        return redirect()->back()->with('success', 'Invoice status as been changed to Packing successfully');
    }


    public function packOnlineInvoice(Invoice $invoice)
    {
        DB::transaction(function() use ($invoice){
            ProcessOrderService::sendBackWaitingForPaymentMessage($invoice->onliner_order_id);
            $invoice->status_id = status('Packed-Waiting-For-Payment');
            $invoice->online_order_debit = 1;
            $invoice->update();
            logActivity($invoice->id, $invoice->invoice_number, "online invoice was updated to Packed-Waiting-For-Payment");
        });
        return redirect()->back()->with('success', 'Invoice status as been changed to Packed successfully, and customer has been notify');
    }


    /**
     * @param Invoice $invoice
     * @return \Illuminate\Http\RedirectResponse|mixed
     * @throws \Throwable
     */
    public function addToWaitingList(Invoice $invoice)
    {
        return DB::transaction(function() use ($invoice){
            AddCustomerToWaitingList::dispatch($invoice);
            logActivity($invoice->id, $invoice->invoice_number, "Invoice was added to waiting list");
            return redirect()->back()->with('success', 'Invoice was added to waiting list successfully!');
        });
    }

    /**
     * @param Invoice $invoice
     * @return \Illuminate\Http\RedirectResponse|mixed
     * @throws \Throwable
     */
    public function removeFromWaitingList(Invoice $invoice)
    {
        return DB::transaction(function() use ($invoice){
            WaitingCustomer::where('invoice_id', $invoice->id)->delete();
            logActivity($invoice->id, $invoice->invoice_number, "Invoice was removed to waiting list");
            return redirect()->back()->with('success', 'Invoice was added to waiting list successfully!');
        });
    }


    public function packWaitingListInvoice(Invoice $invoice)
    {
        return DB::transaction(function() use ($invoice){
            $waitingListInvoice = WaitingCustomer::where('invoice_id', $invoice->id)->first();
            if($waitingListInvoice->status == WaitingCustomer::$waitingInvoiceStatus['complete_picking']){
                $waitingListInvoice->status = WaitingCustomer::$waitingInvoiceStatus['packing'];
                $waitingListInvoice->update();
                addCustomerWaitingListStatusHistory($waitingListInvoice, 'packing');
                return redirect()->back()->with('success', 'Waiting Invoice queue has been updated to Packing successfully!');
            }
            return redirect()->back()->with('error', 'There was an error updating customer waiting list invoice');
        });
    }

    public function completePickingWaitingListInvoice(Invoice $invoice)
    {
        return DB::transaction(function() use ($invoice){
            $waitingListInvoice = WaitingCustomer::where('invoice_id', $invoice->id)->first();
            if($waitingListInvoice->status == WaitingCustomer::$waitingInvoiceStatus['picking']){
                $waitingListInvoice->status = WaitingCustomer::$waitingInvoiceStatus['complete_picking'];
                $waitingListInvoice->update();
                addCustomerWaitingListStatusHistory($waitingListInvoice, 'complete_picking');
                return redirect()->back()->with('success', 'Waiting Invoice queue has been updated to Complete Picking successfully!');
            }
        });
    }


    public function pickWaitingListInvoice(Invoice $invoice)
    {
        return DB::transaction(function() use ($invoice){
            $waitingListInvoice = WaitingCustomer::where('invoice_id', $invoice->id)->first();
            if($waitingListInvoice->status == WaitingCustomer::$waitingInvoiceStatus['waiting']){
                $waitingListInvoice->status = WaitingCustomer::$waitingInvoiceStatus['picking'];
                $waitingListInvoice->update();
                addCustomerWaitingListStatusHistory($waitingListInvoice, 'picking');
                return redirect()->back()->with('success', 'Waiting Invoice queue has been updated to Packing successfully!');
            }
            return redirect()->back()->with('error', 'There was an error updating customer waiting list invoice');
        });
    }

    public function packedWaitingListInvoice(Invoice $invoice)
    {
        return DB::transaction(function() use ($invoice){
            $waitingListInvoice = WaitingCustomer::where('invoice_id', $invoice->id)->first();
            if($waitingListInvoice->status == WaitingCustomer::$waitingInvoiceStatus['packing']){
                $waitingListInvoice->status = WaitingCustomer::$waitingInvoiceStatus['packed'];
                $waitingListInvoice->update();
                addCustomerWaitingListStatusHistory($waitingListInvoice, 'packed');
                return redirect()->back()->with('success', 'Waiting Invoice queue has been updated to Packed successfully!');
            }
            return redirect()->back()->with('error', 'There was an error updating customer waiting list invoice');
        });
    }





}
