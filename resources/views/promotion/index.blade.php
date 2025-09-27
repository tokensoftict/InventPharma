@extends('layouts.app')
@section('pageHeaderTitle1','Promotion List(s)')
@section('pageHeaderDescription','List of all running Promotion')

@section('pageHeaderAction')
    @if(userCanView('product.create'))
        <div class="row">
            <div class="col-sm">
                <div class="mb-4">
                    <a href="{{ route('promo.create') }}"  type="button" class="btn btn-primary waves-effect waves-light">
                        <i  class="bx bx-plus me-1"></i>
                        Add New Promotion
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
        <livewire:promotion.datatable.promotion-data-list :filters="[]"/>
    </div>
@endsection
