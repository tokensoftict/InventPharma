@extends('layouts.app')

@section('pageHeaderTitle1', 'Product Discount')
@section('pageHeaderDescription', 'Apply Product Discount')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/dataTables.bootstrap4.min.css') }}">
@endsection

@section('js')

    <script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('js/dataTables.bootstrap4.min.js') }}"></script>

@endsection

@section('content')
    <div class="table-responsive">
        <livewire:invoice-and-sales.discount.apply-product-discount-component :invoice="$invoice"/>
    </div>
@endsection
