@extends('layouts.app')

@section('pageHeaderTitle1', 'Valued Date Cheque Report')
@section('pageHeaderDescription', 'Supplier Cheque Payments Sorted by Valued Date')

@section('css')
    <link rel="stylesheet" href="{{ asset('libs/flatpickr/flatpickr.min.css') }}"/>
@endsection

@section('js')
    <script src="{{ asset('libs/flatpickr/flatpickr.min.js') }}"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            flatpickr(".datepicker-basic", {
                dateFormat: "Y-m-d"
            });
        });
        document.addEventListener("livewire:navigated", function () {
            flatpickr(".datepicker-basic", {
                dateFormat: "Y-m-d"
            });
        });
    </script>
@endsection

@section('content')
    <livewire:purchase-order.supplier.payment.valued-date-cheque-report />
@endsection
