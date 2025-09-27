@extends('layouts.app')
@section('pageHeaderTitle1',"Today's Expenses List")
@section('pageHeaderDescription',"Manage all Today's Expenses List")

@section('pageHeaderAction')
    @if(userCanView('expenses.create'))
        <div class="row">
            <div class="col-sm">
                <div class="mb-4">
                    <a href="{{ route('expenses.create') }}"  type="button" class="btn btn-primary waves-effect waves-light">
                        <i  class="bx bx-plus me-1"></i>
                        Add New Expense
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
        <livewire:expenses.datatable.expenses-report-datatable :filters="$filters"/>
    </div>
@endsection
