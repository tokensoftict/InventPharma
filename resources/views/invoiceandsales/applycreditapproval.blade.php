@extends('layouts.app')

@section('pageHeaderTitle1', $title)
@section('pageHeaderDescription', $subtitle)

@section('css')

@endsection

@section('js')

@endsection

@section('content')
    <livewire:invoice-and-sales.credit.credit-payment-approval-dialog :invoice="$invoice"/>
@endsection
