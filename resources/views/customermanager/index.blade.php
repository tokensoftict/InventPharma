@extends('layouts.app')

@section('content')
    <livewire:customer.customer-manager-component :filters="$filters"/>
@endsection
