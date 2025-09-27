<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Print Invoice #{{ $invoice->id }}</title>
    <style>
        html, body {
            margin: 0;
            padding: 0;
            font-size: 8pt;
            background-color: #fff;
        }

        #products {
            width: 100%;
        }

        #products tr td {
            font-size: 7pt;
        }

        #printbox {
            width: 80mm;
            margin: 5pt;
            padding: 5px;
            text-align: justify;
        }

        .inv_info tr td {
            padding-right: 14pt;
        }

        .product_row {
            margin: 13pt;
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
@if($store->logo != "1659902910.png")
<h3 id="logo" class="text-center"><br><img style="max-height:30px;" src="{{ public_path("logo/". $store->logo) }}" alt='Logo'></h3>
@endif
<div id="printbox">
    <h2 style="margin:-2px;padding: 0px" class="text-center">{{ $store->name}}</h2>
    <div align="center" >
        {{ $store->first_address }}
        @if(!empty($store->second_address))
            <br/>
            {{ $store->second_address }}
        @endif
        @if(!empty($store->contact_number))
            <br/>
            {{ $store->contact_number }}
        @endif
    </div>
    <table class="inv_info">
        <tr>
            <th  align="left">Reference Number</th>
            <td>{{ $invoice->invoice_number }}</td>
        </tr>
        <tr>
            <th  align="left">Invoice Date</th>
            <td>{{ convert_date($invoice->invoice_date)  }} {{ twelveHourClock($invoice->sales_time) }}</td>
        </tr>
        <tr>
            <th align="left">Customer</th>
            <td>{{ $invoice->customer->firstname }} {{ $invoice->customer->lastname }}</td>
        </tr>
        <tr>
            <th align="left">Sales Rep.</th>
            <td>{{ $invoice->last_updated->name }}</td>
        </tr>
        <tr>
            <th  align="left">Invoice Status</th>
            <td>{{ $invoice->status->name }}</td>
        </tr>
    </table>
    <hr>
    <table id="products">
        <tr class="product_row">
            <td>#</td>
            <td align="left"><b>Name</b></td>
            <td align="center"><b>Qty</b></td>
            <td align="center"><b>Rate</b></td>
            <td align="right"><b>Dis. Rate</b></td>
            <td align="right"><b>Total</b></td>
        </tr>
        <tr>
            <td colspan="6">
                <hr>
            </td>
        </tr>
        <tbody id="appender">
        @foreach($invoice->invoiceitems as $item)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td align="left" class="text-left">{{ $item->stock->name }}</td>
                <td align="center" class="text-center">{{ $item->quantity }}</td>
                <td align="center" class="text-center">{{ money($item->selling_price) }}</td>
                <td align="right" class="text-right">{{ money(($item->selling_price-$item->discount_amount)) }}</td>
                <td align="right" class="text-right">{{ money(($item->quantity * ($item->selling_price - $item->discount_amount))) }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
        @if($invoice->onlineordertotals()->exists())
            @foreach($invoice->onlineordertotals as $ordertotal)
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td align="right">{{ str_replace('Subtotal','Sub total',$ordertotal['name']) }}</td>
                    <td align="right">{{ number_format($ordertotal->value,2) }}</td>
                </tr>
            @endforeach
        @else
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td align="right">Sub Total</td>
                <td align="right">{{ number_format($invoice->sub_total,2) }}</td>
            </tr>
        @endif
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td  align="right" class="text-right">Total</td>
            <td  align="right" class="text-right"><b>{{ number_format(($invoice->sub_total),2) }}</b></td>
        </tr>
        </tfoot>
    </table>
    <hr>
    <div class="text-center">  {{ $store->footer_notes }}</div>
    <div class="text-center"> {!! softwareStampWithDate() !!}</div>
    <br/>
    <div align="center" style="width: 100%;">
        <img src="data:image/png;base64,' . {{ DNS1D::getBarcodePNG((string)$invoice->id, 'C39',3,120) }} . '" alt="barcode"   />
    </div>
    <br/>
    <div class="text-center"> Develop By Tokensoft ICT - 08130610626</div>
</div>
</body>
</html>

