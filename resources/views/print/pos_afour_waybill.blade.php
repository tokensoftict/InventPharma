<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Print Waybill #{{ $invoice->id }}</title>
    <style>
        html, body {
            margin: 0;
            padding: 0;
            font-size: 9pt;
            background-color: #fff;
        }

        #products {
            width: 90%;
        }
           #products th, #products td {
			padding-top:5px;
			padding-bottom:5px;
            border: 1px solid black;
        }
        #products tr td {
            font-size: 8pt;
        }

        #printbox {
            width: 98%;
            margin: 5pt;
            padding: 5px;
            margin: 0px auto;
            text-align: justify;
        }

        .inv_info tr td {
            padding-right: 10pt;
        }

        .product_row {
            margin: 15pt;
        }

        .stamp {
            margin: 5pt;
            padding: 3pt;
            border: 3pt solid #111;
            text-align: center;
            font-size: 20pt;
            color:#000;
        }

        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>

<table width="100%">
    <tr><td valign="top" width="65%">
            <h2 style="margin-top:0"  class="text-center">{{ $store->name}}</h2>
            <p align="center">
                {{ $store->first_address }}
                @if(!empty($store->second_address))
                    <br/>
                    {{ $store->second_address }}
                @endif
                @if(!empty($store->contact_number))
                    <br/>
                    {{ $store->contact_number }}
                @endif
            </p>
        </td>
    </tr>
    <td valign="top" width="35%">
        @if($store->logo != "1659902910.png")
            <img style="max-height:100px;float: right;" src="{{ public_path("logo/". $store->logo) }}" alt='Logo'>
        @endif
            @if($invoice->status_id === status('Complete'))
                <table class="inv_info" style="margin-top: 10px;">
                    @if($invoice->picked_by)
                        <tr>
                            <th  align="left">Picked By  </th><td> {{ ucwords($invoice->picked->name) }}</td>
                        </tr>
                    @endif
                    @if($invoice->packed_by)
                        <tr>
                            <th  align="left">Packed By  </th><td>{{  ucwords($invoice->packed->name) }}</td>
                        </tr>
                    @endif
                    @if($invoice->checked_by)
                        <tr>
                            <th  align="left">Checked By  </th><td>{{ ucwords($invoice->checked->name) }}</td>
                        </tr>
                    @endif

                    @if($invoice->carton_no)
                        <tr>
                            <th align="left">No. Of Cartons : </th><td>{{ $invoice->carton_no }}</td>
                        </tr>
                    @endif

                </table>
            @endif

    </td>
</table>
<div id="printbox">
    <table @if($invoice->picked_by) style="margin-top: -105px;" @else style="margin-top: -50px;" @endif class="inv_info">
    <tr>
            <th  align="left">Invoice Number</th>
            <td>{{ $invoice->invoice_number }}</td>
        </tr>
        <tr>
            <th align="left">Invoice Date</th>
            <td>{{ convert_date($invoice->invoice_date)  }}</td>
        </tr>
        <tr>
            <th align="left">Time</th>
            <td>{{ twelveHourClock($invoice->sales_time) }}</td>
        </tr>
        <tr>
            <th align="left">Customer</th>
            <td>{{ $invoice->customer->firstname }} {{ $invoice->customer->lastname }}</td>
        </tr>
        <tr>
            <th align="left">Department</th>
            <td>{{ \App\Classes\Settings::$department[$invoice->in_department] }}</td>
        </tr>
        <tr>
            <th align="left">PickUp Department</th>
            <td>{{  \App\Classes\Settings::$department[$invoice->department] }}</td>
        </tr>
        <tr>
            <th align="left">Sales Representative</th>
            <td>{{ $invoice->last_updated->name }}</td>
        </tr>

    </table>

    <h2 style="margin-top:0" class="text-center">INVOICE WAYBILL</h2>
    <table id="products">
        <tr class="product_row">
            <td>#</td>
            <td align="left"><b>Name</b></td>
            <td align="center"><b>Quantity</b></td>
            <td align="right"><b>Carton</b></td>
        </tr>
        <tbody id="appender">
        @foreach($invoice->invoiceitems as $item)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td align="left" class="text-left">{{ $item->stock->name }}</td>
                <td align="center" class="text-center">{{ $item->quantity }}</td>
                <td align="center" >
                    @php
                        $string = "";
                           if($item->stock->carton > 1){
                               $qty_by = $item->quantity;
                               if($qty_by < $item->stock->carton){
                                  $string .= $qty_by." pcs";
                               }else{
                                   $carton = floor($qty_by/ $item->stock->carton);
                                    $reminder = ($qty_by % $item->stock->carton);


                                    if($carton > 0){

                                       $string.=$carton." Carton";
                                    }

                                   if($carton > 0 && $reminder > 0){
                                       $string.=" and ".$reminder." pcs";
                                   }

                               }

                           }else{
                            $string .= $item->quantity." pcs";
                           }
                    @endphp
                    {{ $string }}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <div class="text-center">  {{ $store->footer_notes }}</div>
    <br/>
    <div align="center">
        <img src="data:image/png;base64,' . {{ DNS1D::getBarcodePNG((string)$invoice->id, 'C39',3,120) }} . '" alt="barcode"   />
    </div>
    <br/>
    <div class="text-center"> {!! softwareStampWithDate() !!}</div>
</div>
</body>
</html>

