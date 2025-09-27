@extends('layouts.app')
@section('pageHeaderTitle1',"Today's Supplier Credit List")
@section('pageHeaderDescription',"Manage all Supplier Credit List")

@section('pageHeaderAction')

@endsection

@section('content')
    <div class="table-responsive">
        <livewire:purchase-order.supplier.payment.datatable.supplier-payment-datatable :filters="$filters"/>
    </div>
@endsection
