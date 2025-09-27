@extends('layouts.app')

@section('pageHeaderTitle1', $title)
@section('pageHeaderDescription', $subtitle)


@section('content')
    <div class="table-responsive">
    <livewire:payment-manager.datatable.payment-list-datatable :filters="$filters"/>
    </div>
@endsection
