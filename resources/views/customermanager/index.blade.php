@extends('layouts.app')

@section('content')
    <div class="table-responsive">
        <livewire:customer.customer-manager-component :filters="$filters"/>
    </div>
@endsection
