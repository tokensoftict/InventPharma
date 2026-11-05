@extends('layouts.app')

@section('pageHeaderTitle1', $title)
@section('pageHeaderDescription', $subtitle)

@section('css')
    <link rel="stylesheet" href="{{ asset('libs/flatpickr/flatpickr.min.css') }}"/>
    <link href="{{ asset('libs/choices.js/public/assets/styles/choices.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('js')
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
    </script>
@endsection

@section('pageHeaderAction')
    <x-report-filter-component :filters="$filters"/>
@endsection

@section('content')
    @php
        $summaryQuery = \App\Models\OpayPaymentLog::query()->filterdata($filters['filters']);
        $approvedTotal = (clone $summaryQuery)->where('status', 'success')->sum('amount');
        $approvedCount = (clone $summaryQuery)->where('status', 'success')->count();
        $failedCount = (clone $summaryQuery)->where('status', '!=', 'success')->count();
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <small class="text-muted text-uppercase fw-semibold">Approved OPay Amount</small>
                    <h3 class="fw-bold text-success mb-0 mt-1">{{ money($approvedTotal) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <small class="text-muted text-uppercase fw-semibold">Approved Transactions</small>
                    <h3 class="fw-bold text-primary mb-0 mt-1">{{ number_format($approvedCount) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <small class="text-muted text-uppercase fw-semibold">Cancelled / Failed Attempts</small>
                    <h3 class="fw-bold text-danger mb-0 mt-1">{{ number_format($failedCount) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <livewire:payment-manager.datatable.opay-payment-logs-datatable :filters="$filters['filters']"/>
    </div>
@endsection

