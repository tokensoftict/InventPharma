@extends('layouts.app')
@section('pageHeaderTitle1', $title)
@section('pageHeaderDescription', $subtitle)

@section('pageHeaderAction')
    @if(userCanView('purchasereturn.create') and $filters['status_id'] === status('Draft'))
    <div class="row">
        <div class="col-sm">
            <div class="mb-4">
                <a href="{{ route('purchasereturn.create') }}" type="button" class="btn btn-primary waves-effect waves-light">
                    <i class="bx bx-plus me-1"></i>
                    New Purchase Return
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
        <livewire:purchase-return.datatable.purchase-return-datatable :filters="$filters"/>
    </div>
@endsection
