@extends('layouts.app')
@section('pageHeaderTitle1','Nearly Expired Stock List ('.app(\App\Classes\Settings::class)->store()->near_expiry_days.') Days')
@section('pageHeaderDescription','Nearly Expired Stock List ('.app(\App\Classes\Settings::class)->store()->near_expiry_days.') Days')



@section('content')
    <div class="table-responsive">
    <livewire:product-module.expired.near-expiration-stock-list/>
    </div>
@endsection
