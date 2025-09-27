<?php
namespace App\Traits;

use App\Classes\Settings;
use App\Models\Invoice;
use Illuminate\Support\Facades\Auth;
use PDF;

trait InvoiceTrait
{

    public function print_pos(Invoice $invoice)
    {

        if($invoice->in_department=='retail' && $invoice->retail_printed) {

            if(!userCanView('invoiceandsales.rePrintInvoice')){
                return 'You can only print completed invoice once';
            }else{
                logActivity($invoice->id, $invoice->invoice_number,'Retail Invoice was Re-print');
            }
        }

        if($invoice->in_department === 'retail' && ($invoice->status_id == status('Paid') || $invoice->status_id == status('Complete'))  && $invoice->retail_printed === false){
            $invoice->retail_printed = '1';
            $invoice->update();
        }

        logActivity($invoice->id, $invoice->invoice_number,'Print Invoice Thermal Status:'.$invoice->status->name);

        logInvoicePrint(Settings::$printType['thermal'], $invoice);

        $data['invoice'] =$invoice;
        $data['store'] = $this->settings->store();
        $page_size = $invoice->invoiceitems->count() * 15;
        if($invoice->onlineordertotals()->exists()){
            $page_size +=  ($invoice->onlineordertotals->count() -1) * 15;
        }
        $page_size += 180;
        $pdf = PDF::loadView('print.pos', $data,[],[
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
            'default_font_size'    => '12',
        ]);
        $pdf->getMpdf()->SetWatermarkText(strtoupper(status_name($invoice->status_id)));
        $pdf->getMpdf()->showWatermarkText = true;

        if($invoice->in_department !="retail" && $invoice->status_id !=status('Complete')) {
            if($invoice->online_order_status == "1"){
                $departments = [
                    "bulksales",
                    "quantity",
                    "wholesales",
                ];
                foreach($departments as $dept){
                    $data['department'] = $dept;
                    if($invoice->invoiceitembatches()->where('department',$dept)->count() > 0) {
                        $pdf->getMpdf()->AddPage('P', '', '', '', '', 0, 0, 0, 0, 0, 0);
                        $pdf->getMpdf()->WriteHTML(view('print.pos_picker_online', $data));
                    }
                }
            }else {
                $pdf->getMpdf()->AddPage('P', '', '', '', '', 0, 0, 0, 0, 0, 0);
                $pdf->getMpdf()->WriteHTML(view('print.pos_picker', $data));
            }

        }
        if($invoice->in_department =="retail" && Auth::user()->department_id ==department_by_quantity_column('retail')->id){
            //$pdf->getMpdf()->AddPage('P', '', '', '', '', 0, 0, 0, 0, 0, 0);
           // $pdf->getMpdf()->WriteHTML(view('print.pos', $data));
        }
        logActivity($invoice->id, $invoice->invoice_number,"Print POS Invoice Status : ".$invoice->status->name);

        return $pdf->stream('document.pdf');
    }

    public function view(Invoice $invoice)
    {
        //$invoice->load(['customer','user','payment','payment.paymentmethoditems','create_by','last_updated','picked','checked','packed','user','invoiceactivitylogs','invoiceitembatches','invoiceitems','onlineordertotals']);

        $data = [];

        $data['invoice'] = $invoice;

        logActivity($invoice->id, $invoice->invoice_number,'Invoice viewed :'.status_name($invoice->status_id));

        return view('invoiceandsales.show', $data);
    }

    public function edit(Invoice $invoice)
    {
        $data = [];

        $data['title'] = 'Edit ';
        $data['subtitle'] = 'Update Already Generated ';
        $data['invoice'] = $invoice;
        $data['department'] =department_by_quantity_column( $invoice->in_department)->id;
        logActivity($invoice->id, $invoice->invoice_number,'Invoice edit/update page was viewed :'.status_name($invoice->status_id));

        return setPageContent('invoiceandsales.form', $data);
    }


    public function requestForDiscount()
    {

    }


    private function print_afour_merge($main_invoice, $child_invoice)
    {
        $data = [];
        $invoice =Invoice::where(function($query) use(&$main_invoice){ $query->orWhere('id',$main_invoice)->orWhere('invoice_number',$main_invoice); })->first();
        if(!$invoice){
            return redirect()->route('invoiceandsales.merge')->with('error', "Invalid Main Invoice Number, please check and try again");
        }

        if($invoice->status_id != status('Complete') && $invoice->status_id != status('Paid'))
        {
            return redirect()->route('invoiceandsales.merge')->with('error', "Main Invoice has not been dispatched / completed - ".$invoice->invoice_number);
        }

        $data['invoice'] = $invoice;
        $invoice_child_data = [];
        $invoice_discount = 0;
        $invoice_child = Invoice::whereIn('invoice_number',$child_invoice)->get();
        LogActivity($invoice->id, $invoice->invoice_number,"Invoice a4 print merged, Main Invoice : $main_invoice, Child Invoice ".implode(",",$child_invoice));
        foreach ($invoice_child as $child){
            if($child->status_id != status('Complete') && $child->status_id != status('Paid'))
            {
                return redirect()->route('invoiceandsales.merge')->with('error', "Child Invoice ".$child->invoice_number." has not been dispatched / completed");
            }
            LogActivity($child->id, $child->invoice_number,"Invoice a4 print merged, Main Invoice : $main_invoice, Child Invoice ".implode(",",$child_invoice));
            $invoice_discount+=$child->discount_amount;
            foreach($child->invoiceitems()->get() as $item){
                $invoice_child_data[] = $item;
            }
        }
        $data['invoice_child'] = $invoice_child_data;
        $data['invoice_discount'] = $invoice_discount;
        $data['store'] = $this->settings->store();
        //return view("print.pos_afour_merge",$data);
        $pdf = PDF::loadView("print.pos_afour_merge",$data);
        return $pdf->stream('document.pdf');
    }
    private function print_way_bill_merge($main_invoice, $child_invoice)
    {
        $data = [];
        $invoice =Invoice::where(function($query) use(&$main_invoice){ $query->orWhere('id',$main_invoice)->orWhere('invoice_number',$main_invoice); })->first();
        if(!$invoice){
            return redirect()->route('invoiceandsales.merge')->with('error', "Invalid Main Invoice Number, please check and try again");
        }

        $data['invoice'] = $invoice;
        $invoice_child_data = [];
        $invoice_discount = 0;
        $invoice_child = Invoice::whereIn('invoice_number',$child_invoice)->get();
        LogActivity($invoice->id, $invoice->invoice_number,"Invoice a4 print merged, Main Invoice : $main_invoice, Child Invoice ".implode(",",$child_invoice));

        foreach ($invoice_child as $child){
            LogActivity($child->id, $child->invoice_number,"Invoice a4 print merged, Main Invoice : $main_invoice, Child Invoice ".implode(",",$child_invoice));
            $invoice_discount+=$child->discount_amount;
            foreach($child->invoiceitems()->get() as $item){
                $invoice_child_data[] = $item;
            }
        }
        $data['invoice_child'] = $invoice_child_data;
        $data['invoice_discount'] = $invoice_discount;
        $data['store'] = $this->settings->store();
        //return view("print.pos_afour_merge",$data);
        $pdf = PDF::loadView("print.pos_afour_waybill_merge",$data);
        return $pdf->stream('document.pdf');
    }



    public function rePrintInvoice(){

    }


    public function checkOutInvoice()
    {
        return view('invoiceandsales.scan');
    }

}
