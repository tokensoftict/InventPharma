@extends('layouts.app')

@section('pageHeaderTitle1', $title.' Product')
@section('pageHeaderDescription', $subtitle.' Product')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/pikaday.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}">
@endsection

@section('js')
    <script src="{{ asset('plugins/select2/js/select2.min.js') }}"></script>

    <script src="{{ asset('js/moment.min.js') }}"></script>
    <script src="{{ asset('js/pikaday.js') }}"></script>
    <script src="{{ asset('js/barcode.js') }}"></script>
@endsection

@section('content')

    <livewire:product-module.product-component :product="$product"/>
@endsection
