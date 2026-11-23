@extends('layouts.app')

@section('pageHeaderTitle1', $title)
@section('pageHeaderDescription', $subtitle)

@section('css')
    <link rel="stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('libs/flatpickr/flatpickr.min.css') }}"/>
    <link href="{{ asset('libs/choices.js/public/assets/styles/choices.min.css') }}" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" type="text/css" href="{{ asset('DataTables/datatables.min.css') }}"/>
    <style>
        .sas-summary-card {
            border: 1px solid #e3e7ed;
            border-radius: 8px;
            padding: 10px 14px;
            background: #f8f9fb;
        }
        .sas-table th {
            white-space: nowrap;
            font-size: 11.5px !important;
            background: #f4f6f9;
        }
        .sas-table td {
            vertical-align: middle;
        }
        @media print {
            @page {
                size: A4 landscape;
                margin: 8mm;
            }
            .dt-buttons, .dataTables_filter, .dataTables_length, .dataTables_paginate, .dataTables_info, .no-print {
                display: none !important;
            }
            .sas-table th, .sas-table td {
                padding: 3px 5px !important;
                font-size: 8.5pt !important;
            }
        }
    </style>
@endsection

@section('js')
    <script src="{{ asset('plugins/select2/js/select2.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('DataTables/datatables.min.js') }}"></script>
    <script src="{{ asset('libs/flatpickr/flatpickr.min.js') }}"></script>
    <script src="{{ asset('libs/choices.js/public/assets/scripts/choices.min.js') }}"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            flatpickr(".datepicker-basic", {});
            var e = document.querySelectorAll("[data-trigger]");
            for (var i = 0; i < e.length; ++i) {
                new Choices(e[i], {
                    placeholderValue: "Select an option",
                    searchPlaceholderValue: "Type to search..."
                });
            }
        });

        $(function () {
            $('#supplier-statement-table').DataTable({
                dom: 'Bfrtip',
                ordering: false,
                pageLength: 50,
                buttons: [
                    'copy',
                    {
                        extend: 'excelHtml5',
                        title: 'Supplier Account Statement - {{ addslashes($supplier->name ?? "Supplier") }} ({{ $filters["from"] }} to {{ $filters["to"] }})',
                        footer: true
                    },
                    {
                        extend: 'pdfHtml5',
                        orientation: 'landscape',
                        pageSize: 'A4',
                        title: 'Supplier Account Statement - {{ addslashes($supplier->name ?? "Supplier") }} ({{ $filters["from"] }} to {{ $filters["to"] }})',
                        footer: true
                    },
                    {
                        extend: 'print',
                        title: 'Supplier Account Statement - {{ addslashes($supplier->name ?? "Supplier") }} ({{ $filters["from"] }} to {{ $filters["to"] }})',
                        footer: true
                    }
                ]
            });
        });
    </script>
@endsection

@section('pageHeaderAction')
    <div class="no-print">
        <x-report-filter-component :filters="$filters"/>
    </div>
@endsection

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h5 class="mb-0 fw-bold text-dark">
                {{ $supplier->name ?? 'Selected Supplier' }}
            </h5>
            <small class="text-muted">
                Statement Period: <strong>{{ eng_str_date($filters['from']) }}</strong> to <strong>{{ eng_str_date($filters['to']) }}</strong>
            </small>
        </div>
        <div class="no-print">
            <button type="button" onclick="window.print()" class="btn btn-sm btn-outline-secondary">
                <i class="fa fa-print me-1"></i> Print Statement
            </button>
        </div>
    </div>

    {{-- Summary Strip --}}
    <div class="row g-2 mb-3">
        <div class="col-md col-6">
            <div class="sas-summary-card">
                <small class="text-muted d-block">Opening Balance</small>
                <strong class="fs-6 {{ $opening < 0 ? 'text-danger' : 'text-success' }}">
                    {{ number_format($opening, 2) }}
                </strong>
            </div>
        </div>
        <div class="col-md col-6">
            <div class="sas-summary-card">
                <small class="text-muted d-block">Supplied Total</small>
                <strong class="fs-6 text-danger">{{ number_format($totalSupplied, 2) }}</strong>
            </div>
        </div>
        <div class="col-md col-6">
            <div class="sas-summary-card">
                <small class="text-muted d-block">Total Payments</small>
                <strong class="fs-6 text-success">{{ number_format($totalPayments, 2) }}</strong>
            </div>
        </div>
        <div class="col-md col-6">
            <div class="sas-summary-card">
                <small class="text-muted d-block">Total Worth of Stocks Return</small>
                <strong class="fs-6 text-primary">{{ number_format($totalReturns, 2) }}</strong>
            </div>
        </div>
        <div class="col-md col-12">
            <div class="sas-summary-card border-primary bg-primary-subtle">
                <small class="text-primary d-block">Closing Balance</small>
                <strong class="fs-6 {{ $closingBalance < 0 ? 'text-danger' : 'text-success' }}">
                    {{ number_format($closingBalance, 2) }}
                </strong>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table id="supplier-statement-table" class="table table-bordered table-striped table-hover sas-table">
            <thead>
            <tr>
                <th class="text-center">#</th>
                <th class="text-end">Supplied Total</th>
                <th class="text-end">Payments</th>
                <th>Payment Method</th>
                <th class="text-nowrap">Payment Date</th>
                <th>Description</th>
                <th>Account Name (Bank Name)</th>
                <th>Cash Receiver</th>
                <th class="text-end">Total Worth of Stocks Return</th>
                <th class="text-end">Balance</th>
            </tr>
            </thead>
            <tbody>
            @foreach($rows as $row)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td class="text-end text-danger fw-medium">
                        {{ $row['supplied_total'] !== null ? number_format($row['supplied_total'], 2) : '' }}
                    </td>
                    <td class="text-end text-success fw-medium">
                        {{ $row['payment_amount'] !== null ? number_format($row['payment_amount'], 2) : '' }}
                    </td>
                    <td class="text-nowrap">
                        @if($row['kind'] === 'return')
                            <span class="badge bg-info-subtle text-info border border-info-subtle">{{ $row['payment_method'] }}</span>
                        @elseif($row['kind'] === 'supply')
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">{{ $row['payment_method'] }}</span>
                        @else
                            <span class="badge bg-success-subtle text-success border border-success-subtle">{{ $row['payment_method'] }}</span>
                        @endif
                    </td>
                    <td class="text-nowrap">{{ eng_str_date($row['payment_date']) }}</td>
                    <td>
                        @if($row['link_url'])
                            <a href="{{ $row['link_url'] }}" target="_blank" class="fw-medium text-primary">
                                {{ $row['description'] }}
                            </a>
                        @else
                            {{ $row['description'] }}
                        @endif
                    </td>
                    <td>{{ $row['account_bank'] }}</td>
                    <td>{{ $row['cash_receiver'] }}</td>
                    <td class="text-end text-primary fw-medium">
                        {{ $row['return_worth'] !== null ? number_format($row['return_worth'], 2) : '' }}
                    </td>
                    <td class="text-end fw-semibold {{ $row['balance'] < 0 ? 'text-danger' : 'text-success' }}">
                        {{ number_format($row['balance'], 2) }}
                    </td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
            <tr>
                <th>Totals</th>
                <th class="text-end text-danger">{{ number_format($totalSupplied, 2) }}</th>
                <th class="text-end text-success">{{ number_format($totalPayments, 2) }}</th>
                <th></th>
                <th></th>
                <th>Closing Balance (Opening {{ number_format($opening, 2) }})</th>
                <th></th>
                <th></th>
                <th class="text-end text-primary">{{ number_format($totalReturns, 2) }}</th>
                <th class="text-end {{ $closingBalance < 0 ? 'text-danger' : 'text-success' }}">
                    {{ number_format($closingBalance, 2) }}
                </th>
            </tr>
            </tfoot>
        </table>
    </div>
@endsection
