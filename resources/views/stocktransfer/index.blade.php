@extends('layouts.app')
@section('pageHeaderTitle1', $title)
@section('pageHeaderDescription', $subtitle)

@section('pageHeaderAction')
    @if(userCanView('transfer.create'))
    <div class="row">
        <div class="col-sm">
            <div class="mb-4">
                <a href="{{ route('transfer.create') }}"  type="button" class="btn btn-primary waves-effect waves-light">
                    <i  class="bx bx-plus me-1"></i>
                    New Transfer
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
    <livewire:stock-transfer.datatable.stock-transfer-datatable :filters="$filters"/>
    </div>

@endsection
