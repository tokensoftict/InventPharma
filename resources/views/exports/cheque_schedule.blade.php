<table>
    <thead>
        <tr>
            <th colspan="9" style="font-weight: bold; text-align: center; font-size: 16px;">Supplier Payment Cheque Schedule</th>
        </tr>
        <tr>
            <th colspan="9" style="font-weight: bold; text-align: center; font-size: 12px;">Total Cheque Payments By Last Supplied Department</th>
        </tr>
        @foreach($department_totals as $dept => $total)
            <tr>
                <th colspan="2" style="font-weight: bold;">{{ $dept }}</th>
                <td colspan="7">{{ $total }}</td>
            </tr>
        @endforeach
        <tr>
            <th colspan="9"></th>
        </tr>
        <tr style="background-color: #f2f2f2; font-weight: bold;">
            <th>Supplier</th>
            <th>Amount</th>
            <th>Cheque Date</th>
            <th>Date Issued</th>
            <th>Payment Date</th>
            <th>Last Purchase Date</th>
            <th>Last Dept Supplied</th>
            <th>Remark</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($cheques as $item)
            @php
                $cheque = $item['cheque'];
            @endphp
            <tr>
                <td>{{ $cheque->supplier->name ?? 'N/A' }}</td>
                <td>{{ $cheque->amount }}</td>
                <td>{{ $cheque->payment_info['cheque_date'] ?? 'N/A' }}</td>
                <td>{{ $cheque->payment_info['date_of_issued'] ?? 'N/A' }}</td>
                <td>{{ $cheque->payment_date->format('Y-m-d') }}</td>
                <td>{{ $item['last_purchase_date'] }}</td>
                <td>{{ $item['last_dept_name'] }}</td>
                <td>{{ $cheque->remark ?? 'N/A' }}</td>
                <td>{{ $cheque->payment_info['status'] ?? 'Pending' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
