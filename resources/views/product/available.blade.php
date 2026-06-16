@extends('layouts.app')
@section('pageHeaderTitle1','Stock Availability List')
@section('pageHeaderDescription','Manage all Stock Availability')

@section('pageHeaderAction')
    @if(userCanView('product.create'))
        <div class="row">
            <div class="col-sm">
                <div class="mb-4">
                    <a href="{{ route('product.create') }}"  type="button" class="btn btn-primary waves-effect waves-light">
                        <i  class="bx bx-plus me-1"></i>
                        Add New Product
                    </a>
                </div>
            </div>
            <div class="col-sm-auto">

            </div>
        </div>
    @endif
@endsection

@section('content')
    @if(userCanView("product.stock_worth"))
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm border-start border-primary border-3">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted text-uppercase fw-semibold mb-2">Total Stock Worth</h6>
                            <h3 class="mb-0 text-primary fw-bold">&#8358;{{ money($stock_worth) }}</h3>
                        </div>
                        <div>
                            <span class="badge bg-soft-primary text-primary p-2 fs-4">
                                <i class="bx bx-money"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
    <div class="table-responsive">
        <livewire:product-module.datatable.product-component-datatable-available :filters="$filters" template="boostrap5"/>
    </div>
@endsection
