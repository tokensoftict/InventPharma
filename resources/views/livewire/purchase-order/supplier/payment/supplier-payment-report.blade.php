<div>
    <div class="card">
        <div class="card-header bg-transparent border-bottom">
            <h4 class="card-title text-primary"><i class="bx bx-file me-1"></i> Un-approved Supplier Cheque Payment Report</h4>
            <p class="card-title-desc">Filter and manage pending cheque payments before approval.</p>
        </div>
        <div class="card-body">
            <div class="row mb-4 align-items-end">
                <div class="col-md-3">
                    <label class="form-label font-weight-bold">Filter By Date</label>
                    <select class="form-select" wire:model.live="date_filter">
                        <option value="Today">Today</option>
                        <option value="This Week">This Week</option>
                        <option value="This Month">This Month</option>
                        <option value="Custom">Custom Date Range</option>
                    </select>
                </div>
                @if($date_filter === 'Custom')
                    <div class="col-md-3">
                        <label class="form-label">Start Date</label>
                        <input type="text" class="form-control datepicker-basic" wire:model.live="start_date" placeholder="YYYY-MM-DD">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">End Date</label>
                        <input type="text" class="form-control datepicker-basic" wire:model.live="end_date" placeholder="YYYY-MM-DD">
                    </div>
                @endif
                <div class="col-md-3 ms-auto text-end">
                    <div class="p-2 border rounded bg-light">
                        <h6 class="text-muted mb-1">Grand Total Pending</h6>
                        <h3 class="text-danger mb-0">{{ money($grand_total) }}</h3>
                    </div>
                </div>
            </div>

            <div class="accordion" id="paymentsAccordion">
                @forelse($grouped_payments as $date => $dayPayments)
                    @php
                        $dayTotal = $dayPayments->sum('amount');
                        $formattedDate = \Carbon\Carbon::parse($date)->format('l, F j, Y');
                        $collapseId = 'collapse-' . str_replace('-', '', $date);
                    @endphp
                    <div class="card mb-2 border">
                        <div class="card-header bg-light p-2" id="heading-{{ $date }}">
                            <h5 class="m-0">
                                <button class="btn btn-link w-100 text-start d-flex justify-content-between align-items-center text-decoration-none shadow-none" 
                                        type="button" 
                                        data-bs-toggle="collapse" 
                                        data-bs-target="#{{ $collapseId }}" 
                                        aria-expanded="true" 
                                        aria-controls="{{ $collapseId }}">
                                    <span class="font-size-15 text-dark fw-bold">
                                        <i class="bx bx-calendar-event me-2 text-primary font-size-18"></i>{{ $formattedDate }}
                                    </span>
                                    <span class="badge bg-soft-success text-success font-size-13 p-2">
                                        Daily Total: {{ money($dayTotal) }}
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
                                                <th>Type</th>
                                                <th>Payment Method</th>
                                                <th>Amount</th>
                                                <th>Payment Date</th>
                                                <th>Remark</th>
                                                <th>Created By</th>
                                                <th>Last Modified</th>
                                                <th class="text-center">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($dayPayments as $row)
                                                <tr>
                                                    <td>
                                                        <a target="_blank" href="{{ route('supplier.show', $row->supplier->id) }}" class="fw-medium text-primary">
                                                            {{ $row->supplier->name }}
                                                        </a>
                                                    </td>
                                                    <td>{{ $row->type }}</td>
                                                    <td>
                                                        @php
                                                            $chequeDate = $row->payment_info['cheque_date'] ?? '';
                                                            $dateOfIssued = $row->payment_info['date_of_issued'] ?? '';
                                                            $status = $row->payment_info['status'] ?? 'Pending';
                                                            $statusBadgeClass = $status === 'Approved' ? 'success' : ($status === 'Declined' ? 'danger' : 'warning');
                                                        @endphp
                                                        <span class="fw-semibold">{{ $row->paymentmethod->name ?? '' }}</span>
                                                        <small class="text-muted d-block">Issued: {{ $dateOfIssued }}, Cheque: {{ $chequeDate }}</small>
                                                        <span class="badge badge-soft-{{ $statusBadgeClass }} mt-1">{{ $status }}</span>
                                                    </td>
                                                    <td class="fw-bold text-success">{{ money($row->amount) }}</td>
                                                    <td>{{ eng_str_date($row->payment_date) }}</td>
                                                    <td>
                                                        <span class="text-wrap" style="max-width: 150px; display: inline-block;">{{ $row->remark ?? 'N/A' }}</span>
                                                    </td>
                                                    <td>{{ $row->user->name ?? 'N/A' }}</td>
                                                    <td>{{ $row->updated_at }}</td>
                                                    <td class="text-center" style="width: 180px;">
                                                        <button wire:click="approve({{ $row->id }})" 
                                                                onclick="return confirm('Are you sure you want to approve this cheque payment?');" 
                                                                class="btn btn-success btn-sm me-1">
                                                            <i class="fa fa-check me-1"></i> Approve
                                                        </button>
                                                        <button wire:click="decline({{ $row->id }})" 
                                                                onclick="return confirm('Are you sure you want to decline this cheque payment?');" 
                                                                class="btn btn-danger btn-sm">
                                                            <i class="fa fa-times me-1"></i> Decline
                                                        </button>
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
                        No un-approved cheque payments found for the selected period.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
