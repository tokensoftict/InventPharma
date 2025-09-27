<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Deposit Receipt</title>
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
<div id="printbox">
    <table width="65%">
        <tr><td valign="top">
                <h2  class="text-center">{{ $store->name}}</h2>
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
            <img style="max-height:100px;float: right;" src="{{ public_path("img/". $store->logo) }}" alt='Logo'>
        </td>
    </table>


    <table  class="inv_info">

        <tr>
            <td>Deposit Number</td>
            <td>{{ $deposit->deposit_number }}</td>
        </tr>
        <tr>
            <td>Deposit Date</td>
            <td>{{ convert_date($deposit->deposit_date)  }}</td>
        </tr>
        <tr>
            <td>Time</td>
            <td>{{ date("h:i a",strtotime($deposit->deposit_time)) }}</td>
        </tr>
        <tr>
            <td>Customer</td>
            <td>{{ $deposit->customer->firstname }} {{ $deposit->customer->lastname }}</td>
        </tr>
        <tr>        <tr>
            <td>Mode of Payment</td>
            <td>
                @if($deposit->paymentMethodTable->count() > 1)
                    @php
                        $methods = [];
                    @endphp

                    @foreach($deposit->paymentMethodTable as $meth)
                        @php
                            echo  $meth->payment_method->name." : ". number_format( $meth->amount,2)."<br/>";
                        @endphp
                    @endforeach


                @else
                    {{ $deposit->paymentMethodTable->first()->payment_method->name  }} : {{ number_format($deposit->paymentMethodTable->first()->amount,2) }}
                @endif
            </td>
        </tr>
    </table>


    <h2 style="margin-top:0" class="text-center">Payment Deposit Receipt</h2>

    <table id="products">
        <tr class="product_row">
            <td align="center"><b>Description</b></td>
            <td align="center"><b>Amount Deposited</b></td>
        </tr>
        <tbody id="appender">
            <tr>
                <td align="center" class="text-left">{{ $deposit->description }}</td>
                <td align="right" class="text-right">{{ number_format($deposit->amount,2) }}</td>
            </tr>
        </tbody>
        <tfoot>
        <tr>
            <td  align="right" class="text-right">Total Deposited</td>
            <td  align="right" class="text-right">{{ number_format($deposit->customer->customerDepositsHistory()->sum('amount'),2) }}</td>
        </tr>
        <tr>
            <td   align="right" class="text-right">Total Used</td>
            <td  align="right" class="text-right"><b>{{ number_format($deposit->customer->payment_method_tables()->where('payment_method_id',5)->sum('amount'),2) }}</b></td>
        </tr>
        <tr>
            <td   align="right" class="text-right"><b>Balance</b></td>
            <td  align="right" class="text-right"><b>{{ number_format($deposit->customer->deposit_balance,2) }}</b></td>
        </tr>
        </tfoot>
    </table>

    <div class="text-center">  {{ $store->footer_notes }}</div>
    <br/>
    <div align="center">

    </div>
    <br/>
    <div class="text-center"> {!! softwareStampWithDate() !!}</div>
</div>
</body>
</html>

