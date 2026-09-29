@extends('layouts.app')

@section('content')
    <div class="table-responsive">
        <livewire:purchase-limit.customer-usage-component :customer="$customer" />
    </div>
@endsection
