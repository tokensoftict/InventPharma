@extends('layouts.app')

@section('pageHeaderTitle1', 'View Details')
@section('pageHeaderDescription', 'View Invoice Details')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}">
@endsection

@section('js')

    <script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('plugins/select2/js/select2.min.js') }}"></script>
@endsection

@section('content')

    <livewire:invoice-and-sales.show.show-invoice-component :invoice="$invoice"/>

    @if($invoice->status_id === status('Waiting-For-Credit-Approval'))
        <livewire:invoice-and-sales.credit.credit-payment-approval-dialog  mode="approveDecline" :invoice="$invoice"/>
    @endif

    @if($invoice->status_id === status('Waiting-For-Cheque-Approval'))
        <livewire:invoice-and-sales.cheque.cheque-payment-approval-dialog  mode="approveDecline" :invoice="$invoice"/>
    @endif

    @if($invoice->status_id ===  status('Paid') || $invoice->status_id === status('Dispatched') || $invoice->status_id == status("Complete"))
        <x-show-payment-component :payment="$invoice->payment"/>
    @endif

    @if($invoice->status_id === status('Draft') || $invoice->status_id === status('Packed-Waiting-For-Payment'))
        <x-create-payment-component :invoice="$invoice"/>
    @endif

    @if($invoice->status_id === status('Paid'))
        <x-dispatch-invoice-component :invoice="$invoice"/>
    @endif

@endsection
