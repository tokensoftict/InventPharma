<div>
    @php
        $grandTotal = 0;
    @endphp
    @foreach($payments as $payment)
        <h3>{{ $payment->name }}'S PAYMENT</h3>
        <div class="table-responsive">
    <table class="table table-bordered table-striped mt-5 payment">
        <thead>
            <tr>
                <th>No.</th>
                <th>Customer</th>
                <th>Type</th>
                <th>Invoice Number</th>
                <th>Amount</th>
                <th>Payment Method</th>
                <th>Payment Date</th>
                <th>Time</th>
                <th>By</th>
            </tr>
        </thead>
        <tbody>
        @php
            $total = 0;
        @endphp
            @foreach($payment->paymentmethoditems as $item)
                @php
                    $grandTotal+=$item->amount;
                     $total+=$item->amount;
                @endphp
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $item->customer->firstname }} {{ $item->customer->lastname }}</td>
                <td>{{ \App\Livewire\PaymentManager\Datatable\PaymentListDatatable::$invoiceType[$item->invoice_type] }}</td>
                <td>{{ $item->payment->invoice_number }}</td>
                <td>{{ money($item->amount) }}</td>
                <td>{{ $payment->name }}</td>
                <td>{{ eng_str_date($item->payment_date) }}</td>
                <td>{{ twelveHourClock($item->payment->payment_time) }}</td>
                <td>{{ $item->user->name }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
        <tr>
            <th></th>
            <th></th>
            <th></th>
            <th></th>
            <th>{{ money($total) }}</th>
            <th></th>
            <th></th>
            <th></th>
            <th></th>
        </tr>
        </tfoot>
    </table>
        </div>
    @endforeach

<br/><br/>
    <h2 class="float-end">Grand Total :{{ money($grandTotal) }}</h2>

</div>
