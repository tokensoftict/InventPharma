@extends('layouts.app')
@section('pageHeaderTitle1','Expired Stock List ('.app(\App\Classes\Settings::class)->store()->near_expiry_days.') Days')
@section('pageHeaderDescription','Expired Stock List ('.app(\App\Classes\Settings::class)->store()->near_expiry_days.') Days')



@section('content')
    <div class="table-responsive">
        <livewire:product-module.expired.expired-stock-list/>
    </div>
@endsection
