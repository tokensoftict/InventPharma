@section('pageHeaderTitle', 'Customer Dashboard')
@section('pageHeaderDescription', 'Unified customer management, debt tracking, spending at a glance, and wholesale/retail profiling')

@section('pageHeaderAction')
    @if(userCanView('customer.create'))
        <button wire:click="new" wire:target="new" wire:loading.attr="disabled" type="button" class="btn btn-primary waves-effect waves-light shadow-sm">
            <i wire:loading.remove wire:target="new" class="bx bx-user-plus me-1 fs-5 align-middle"></i>
            <span wire:loading wire:target="new" class="spinner-border spinner-border-sm me-2" role="status"></span>
            New Customer
        </button>

    @endif
@endsection

<div>
    @if(View::hasSection('pageHeaderTitle'))
        @include('shared.pageheader')
    @endif

    {{-- =========================================================================
         TOP SUMMARY STATS CARDS (AT A GLANCE)
         ========================================================================= --}}
    <div class="row g-3 mb-4">
        {{-- Card 1: Total Customers --}}
        <div class="col-xl-3 col-md-6">
            <div class="card card-h-100 shadow-sm border-0 mb-0">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <span class="text-muted mb-2 lh-1 d-block text-truncate small fw-semibold text-uppercase">Total Customers</span>
                            <h3 class="mb-2 text-dark fw-bold">
                                {{ number_format($stats['total_customers']) }}
                            </h3>
                            <div class="d-flex flex-wrap gap-1 mt-1">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle" title="Wholesales only">{{ number_format($stats['wholesale_count']) }} Wholesale</span>
                                <span class="badge bg-success-subtle text-success border border-success-subtle" title="Retail only">{{ number_format($stats['retail_count']) }} Retail</span>
                                <span class="badge text-white" style="background-color: #6f42c1 !important;" title="Active in Both">{{ number_format($stats['both_count']) }} Both</span>
                            </div>
                        </div>
                        <div class="flex-shrink-0">
                            <div class="avatar-sm rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="bx bx-group fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card 2: Total Debt / Customers Owing --}}
        <div class="col-xl-3 col-md-6">
            <div class="card card-h-100 shadow-sm border-0 mb-0 border-start border-danger border-4">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <span class="text-muted mb-2 lh-1 d-block text-truncate small fw-semibold text-uppercase">Total Debt (Owing)</span>
                            <h3 class="mb-2 text-danger fw-bold">
                                ₦{{ number_format($stats['total_owing'], 2) }}
                            </h3>
                            <div class="text-muted small">
                                <span class="text-danger fw-bold"><i class="bx bx-error-circle me-1"></i>{{ number_format($stats['debtors_count']) }}</span> customer(s) currently owing
                            </div>
                        </div>
                        <div class="flex-shrink-0">
                            <div class="avatar-sm rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="bx bx-credit-card-front fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card 3: Total Customer Spending --}}
        <div class="col-xl-3 col-md-6">
            <div class="card card-h-100 shadow-sm border-0 mb-0">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <span class="text-muted mb-2 lh-1 d-block text-truncate small fw-semibold text-uppercase">Total Customer Spending</span>
                            <h3 class="mb-2 text-success fw-bold">
                                ₦{{ number_format($stats['total_spent'], 2) }}
                            </h3>
                            <div class="text-muted small">
                                <i class="bx bx-check-double text-success me-1"></i>Lifetime completed purchases
                            </div>
                        </div>
                        <div class="flex-shrink-0">
                            <div class="avatar-sm rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="bx bx-cart-alt fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card 4: Customer Deposits / Advances --}}
        <div class="col-xl-3 col-md-6">
            <div class="card card-h-100 shadow-sm border-0 mb-0">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <span class="text-muted mb-2 lh-1 d-block text-truncate small fw-semibold text-uppercase">Customer Deposits / Advance</span>
                            <h3 class="mb-2 text-info fw-bold">
                                ₦{{ number_format($stats['total_deposit'], 2) }}
                            </h3>
                            <div class="text-muted small">
                                <i class="bx bx-wallet text-info me-1"></i>Prepaid wallet &amp; credit balances
                            </div>
                        </div>
                        <div class="flex-shrink-0">
                            <div class="avatar-sm rounded-circle bg-info-subtle text-info d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="bx bx-wallet-alt fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- =========================================================================
         QUICK FILTER BAR (MERGED WHOLESALE, RETAIL, BOTH & FINANCIAL STATUS)
         ========================================================================= --}}
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body p-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                {{-- Department Filters --}}
                <div class="d-flex flex-wrap align-items-center gap-1">
                    <span class="small text-muted fw-semibold me-2 text-uppercase" style="font-size: 11px;">Department:</span>
                    <button type="button" 
                            wire:click="filterByDepartment('all')" 
                            class="btn btn-sm {{ $currentDepartmentFilter === 'all' ? 'btn-primary' : 'btn-outline-secondary' }} rounded-pill px-3">
                        <i class="bx bx-grid-alt me-1"></i> All Customers
                    </button>
                    <button type="button" 
                            wire:click="filterByDepartment('wholesale')" 
                            class="btn btn-sm {{ $currentDepartmentFilter === 'wholesale' ? 'btn-primary' : 'btn-outline-primary' }} rounded-pill px-3">
                        <i class="bx bx-buildings me-1"></i> Wholesales
                    </button>
                    <button type="button" 
                            wire:click="filterByDepartment('retail')" 
                            class="btn btn-sm {{ $currentDepartmentFilter === 'retail' ? 'btn-success' : 'btn-outline-success' }} rounded-pill px-3">
                        <i class="bx bx-store me-1"></i> Retail
                    </button>
                    <button type="button" 
                            wire:click="filterByDepartment('both')" 
                            class="btn btn-sm {{ $currentDepartmentFilter === 'both' ? 'text-white' : 'btn-outline-dark' }} rounded-pill px-3"
                            style="{{ $currentDepartmentFilter === 'both' ? 'background-color: #6f42c1 !important; border-color: #6f42c1;' : '' }}">
                        <i class="bx bx-git-merge me-1"></i> Both (Wholesale &amp; Retail)
                    </button>
                </div>

                {{-- Financial & Debt Quick Filters --}}
                <div class="d-flex flex-wrap align-items-center gap-1">
                    <span class="small text-muted fw-semibold me-2 text-uppercase" style="font-size: 11px;">Status:</span>
                    <button type="button" 
                            wire:click="filterByFinancial('owing')" 
                            class="btn btn-sm {{ $currentFinancialFilter === 'owing' ? 'btn-danger' : 'btn-outline-danger' }} rounded-pill px-3">
                        <i class="bx bx-error-circle me-1"></i> Owing (Debtors)
                    </button>
                    <button type="button" 
                            wire:click="filterByFinancial('deposit')" 
                            class="btn btn-sm {{ $currentFinancialFilter === 'deposit' ? 'btn-info' : 'btn-outline-info' }} rounded-pill px-3">
                        <i class="bx bx-wallet me-1"></i> Has Deposit
                    </button>
                    @if($currentDepartmentFilter !== 'all' || !empty($currentFinancialFilter))
                        <button type="button" wire:click="resetFilters" class="btn btn-sm btn-light rounded-pill px-2" title="Reset all filters">
                            <i class="bx bx-reset me-1"></i> Clear
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- =========================================================================
         MERGED CUSTOMER DATATABLE
         ========================================================================= --}}
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <livewire:customer.datatable.customer-data-table :filters="$this->filters" :wire:key="'cust-dt-'.md5(json_encode($this->filters))"/>
        </div>
    </div>

    {{-- =========================================================================
         CUSTOMER 360 GLANCE MODAL (AT A GLANCE OVERVIEW)
         ========================================================================= --}}
    <div wire:ignore.self class="modal fade" id="customerGlanceModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content border-0 shadow">
                @if($selectedCustomer)
                    <div class="modal-header bg-light py-3">
                        <div class="d-flex align-items-center">
                            <div class="avatar-sm rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3 fw-bold fs-5" style="width: 44px; height: 44px;">
                                {{ strtoupper(substr($selectedCustomer->firstname ?? '', 0, 1) . substr($selectedCustomer->lastname ?? '', 0, 1)) }}
                            </div>
                            <div>
                                <h5 class="modal-title mb-1 fw-bold text-dark">
                                    {{ $selectedCustomer->firstname }} {{ $selectedCustomer->lastname }}
                                    <span class="ms-2">{!! $selectedCustomer->type_badge !!}</span>
                                    @if($selectedCustomer->status)
                                        <span class="badge bg-success-subtle text-success border border-success small ms-1">Active</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger small ms-1">Inactive</span>
                                    @endif
                                </h5>
                                <div class="text-muted small">
                                    <span class="me-3"><i class="bx bx-phone text-muted me-1"></i>{{ $selectedCustomer->phone_number ?: 'No phone' }}</span>
                                    <span class="me-3"><i class="bx bx-envelope text-muted me-1"></i>{{ $selectedCustomer->email ?: 'No email' }}</span>
                                    <span><i class="bx bx-map-pin text-muted me-1"></i>{{ $selectedCustomer->city?->name ?? 'No city' }} {{ $selectedCustomer->address ? '('.$selectedCustomer->address.')' : '' }}</span>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4">
                        {{-- Financial Glance Cards --}}
                        <div class="row g-3 mb-4">
                            {{-- Debt / Credit Box --}}
                            <div class="col-md-3">
                                <div class="p-3 rounded border {{ $selectedCustomer->is_owing ? 'bg-danger-subtle border-danger' : 'bg-light border' }}">
                                    <div class="small fw-semibold text-uppercase {{ $selectedCustomer->is_owing ? 'text-danger' : 'text-muted' }} mb-1">
                                        <i class="bx {{ $selectedCustomer->is_owing ? 'bx-error-circle' : 'bx-check-circle' }} me-1"></i>
                                        {{ $selectedCustomer->is_owing ? 'Current Debt (Owing)' : 'Balance Status' }}
                                    </div>
                                    <h4 class="fw-bold mb-0 {{ $selectedCustomer->is_owing ? 'text-danger' : 'text-success' }}">
                                        @if($selectedCustomer->is_owing)
                                            ₦{{ number_format($selectedCustomer->owing_amount, 2) }}
                                        @elseif($selectedCustomer->credit_balance > 0)
                                            Deposit: ₦{{ number_format($selectedCustomer->credit_balance, 2) }}
                                        @else
                                            ₦0.00 (Cleared)
                                        @endif
                                    </h4>
                                    <div class="small text-muted mt-1">
                                        {{ $selectedCustomer->is_owing ? 'Customer owes business' : 'No overdue credit' }}
                                    </div>
                                </div>
                            </div>

                            {{-- Total Spent Box --}}
                            <div class="col-md-3">
                                <div class="p-3 rounded border bg-light">
                                    <div class="small fw-semibold text-uppercase text-muted mb-1">
                                        <i class="bx bx-cart me-1"></i> Lifetime Spending
                                    </div>
                                    <h4 class="fw-bold text-dark mb-0">
                                        ₦{{ number_format($selectedCustomerStats['total_spent'] ?? 0, 2) }}
                                    </h4>
                                    <div class="small text-muted mt-1">
                                        {{ $selectedCustomerStats['total_invoices'] ?? 0 }} completed invoice(s)
                                    </div>
                                </div>
                            </div>

                            {{-- Wholesale vs Retail Spending Breakdown --}}
                            <div class="col-md-3">
                                <div class="p-3 rounded border bg-light">
                                    <div class="small fw-semibold text-uppercase text-muted mb-1">
                                        <i class="bx bx-pie-chart-alt me-1"></i> Spending Breakdown
                                    </div>
                                    <div class="small d-flex justify-content-between align-items-center mb-1">
                                        <span><i class="bx bx-buildings text-primary me-1"></i>Wholesale:</span>
                                        <strong>₦{{ number_format($selectedCustomerStats['wholesale_spent'] ?? 0, 2) }}</strong>
                                    </div>
                                    <div class="small d-flex justify-content-between align-items-center">
                                        <span><i class="bx bx-store text-success me-1"></i>Retail:</span>
                                        <strong>₦{{ number_format($selectedCustomerStats['retail_spent'] ?? 0, 2) }}</strong>
                                    </div>
                                </div>
                            </div>

                            {{-- Member Groups & Loyalty Points --}}
                            <div class="col-md-3">
                                <div class="p-3 rounded border bg-light">
                                    <div class="small fw-semibold text-uppercase text-muted mb-1">
                                        <i class="bx bx-award me-1"></i> Member Groups &amp; Loyalty
                                    </div>
                                    <div class="small mb-1 text-truncate">
                                        <span class="text-muted">Wholesale:</span>
                                        <span class="badge bg-primary-subtle text-primary">{{ $selectedCustomer->memberGroup?->label ?? 'None' }}</span>
                                        <span class="text-muted ms-1">({{ number_format($selectedCustomer->loyalty_points ?? 0) }} pts)</span>
                                    </div>
                                    <div class="small text-truncate">
                                        <span class="text-muted">Retail:</span>
                                        <span class="badge bg-info-subtle text-info">{{ $selectedCustomer->retailMemberGroup?->label ?? 'None' }}</span>
                                        <span class="text-muted ms-1">({{ number_format($selectedCustomer->retail_loyalty_points ?? 0) }} pts)</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Nav Tabs for Details --}}
                        <ul class="nav nav-tabs nav-tabs-custom mb-3" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" href="#tab-invoices" role="tab">
                                    <i class="bx bx-receipt me-1"></i> Recent Invoices ({{ count($customerInvoices) }})
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#tab-payments" role="tab">
                                    <i class="bx bx-money me-1"></i> Recent Payments ({{ count($customerPayments) }})
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#tab-creditlogs" role="tab">
                                    <i class="bx bx-history me-1"></i> Credit / Debt Logs ({{ count($customerCreditLogs) }})
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content">
                            {{-- Tab 1: Recent Invoices --}}
                            <div class="tab-pane active" id="tab-invoices" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Invoice #</th>
                                                <th>Date</th>
                                                <th>Department</th>
                                                <th>Status</th>
                                                <th class="text-end">Sub Total</th>
                                                <th class="text-end">Paid Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($customerInvoices as $inv)
                                                <tr>
                                                    <td class="fw-bold">{{ $inv->invoice_number }}</td>
                                                    <td>{{ convert_date($inv->invoice_date) }}</td>
                                                    <td>
                                                        @if($inv->in_department === 'retail')
                                                            <span class="badge bg-success-subtle text-success">Retail</span>
                                                        @elseif(in_array($inv->in_department, ['bulksales', 'wholesales']))
                                                            <span class="badge bg-primary-subtle text-primary">Wholesale</span>
                                                        @else
                                                            <span class="badge bg-secondary-subtle text-secondary">{{ ucfirst($inv->in_department) }}</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-secondary-subtle text-secondary">{{ $inv->status?->name ?? 'Complete' }}</span>
                                                    </td>
                                                    <td class="text-end fw-semibold">₦{{ number_format($inv->sub_total, 2) }}</td>
                                                    <td class="text-end text-success">₦{{ number_format($inv->total_amount_paid, 2) }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="text-center py-4 text-muted">No invoices found for this customer.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {{-- Tab 2: Recent Payments --}}
                            <div class="tab-pane" id="tab-payments" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Date</th>
                                                <th>Payment Method</th>
                                                <th>Reference</th>
                                                <th class="text-end">Amount Paid</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($customerPayments as $pay)
                                                <tr>
                                                    <td>{{ $pay->id }}</td>
                                                    <td>{{ convert_date($pay->payment_date) }}</td>
                                                    <td>{{ $pay->paymentmethod?->name ?? 'Cash' }}</td>
                                                    <td>{{ $pay->reference ?? 'N/A' }}</td>
                                                    <td class="text-end fw-bold text-success">₦{{ number_format($pay->total_paid ?? $pay->amount ?? 0, 2) }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center py-4 text-muted">No payment records found.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {{-- Tab 3: Credit / Debt Logs --}}
                            <div class="tab-pane" id="tab-creditlogs" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Credit #</th>
                                                <th>Date</th>
                                                <th>Description / Type</th>
                                                <th class="text-end">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($customerCreditLogs as $log)
                                                <tr>
                                                    <td>{{ $log->credit_number ?? '#'.$log->id }}</td>
                                                    <td>{{ convert_date($log->payment_date) }}</td>
                                                    <td>
                                                        @if($log->amount < 0)
                                                            <span class="text-danger fw-semibold"><i class="bx bx-minus-circle me-1"></i>Credit Purchase (Debt Added)</span>
                                                        @else
                                                            <span class="text-success fw-semibold"><i class="bx bx-plus-circle me-1"></i>Credit Settlement (Payment)</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-end fw-bold {{ $log->amount < 0 ? 'text-danger' : 'text-success' }}">
                                                        ₦{{ number_format(abs($log->amount), 2) }}
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center py-4 text-muted">No credit log history found.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer bg-light py-2 d-flex justify-content-between">
                        <div>
                            @if(Route::has('reports.customerReport.balance_sheet') && userCanView('reports.customerReport.balance_sheet'))
                                <a href="{{ route('reports.customerReport.balance_sheet', ['filter' => ['from' => now()->subYear()->toDateString(), 'to' => now()->toDateString(), 'customer_id' => $selectedCustomer->id]]) }}" class="btn btn-outline-secondary btn-sm" target="_blank">
                                    <i class="bx bx-book-content me-1"></i> View Full Balance Sheet
                                </a>
                            @endif
                        </div>
                        <div class="d-flex gap-2">
                            @if(userCanView('customer.update'))
                                <button type="button" class="btn btn-primary btn-sm" wire:click="editFromGlance">
                                    <i class="bx bx-edit me-1"></i> Edit Customer
                                </button>
                            @endif
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- =========================================================================
         SIMPLIFIED CUSTOMER FORM MODAL (CREATE / UPDATE)
         ========================================================================= --}}
    <div wire:ignore.self class="modal fade" id="simpleComponentModal" tabindex="-1" role="dialog" aria-hidden="true">
        <form method="post" wire:submit="saveCustomers">
            <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                <div class="modal-content shadow border-0">
                    <div class="modal-header bg-light py-3">
                        <h5 class="modal-title fw-bold">
                            <i class="bx {{ $modelId ? 'bx-edit' : 'bx-user-plus' }} me-2 text-primary"></i>
                            {{ $this->modalTitle }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4">
                        <div class="row g-3">
                            {{-- Firstname & Lastname --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Firstname <span class="text-danger">*</span></label>
                                <input class="form-control" type="text" wire:model="firstname" placeholder="e.g. John" required>
                                @error('firstname') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Lastname <span class="text-danger">*</span></label>
                                <input class="form-control" type="text" wire:model="lastname" placeholder="e.g. Doe" required>
                                @error('lastname') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            {{-- Phone Number (Flexible & Non-blocking) --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Phone Number <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bx bx-phone"></i></span>
                                    <input class="form-control" type="text" wire:model.live.debounce.400ms="phone_number" placeholder="e.g. 08012345678" required>
                                </div>
                                <div class="form-text small text-muted">Required. Uniquely identifies the customer (e.g. 080..., +234...).</div>
                                @error('phone_number') <span class="text-danger small">{{ $message }}</span> @enderror

                                {{-- Duplicate Phone Warning Alert --}}
                                @if($duplicatePhoneWarning)
                                    <div class="alert alert-danger py-2 px-3 small mt-2 mb-0">
                                        <i class="bx bx-error-circle me-1"></i> {{ $duplicatePhoneWarning }}
                                    </div>
                                @endif
                            </div>

                            {{-- Email Address --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bx bx-envelope"></i></span>
                                    <input class="form-control" type="email" wire:model="email" placeholder="customer@example.com">
                                </div>
                                @error('email') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            {{-- Customer Category / Department (Wholesale, Retail, Both) --}}
                            <div class="col-12">
                                <label class="form-label fw-semibold d-block">Customer Category / Department <span class="text-danger">*</span></label>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <div class="form-check card p-2 border {{ $customer_type === 'wholesale' ? 'border-primary bg-primary-subtle' : 'border' }} mb-0">
                                            <input class="form-check-input ms-1" type="radio" wire:model="customer_type" value="wholesale" id="typeWholesale">
                                            <label class="form-check-label ms-2 fw-semibold" for="typeWholesale">
                                                <i class="bx bx-buildings text-primary me-1"></i> Wholesales
                                                <div class="small text-muted fw-normal">Customer buys in wholesale</div>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check card p-2 border {{ $customer_type === 'retail' ? 'border-success bg-success-subtle' : 'border' }} mb-0">
                                            <input class="form-check-input ms-1" type="radio" wire:model="customer_type" value="retail" id="typeRetail">
                                            <label class="form-check-label ms-2 fw-semibold" for="typeRetail">
                                                <i class="bx bx-store text-success me-1"></i> Retail
                                                <div class="small text-muted fw-normal">Customer buys in retail</div>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check card p-2 border {{ $customer_type === 'both' ? 'border-purple text-dark' : 'border' }} mb-0" style="{{ $customer_type === 'both' ? 'border-color: #6f42c1 !important; background-color: rgba(111, 66, 193, 0.08);' : '' }}">
                                            <input class="form-check-input ms-1" type="radio" wire:model="customer_type" value="both" id="typeBoth">
                                            <label class="form-check-label ms-2 fw-semibold" for="typeBoth">
                                                <i class="bx bx-git-merge text-purple me-1" style="color: #6f42c1;"></i> Both (Wholesale &amp; Retail)
                                                <div class="small text-muted fw-normal">Customer buys in both</div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                @error('customer_type') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            {{-- City --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">City</label>
                                <select class="form-select" wire:model="city_id">
                                    <option value="">Select City</option>
                                    @foreach($this->cities as $city)
                                        <option value="{{ $city->id }}">{{ $city->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Address --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Address</label>
                                <textarea class="form-control" rows="2" wire:model="address" placeholder="Physical Address"></textarea>
                                @error('address') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            {{-- Member Groups (Controlled by Permission) --}}
                            @if(userCanView("customer.changeorassigncustumertomembergroup"))
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Wholesale Member Group</label>
                                    <select class="form-select" wire:model="member_group_id">
                                        <option value="">Select Member Group</option>
                                        @foreach($this->memberGroups as $mg)
                                            <option value="{{ $mg->id }}">{{ $mg->label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Retail Member Group</label>
                                    <select class="form-select" wire:model="retail_member_group_id">
                                        <option value="">Select Retail Member Group</option>
                                        @foreach($this->memberGroups as $mg)
                                            <option value="{{ $mg->id }}">{{ $mg->label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="modal-footer bg-light py-2 d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" wire:target="saveCustomers" wire:loading.attr="disabled" class="btn btn-primary btn-sm px-4">
                            <span wire:loading wire:target="saveCustomers" class="spinner-border spinner-border-sm me-2" role="status"></span>
                            <i wire:loading.remove wire:target="saveCustomers" class="bx bx-check me-1"></i>
                            {{ $this->saveButton }}
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    {{-- Modal Triggers & Script --}}
    <script>
        (function() {
            function showModal(id) {
                const el = document.getElementById(id);
                if (el && window.bootstrap) {
                    bootstrap.Modal.getOrCreateInstance(el).show();
                }
            }

            function hideModal(id) {
                const el = document.getElementById(id);
                if (el && window.bootstrap) {
                    const inst = bootstrap.Modal.getInstance(el) || bootstrap.Modal.getOrCreateInstance(el);
                    inst.hide();
                }
            }

            window.addEventListener('openModal', () => showModal("simpleComponentModal"));
            window.addEventListener('closeModal', () => hideModal("simpleComponentModal"));
            window.addEventListener('openGlanceModal', () => showModal("customerGlanceModal"));
            window.addEventListener('closeGlanceModal', () => hideModal("customerGlanceModal"));
        })();
    </script>
</div>
