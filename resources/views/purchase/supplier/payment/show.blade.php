@extends('layouts.app')

@section('pageHeaderTitle1', $title)
@section('pageHeaderDescription', $subtitle)

@section('content')
    <livewire:purchase-order.supplier.payment.supplier-payment-detail-component :payment="$payment" />
@endsection
