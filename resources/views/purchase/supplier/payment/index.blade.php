@extends('layouts.app')
@section('pageHeaderTitle1',"Today's Supplier Payment")
@section('pageHeaderDescription',"Manage Payment Made to Supplier Today")

@section('pageHeaderAction')
    @if(route('supplier.payment.create'))
        <div class="row">
            <div class="col-sm">
                <div class="mb-4">
                    <a href="{{ route('supplier.payment.create') }}"  type="button" class="btn btn-primary waves-effect waves-light">
                        <i  class="bx bx-plus me-1"></i>
                        New Payment
                    </a>
                </div>
            </div>
            <div class="col-sm-auto">

            </div>
        </div>
    @endif
@endsection

@section('content')
    <div class="table-responsive">
        <livewire:purchase-order.supplier.payment.datatable.supplier-payment-datatable :filters="$filters"/>
    </div>
@endsection
