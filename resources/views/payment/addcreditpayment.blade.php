@extends('layouts.app')

@section('pageHeaderTitle1', $title)
@section('pageHeaderDescription', $subtitle)

@section('content')
    <livewire:payment-manager.addcredit-component  :amount="$amount" :customer="$customer_id"/>
@endsection
