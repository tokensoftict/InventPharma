@extends('layouts.app')

@section('pageHeaderTitle1', $title)
@section('pageHeaderDescription', $subtitle)

@section('css')
    <link rel="stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('libs/flatpickr/flatpickr.min.css') }}"/>
    <link href="{{ asset('libs/choices.js/public/assets/styles/choices.min.css') }}" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" type="text/css" href="{{ asset('DataTables/datatables.min.css') }}"/>
@endsection

@section('js')
    <script src="{{ asset('plugins/select2/js/select2.min.js') }}"></script>
    <script type="text/javascript" src="{{asset('DataTables/datatables.min.js') }}"></script>
    <script src="{{ asset('libs/flatpickr/flatpickr.min.js') }}"></script>
    <script src="{{ asset('libs/choices.js/public/assets/scripts/choices.min.js') }}"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            flatpickr(".datepicker-basic", {  });

            var e = document.querySelectorAll("[data-trigger]");
            for (i = 0; i < e.length; ++i) {
                var a = e[i];
                new Choices(a, { placeholderValue: "This is a placeholder set in the config", searchPlaceholderValue: "This is a search placeholder" });
            }
        });

        $(function () {
            var table =  $('#invoice-list').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'excel', 'pdf'
                ],

            });


        });

    </script>

@endsection

@section('pageHeaderAction')

    <x-report-filter-component :filters="$filters"/>

@endsection

@section('content')

    <div class="table-responsive">
        <table id="invoice-list" class="table table-bordered table-responsive table table-striped">
            <thead>
            <tr>
                <th>#</th>
                <th>Customer</th>
                <th>Phone number</th>
                <th>Invoice Count</th>
                <th>Total Paid</th>
                <th>Action</th>
            </tr>
            </thead>
            <body>
                @foreach($reports as $report)
                    <tr>
                        <th>{{ $loop->iteration }}</th>
                        <td>{{ $report->customer->firstname }} {{ $report->customer->lastname }}</td>
                        <td>{{ $report->customer->phone_number }}</td>
                        <td>{{ $report->invoice_count }}</td>
                        <td>{{ money($report->total_invoice_amount) }}</td>
                        <td>
                            <a href="{{ route('reports.invoice.by_date') . "?from=".$filters['filters']['between.invoice_date'][0]."&to=".$filters['filters']['between.invoice_date'][1]."&customer=".$report->customer->id."&department=".$filters['filters']['custom_dropdown_id'] }}" class="btn btn-sm btn-primary">View Invoice</a>
                        </td>
                    </tr>
                @endforeach
            </body>
        </table>
    </div>
@endsection
