@extends('layouts.app')

@section('pageHeaderTitle1', $title)
@section('pageHeaderDescription', $subtitle)

@section('css')
    <link rel="stylesheet" href="{{ asset('libs/flatpickr/flatpickr.min.css') }}"/>
    <link href="{{ asset('libs/choices.js/public/assets/styles/choices.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('js')
    <script src="{{ asset('libs/flatpickr/flatpickr.min.js') }}"></script>
    <script src="{{ asset('libs/choices.js/public/assets/scripts/choices.min.js') }}"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            flatpickr(".datepicker-basic", {
                dateFormat: "Y-m-d"
            });
        });

        function toggleDateFields() {
            var period = document.getElementById('filter-period').value;
            var fromContainer = document.getElementById('from-date-container');
            var toContainer = document.getElementById('to-date-container');
            if (period === 'custom') {
                fromContainer.style.display = 'block';
                toContainer.style.display = 'block';
            } else {
                fromContainer.style.display = 'none';
                toContainer.style.display = 'none';
            }
        }
    </script>
@endsection

@section('pageHeaderAction')
    <div>
        <form action="" method="post" class="border-bottom" id="filterForm">
            @csrf
            <div class="row align-items-end">
                <div class="col-auto">
                    <div class="mb-3">
                        <label class="form-label">Period</label>
                        <select class="form-control" name="filter[period]" id="filter-period" onchange="toggleDateFields()">
                            <option value="today" {{ ($filters['period'] ?? '') == 'today' ? 'selected' : '' }}>Today</option>
                            <option value="this_week" {{ ($filters['period'] ?? '') == 'this_week' ? 'selected' : '' }}>This Week</option>
                            <option value="this_month" {{ ($filters['period'] ?? '') == 'this_month' ? 'selected' : '' }}>This Month</option>
                            <option value="custom" {{ ($filters['period'] ?? '') == 'custom' ? 'selected' : '' }}>Custom Date</option>
                        </select>
                    </div>
                </div>

                <div class="col-auto" id="from-date-container" style="display: {{ ($filters['period'] ?? '') == 'custom' ? 'block' : 'none' }};">
                    <div class="mb-3">
                        <label class="form-label">From</label>
                        <input type="text" value="{{ $filters['from'] }}" class="form-control datepicker-basic" name="filter[from]">
                    </div>
                </div>

                <div class="col-auto" id="to-date-container" style="display: {{ ($filters['period'] ?? '') == 'custom' ? 'block' : 'none' }};">
                    <div class="mb-3">
                        <label class="form-label">To</label>
                        <input type="text" value="{{ $filters['to'] }}" class="form-control datepicker-basic" name="filter[to]">
                    </div>
                </div>

                <div class="col-auto">
                    <div class="mb-3">
                        <button type="submit" name="action" value="filter" class="btn btn-primary">Filter</button>
                        <button type="submit" name="action" value="export" class="btn btn-success"><i class="bx bx-file me-1"></i> Export to Excel</button>
                    </div>
                </div>
            </div>
        </form>
        <br/>
    </div>
@endsection

@section('content')
    <div class="row">
        <!-- Totals by Department Card -->
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header bg-transparent border-bottom">
                    <h5 class="my-0 text-primary"><i class="bx bx-pie-chart-alt-2 me-1"></i> Total Cheque Payments By Last Supplied Department</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @forelse($department_totals as $dept => $total)
                            <div class="col-md-3 col-sm-6 mb-3">
                                <div class="p-3 bg-light rounded text-center">
                                    <h6 class="text-muted text-uppercase mb-2">{{ $dept }}</h6>
                                    <h3 class="text-primary mb-0">{{ money($total) }}</h3>
                                </div>
                            </div>
                        @empty
                            <div class="col-12 text-center text-muted">
                                No department data available.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cheque Schedule Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-transparent border-bottom">
                    <h5 class="my-0 text-primary"><i class="bx bx-calendar me-1"></i> Cheque Payments List</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle dt-responsive nowrap w-100">
                            <thead>
                                <tr>
                                    <th>Supplier</th>
                                    <th>Amount</th>
                                    <th>Cheque Date</th>
                                    <th>Date Issued</th>
                                    <th>Payment Date</th>
                                    <th>Last Purchase Date</th>
                                    <th>Last Dept Supplied</th>
                                    <th>Remark</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($cheques as $item)
                                    @php
                                        $cheque = $item['cheque'];
                                        $status = $cheque->payment_info['status'] ?? 'Pending';
                                    @endphp
                                    <tr>
                                        <td><strong>{{ $cheque->supplier->name ?? 'N/A' }}</strong></td>
                                        <td><span class="text-primary font-weight-bold">{{ money($cheque->amount) }}</span></td>
                                        <td>{{ $cheque->payment_info['cheque_date'] ?? 'N/A' }}</td>
                                        <td>{{ $cheque->payment_info['date_of_issued'] ?? 'N/A' }}</td>
                                        <td>{{ eng_str_date($cheque->payment_date) }}</td>
                                        <td>{{ $item['last_purchase_date'] }}</td>
                                        <td><span class="badge badge-soft-primary">{{ $item['last_dept_name'] }}</span></td>
                                        <td>{{ $cheque->remark ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge badge-soft-{{ $status === 'Approved' ? 'success' : ($status === 'Declined' ? 'danger' : 'warning') }}">
                                                {{ $status }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-4 text-muted">
                                            No cheque payments found for the selected range.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
