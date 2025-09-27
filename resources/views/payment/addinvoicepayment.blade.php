@extends('layouts.app')

@section('pageHeaderTitle1', $title)
@section('pageHeaderDescription', $subtitle)

@section('content')
   <livewire:payment-manager.addpayment-component :invoice="$invoice"/>
@endsection
