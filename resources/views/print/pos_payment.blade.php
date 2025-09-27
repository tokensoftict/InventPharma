<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Print Receipt #{{ $payment->id }}</title>
    <style>
        html, body {
            margin: 0;
            padding: 0;
            font-size: 9pt;
            background-color: #fff;
        }

        #products {
            width: 100%;
        }

        #products tr td {
            font-size: 8pt;
        }

        #printbox {
            width: 80mm;
            margin: 5pt;
            padding: 5px;
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
@if($store->logo != "1659902910.png")
    <h3 id="logo" class="text-center"><br><img style="max-height:30px;" src="{{ public_path("logo/". $store->logo) }}" alt='Logo'></h3>
@endif
<div id="printbox">
    <h2 style="margin-top:0" class="text-center">{{ $store->name}}</h2>
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
    <table class="inv_info">
        <tr>
            <td>Reference</td>
            <td>{{ $payment->invoice_number }}</td>
        </tr>
        <tr>
            <td>Payment Date</td>
            <td>{{ convert_date($payment->payment_date)  }}</td>
        </tr>
        <tr>
            <td>Time</td>
            <td>{{ twelveHourClock($payment->payment_time) }}</td>
        </tr>
        <tr>
            <td>Customer</td>

            <td>{{ $payment->customer->firstname.' '.$payment->customer->lastname }}</td>
        </tr>
        <tr>
            <td>Cashier</td>
            <td>{{ $payment->user->name }}</td>
        </tr>
    </table>
    <hr>
    <h2 align="center">Payment Receipt</h2>
    <hr>
    <table id="products">
        <tr class="product_row">
            <td align="left"><b>Invoice Number</b></td>
            <td align="right">{{ $payment->invoice_number }}</td>
        </tr>
        <tr class="product_row">
            <td align="left"><b>Amount</b></td>
            <td align="right">{{ number_format($payment->total_paid,2) }}</td>
        </tr>
    </table>
    @php
    $total = 0;
    @endphp
    <h3 align="center">Payment Method(s)</h3>
    <table id="products">
        @foreach($payment->paymentmethoditems as $pm)
            <tr class="product_row">
                <td align="left"><b>{{ $pm->paymentmethod->name }}</b></td>
                <td align="right">{{ number_format($pm->amount,2) }}</td>
            </tr>
            @php
            $total = $total + $pm->amount;
            @endphp
        @endforeach
        <tfoot>
            <tr>
                <th align="right"></th>
                <th align="right">Total : {{ number_format($total,2) }}</th>
            </tr>
        </tfoot>
    </table>
    <hr>
    <div class="text-center">  {{ $store->footer_notes }}</div>
    <div class="text-center"> {!! softwareStampWithDate() !!}</div>
    <div class="text-center"> Develop By Tokensoft ICT - 08130610626</div>
</div>
</body>
</html>

