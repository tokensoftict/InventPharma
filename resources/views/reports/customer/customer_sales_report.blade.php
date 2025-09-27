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
    <form action="" method="post" class="border-bottom">
        @csrf
        <div class="row">
            <div class="col-auto">
                <div class="mb-3">
                    <label class="form-label">Start From</label>
                    <input type="text" value="{{ $filters['start_from'] }}" class="form-control datepicker-basic" name="start_from" id="datepicker-basic">
                </div>
            </div>
            <div class="col-auto">
                <div class="mb-3">
                    <label class="form-label">Start To</label>
                    <input type="text" value="{{ $filters['start_to'] }}" class="form-control datepicker-basic" name="start_to" id="datepicker-basic">
                </div>
            </div>

            <div class="col-auto">
                <h4 class="mt-4">To</h4>
            </div>

            <div class="col-auto">
                <div class="mb-3">
                    <label class="form-label">End From</label>
                    <input type="text" value="{{ $filters['end_from'] }}" class="form-control datepicker-basic" name="end_from" id="datepicker-basic">
                </div>
            </div>
            <div class="col-auto">
                <div class="mb-3">
                    <label class="form-label">End To</label>
                    <input type="text" value="{{ $filters['end_to'] }}" class="form-control datepicker-basic" name="end_to" id="datepicker-basic">
                </div>
            </div>

            <div class="col-auto">
                <button type="submit" class="btn btn-primary mt-4">Filter</button>
            </div>
        </div>
    </form>
@endsection

@section('content')
    <div class="table-responsive">
        <table id="invoice-list" class="table table-bordered table-responsive table table-striped">
            <thead>
            <tr>
                <th>#</th>
                <th>Customer</th>
                <th>Phone Number</th>
                <th>Total Sum Of Invoice ( {{ $filters['start_from'] }} - {{ $filters['start_to'] }})</th>
                <th>Total Sum Of Invoice ( {{ $filters['end_from'] }} - {{ $filters['end_from'] }})</th>
                <th>Total Difference</th>
            </tr>
            </thead>
            <tbody>
            @foreach($reports as $report)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $report->customer_name }}</td>
                    <td>{{ $report->phone_number }}</td>
                    <td>{{ money($report->start_invoice_amount) }}</td>
                    <td>{{ money($report->end_invoice_amount) }}</td>
                    <td>{{ money($report->difference) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection