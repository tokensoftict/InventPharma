<div>
    <div class="card">
        <div class="card-header bg-transparent border-bottom">
            <h4 class="card-title text-primary"><i class="bx bx-file me-1"></i> Supplier Cheque Payment Report</h4>
            <p class="card-title-desc">Filter, analyze, and manage supplier cheque payments.</p>
        </div>
        <div class="card-body">
            <div class="row mb-4 align-items-end">
                <div class="col-md-2">
                    <label class="form-label font-weight-bold">Filter By Date</label>
                    <select class="form-select" wire:model.live="date_filter">
                        <option value="Today">Today</option>
                        <option value="This Week">This Week</option>
                        <option value="This Month">This Month</option>
                        <option value="Custom">Custom Date Range</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label font-weight-bold">Filter By Last Dept Supplied</label>
                    <select class="form-select" wire:model.live="last_dept_supplied">
                        <option value="">All Departments</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->quantity_column }}">{{ $dept->label ?? $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                @if($date_filter === 'Custom')
                    <div class="col-md-2">
                        <label class="form-label">Start Date</label>
                        <input type="text" class="form-control datepicker-basic" wire:model.live="start_date"
                            placeholder="YYYY-MM-DD">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">End Date</label>
                        <input type="text" class="form-control datepicker-basic" wire:model.live="end_date"
                            placeholder="YYYY-MM-DD">
                    </div>
                @endif
                <div class="col-md-4 ms-auto">
                    <div class="row text-center g-2">
                        <div class="col">
                            <div class="p-2 border rounded bg-light">
                                <h6 class="text-muted mb-1 font-size-12">Approved Total</h6>
                                <h4 class="text-success mb-0 fw-bold">{{ money($total_approved) }}</h4>
                            </div>
                        </div>
                        <div class="col">
                            <div class="p-2 border rounded bg-light">
                                <h6 class="text-muted mb-1 font-size-12">Pending Total</h6>
                                <h4 class="text-warning mb-0 fw-bold">{{ money($total_pending) }}</h4>
                            </div>
                        </div>
                        <div class="col">
                            <div class="p-2 border rounded bg-light">
                                <h6 class="text-muted mb-1 font-size-12">Grand Total</h6>
                                <h4 class="text-primary mb-0 fw-bold">{{ money($grand_total) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="accordion" id="paymentsAccordion">
                @forelse($grouped_payments as $date => $dayPayments)
                    @php
                        $dayTotal = $dayPayments->filter(fn($p) => (($p->payment_info['status'] ?? 'Pending') === 'Approved' || ($p->payment_info['status'] ?? 'Pending') === 'Pending'))->sum('amount');
                        $dayApproved = $dayPayments->filter(fn($p) => ($p->payment_info['status'] ?? 'Pending') === 'Approved')->sum('amount');
                        $dayPending = $dayPayments->filter(fn($p) => ($p->payment_info['status'] ?? 'Pending') === 'Pending')->sum('amount');
                        $formattedDate = \Carbon\Carbon::parse($date)->format('l, F j, Y');
                        $collapseId = 'collapse-' . str_replace('-', '', $date);
                    @endphp
                    <div class="card mb-2 border">
                        <div class="card-header bg-light p-2" id="heading-{{ $date }}">
                            <h5 class="m-0">
                                <button
                                    class="btn btn-link w-100 text-start d-flex justify-content-between align-items-center text-decoration-none shadow-none"
                                    type="button" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}"
                                    aria-expanded="true" aria-controls="{{ $collapseId }}">
                                    <span class="font-size-15 text-dark fw-bold">
                                        <i
                                            class="bx bx-calendar-event me-2 text-primary font-size-18"></i>{{ $formattedDate }}
                                    </span>
                                    <span class="font-size-13 text-muted">
                                        <span class="badge bg-soft-success text-success p-2 me-1">Approved:
                                            {{ money($dayApproved) }}</span>
                                        <span class="badge bg-soft-warning text-warning p-2 me-1">Pending:
                                            {{ money($dayPending) }}</span>
                                        <span class="badge bg-soft-primary text-primary p-2">Total:
                                            {{ money($dayTotal) }}</span>
                                    </span>
                                </button>
                            </h5>
                        </div>

                        <div id="{{ $collapseId }}" class="collapse show" aria-labelledby="heading-{{ $date }}">
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-centered table-bordered table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Supplier</th>
                                                <th>Payment Method</th>
                                                <th>Amount</th>

                                                <th>Total Opening Stock</th>
                                                <th>Last Supplied Date</th>
                                                <th>Last Dept Supplied</th>
                                                <th>Amount To Pay</th>

                                                <th>Remark</th>
                                                <th>Created By</th>
                                                <th>Created Date</th>
                                                <th class="text-center">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($dayPayments as $row)
                                                @php
                                                    $chequeDate = $row->payment_info['cheque_date'] ?? '';
                                                    $dateOfIssued = $row->payment_info['date_of_issued'] ?? '';
                                                    $valueDate = $row->payment_info['going_out_date'] ?? '';
                                                    $approvedDate = $row->payment_info['approved_date'] ?? '';
                                                    $declineDate = $row->payment_info['decline_date'] ?? '';
                                                    $status = $row->payment_info['status'] ?? 'Pending';
                                                    $statusBadgeClass = $status === 'Approved' ? 'success' : ($status === 'Declined' ? 'danger' : 'warning');
                                                @endphp

                                                @php
                                                    $amountToPay = NULL;
                                                    $totalOpeningStock = NULL;
                                                    $lastSupplyDate = NULL;
                                                    if (isset($row->supplier->supplierStockOpening)) {
                                                        $amountToPay = $row->supplier->supplierStockOpening->total_supplier_outstanding + ($row->supplier->supplierStockOpening->total_opening_cost_price + $row->supplier->supplierStockOpening->total_opening_retail_cost_price);
                                                        $totalOpeningStock = $row->supplier->supplierStockOpening->total_opening_cost_price + $row->supplier->supplierStockOpening->total_opening_retail_cost_price;
                                                        $lastSupplyDate = $row->supplier->supplierStockOpening->last_supplier_date;

                                                    }
                                                @endphp

                                                <tr>
                                                    <td>
                                                        <a target="_blank"
                                                            href="{{ route('supplier.show', $row->supplier->id) }}"
                                                            class="fw-medium text-primary">
                                                            {{ $row->supplier->name }}
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <span class="fw-semibold">{{ $row->paymentmethod->name ?? '' }}</span>
                                                        <small class="text-muted d-block">Cheque Date: {{ $chequeDate }},Value
                                                            Date: {{ $valueDate }}
                                                            {{ $status === 'Approved' ? "Approved Date : $approvedDate" : ($status === 'Declined' ? "Declined Date : $declineDate" : "") }}</small>
                                                        <span
                                                            class="badge badge-soft-{{ $statusBadgeClass }} mt-1">{{ $status }}</span>
                                                    </td>
                                                    <td class="fw-bold text-success">{{ money($row->amount) }}</td>

                                                    <td class="fw-bold">{{ money($totalOpeningStock) }}</td>
                                                    <td class="fw-bold">{{ mysql_str_date($lastSupplyDate) }}</td>
                                                    <td class="fw-bold">{{ $row->supplier->lastDeptSupplied() }}</td>
                                                    <td class="fw-bold">{{ money($amountToPay) }}</td>

                                                    <td><span class="text-wrap"
                                                            style="max-width: 150px; display: inline-block;">{{ $row->remark ?? 'N/A' }}</span>
                                                    </td>
                                                    <td>{{ $row->user->name ?? 'N/A' }}</td>
                                                    <td>{{ eng_str_date($row->created_at) }}</td>
                                                    <td class="text-center" style="width: 180px;">
                                                        @if($status === 'Pending' and (userCanView('supplier.payment.approve_supplier_cheque_payment') || userCanView('supplier.payment.decline_supplier_cheque_payment') || auth()->user()->can("update", $row)))
                                                            @if(userCanView('supplier.payment.approve_supplier_cheque_payment'))
                                                                <button wire:click="approve({{ $row->id }})"
                                                                    onclick="return confirm('Are you sure you want to approve this cheque payment?');"
                                                                    class="btn btn-success btn-sm me-1">
                                                                    <i class="fa fa-check me-1"></i> Approve
                                                                </button>
                                                            @endif
                                                            @if(auth()->user()->can("update", $row))
                                                                <a href="{{ route('supplier.payment.edit', $row->id) }}"
                                                                    class="btn btn-primary btn-sm">Edit Payment</a>
                                                            @endif
                                                            @if(userCanView('supplier.payment.decline_supplier_cheque_payment'))
                                                                <button wire:click="decline({{ $row->id }})"
                                                                    onclick="return confirm('Are you sure you want to decline this cheque payment?');"
                                                                    class="btn btn-danger btn-sm">
                                                                    <i class="fa fa-times me-1"></i> Decline
                                                                </button>
                                                            @endif
                                                        @else
                                                            <span class="text-muted font-size-12">No Action</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="alert alert-info text-center py-4">
                        <i class="bx bx-info-circle font-size-24 align-middle me-2"></i>
                        No cheque payments found for the selected period.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>