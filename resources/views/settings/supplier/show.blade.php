@extends('layouts.app')

@section('pageHeaderTitle1', $title)
@section('pageHeaderDescription', $subtitle)

@section('content')
    <div class="row">
        <!-- Supplier Information Card -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-transparent border-bottom">
                    <h5 class="my-0 text-primary"><i class="bx bx-user me-1"></i> Supplier Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tbody>
                            <tr>
                                <th scope="row" style="width: 30%;">Name :</th>
                                <td>{{ $supplier->name }}</td>
                            </tr>
                            <tr>
                                <th scope="row">Email :</th>
                                <td>{{ $supplier->email ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th scope="row">Phone :</th>
                                <td>{{ $supplier->phonenumber ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th scope="row">Address :</th>
                                <td>{{ $supplier->address ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th scope="row">Status :</th>
                                <td>
                                    <span class="badge badge-soft-{{ $supplier->status ? 'success' : 'danger' }}">
                                        {{ $supplier->status ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Supplier Credit Balance Card -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-transparent border-bottom">
                    <h5 class="my-0 text-primary"><i class="bx bx-wallet me-1"></i> Credit Summary</h5>
                </div>
                <div class="card-body d-flex flex-column justify-content-center align-items-center py-4">
                    <h6 class="text-muted text-uppercase mb-2">Credit Balance</h6>
                    <h2 class="text-danger mb-0">{{ money($supplier->credit_balance) }}</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment History and Approvals -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-transparent border-bottom">
                    <h5 class="my-0 text-primary"><i class="bx bx-list-ul me-1"></i> Payment History & Approvals</h5>
                </div>
                <div class="card-body">
                    <livewire:purchase-order.supplier.payment.datatable.supplier-payment-datatable :filters="['supplier_id' => $supplier->id]"/>
                </div>
            </div>
        </div>
    </div>
@endsection
