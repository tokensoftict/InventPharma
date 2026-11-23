<div>
    <form method="post" wire:submit="savePayment">
        <div class="col-md-7 offset-2">
            <div class="mb-3">
                <label>Payment Date</label>
                <input type="text" wire:model="payment_data.payment_date" placeholder="Payment Date" class="form-control datepicker-basic">
                @error('payment_data.payment_date') <span class="text-danger d-block">{{ $message }}</span> @enderror
            </div>

            <div class="mb-3" wire:ignore>
                <label>Supplier</label>
                <select class="form-control select2Product" wire:model.live="payment_data.supplier_id" style="width: 100%;">
                    <option value="">Select Supplier</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>
            @error('payment_data.supplier_id') <span class="text-danger d-block">{{ $message }}</span> @enderror

            @if($selectedSupplierSummary)
                <div class="card border-primary mb-3 bg-light">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-1 mb-2">
                            <strong class="text-primary">Supplier Balance Summary</strong>
                            <span class="badge bg-primary">{{ $selectedSupplierSummary['supplier_name'] }}</span>
                        </div>
                        <div class="row text-center">
                            <div class="col-md-4">
                                <small class="text-muted d-block">Current Credit Balance</small>
                                <strong class="{{ $selectedSupplierSummary['credit_balance'] < 0 ? 'text-danger' : 'text-success' }}">
                                    ₦{{ money($selectedSupplierSummary['credit_balance']) }}
                                </strong>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block">Outstanding Owed to Supplier</small>
                                <strong class="text-danger">₦{{ money($selectedSupplierSummary['outstanding_balance']) }}</strong>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block">Remaining Owed After Payment</small>
                                <strong class="text-dark">₦{{ money($selectedSupplierSummary['remaining_owed_after_payment']) }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="mb-3">
                <label>Payment Method</label>
                <select class="form-control" id="paymentMthod" wire:model.live="payment_data.paymentmethod_id">
                    <option value="">Select Payment Method</option>
                    @foreach($paymentMethods as $paymentMethod)
                        <option value="{{ $paymentMethod->id }}">{{ $paymentMethod->name }}</option>
                    @endforeach
                </select>
                @error('payment_data.paymentmethod_id') <span class="text-danger d-block">{{ $message }}</span> @enderror
            </div>

            @if($isStockForStock)
                <div class="card border-info mb-3">
                    <div class="card-header bg-info-subtle">
                        <h6 class="mb-0 text-dark"><i class="bx bx-transfer me-1"></i> Stock for Stock — Exchange Invoice Selection</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Search Stock Exchange Invoice by Invoice Number</label>
                            <input type="text"
                                   class="form-control"
                                   wire:model.live.debounce.300ms="invoiceSearch"
                                   placeholder="Type invoice number to filter eligible stock exchange invoices...">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Select Eligible Stock Exchange Invoice <span class="text-danger">*</span></label>
                            <select class="form-select" wire:model.live="payment_data.invoice_id">
                                <option value="">-- Select Stock Exchange Invoice --</option>
                                @foreach($eligibleStockExchangeInvoices as $inv)
                                    <option value="{{ $inv->id }}">
                                        Invoice #{{ $inv->invoice_number }} | {{ $inv->supplier->name ?? 'Supplier' }} | Status: {{ status_name($inv->status_id) }} | Remaining Balance: ₦{{ money($inv->remaining_exchange_balance) }} (Original: ₦{{ money($inv->original_invoice_total) }})
                                    </option>
                                @endforeach
                            </select>
                            @if(!empty($this->payment_data['supplier_id']) && $eligibleStockExchangeInvoices->isEmpty())
                                <small class="text-warning d-block mt-1">
                                    No eligible stock exchange invoices with an available balance were found for this supplier.
                                </small>
                            @endif
                            @error('payment_data.invoice_id') <span class="text-danger d-block">{{ $message }}</span> @enderror
                        </div>

                        @if($selectedInvoiceSummary)
                            <div class="p-3 border rounded bg-light mb-2">
                                <h6 class="fw-bold text-dark border-bottom pb-2 mb-2">Selected Stock Exchange Invoice Details</h6>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <small class="text-muted d-block">Invoice Number</small>
                                        <strong>#{{ $selectedInvoiceSummary['invoice_number'] }}</strong>
                                    </div>
                                    <div class="col-md-4">
                                        <small class="text-muted d-block">Supplier</small>
                                        <strong>{{ $selectedInvoiceSummary['supplier_name'] }}</strong>
                                    </div>
                                    <div class="col-md-4">
                                        <small class="text-muted d-block">Current Status</small>
                                        <span class="badge bg-info text-dark">{{ $selectedInvoiceSummary['status_name'] }}</span>
                                    </div>
                                    <div class="col-md-4">
                                        <small class="text-muted d-block">Original Invoice Value</small>
                                        <strong>₦{{ money($selectedInvoiceSummary['original_value']) }}</strong>
                                    </div>
                                    <div class="col-md-4">
                                        <small class="text-muted d-block">Previously Utilised Amount</small>
                                        <strong class="text-primary">₦{{ money($selectedInvoiceSummary['utilised_amount']) }}</strong>
                                    </div>
                                    <div class="col-md-4">
                                        <small class="text-muted d-block">Remaining Exchange Balance</small>
                                        <strong class="text-success">₦{{ money($selectedInvoiceSummary['remaining_balance']) }}</strong>
                                    </div>
                                </div>
                                <hr class="my-2">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <small class="text-muted d-block">Remaining Invoice Exchange Balance After Payment</small>
                                        <strong class="text-dark">₦{{ money($selectedInvoiceSummary['remaining_after_allocation']) }}</strong>
                                    </div>
                                    @if($selectedSupplierSummary)
                                        <div class="col-md-6">
                                            <small class="text-muted d-block">Remaining Supplier Balance Owed After Payment</small>
                                            <strong class="text-dark">₦{{ money($selectedSupplierSummary['remaining_owed_after_payment']) }}</strong>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <div class="mb-3">
                <label>{{ $isStockForStock ? 'Amount to Apply (₦)' : 'Amount' }}</label>
                <input type="text" placeholder="Amount Paid" wire:model.live="payment_data.amount" class="form-control">
                @error('payment_data.amount') <span class="text-danger d-block">{{ $message }}</span> @enderror
            </div>

            @if($isTransfer)
                <div id="transfer_details" class="card border-secondary mb-3">
                    <div class="card-header bg-light py-2">
                        <h6 class="mb-0 text-dark"><i class="bx bx-bank me-1"></i> Transfer Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label>Bank Name <span class="text-danger">*</span></label>
                            <select class="form-select" wire:model="payment_data.payment_info.bank_name">
                                <option value="">Select Bank</option>
                                @foreach($banks as $bank)
                                    <option value="{{ $bank->name }}">{{ $bank->name }}</option>
                                @endforeach
                            </select>
                            @error('payment_data.payment_info.bank_name') <span class="text-danger d-block">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-3">
                            <label>Account Number <span class="text-danger">*</span></label>
                            <input type="text" wire:model="payment_data.payment_info.account_number" placeholder="Enter Account Number" class="form-control">
                            @error('payment_data.payment_info.account_number') <span class="text-danger d-block">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-0">
                            <label>Account Name <span class="text-danger">*</span></label>
                            <input type="text" wire:model="payment_data.payment_info.account_name" placeholder="Enter Account Name" class="form-control">
                            @error('payment_data.payment_info.account_name') <span class="text-danger d-block">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
            @endif

            @if($isCash)
                <div id="cash_details" class="mb-3">
                    <label>Cash Received By <span class="text-danger">*</span></label>
                    <input type="text" wire:model="payment_data.payment_info.cash_received_by" placeholder="Enter name of person who received the cash" class="form-control">
                    @error('payment_data.payment_info.cash_received_by') <span class="text-danger d-block">{{ $message }}</span> @enderror
                </div>
            @endif

            @if(isset($this->payment_data['paymentmethod_id']) and (string) $this->payment_data['paymentmethod_id'] === "8")
                <div id="cheque_details">
                    <div class="mb-3">
                        <label>Cheque Date</label>
                        <input type="text" wire:model="payment_data.payment_info.cheque_date" placeholder="Cheque Date" id="chequeDate" class="form-control datepicker-basic">
                        @error('payment_data.payment_info.cheque_date') <span class="text-danger d-block">{{ $message }}</span> @enderror
                    </div>
                </div>
            @endif

            <div class="mb-3">
                <label>Remark</label>
                <textarea class="form-control" placeholder="Remark" name="remark" wire:model="payment_data.remark"></textarea>
            </div>

            @if($showConfirmation && $isStockForStock && $selectedInvoiceSummary && $selectedSupplierSummary)
                <div class="alert alert-warning border-warning p-3 mb-3">
                    <h6 class="fw-bold text-dark mb-2"><i class="bx bx-check-shield me-1"></i> Confirm Stock for Stock Supplier Payment</h6>
                    <p class="mb-2 small">Please verify the details below before confirming:</p>
                    <ul class="small mb-3">
                        <li><strong>Supplier:</strong> {{ $selectedInvoiceSummary['supplier_name'] }}</li>
                        <li><strong>Stock Exchange Invoice:</strong> #{{ $selectedInvoiceSummary['invoice_number'] }} (Available: ₦{{ money($selectedInvoiceSummary['remaining_balance']) }})</li>
                        <li><strong>Amount to Offset Now:</strong> ₦{{ money($selectedInvoiceSummary['proposed_amount']) }}</li>
                        <li><strong>Remaining Invoice Exchange Balance After Payment:</strong> ₦{{ money($selectedInvoiceSummary['remaining_after_allocation']) }}</li>
                        <li><strong>Remaining Supplier Balance Owed After Payment:</strong> ₦{{ money($selectedSupplierSummary['remaining_owed_after_payment']) }}</li>
                    </ul>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success" wire:loading.attr="disabled">
                            <i class="fa fa-check me-1"></i> Confirm &amp; Save Stock Exchange Payment
                        </button>
                        <button type="button" class="btn btn-outline-secondary" wire:click="cancelConfirmation">
                            Modify Details
                        </button>
                    </div>
                </div>
            @endif

            <div class="col-lg-12">
                <div class="col-lg-12 mt-4">
                    @if($isStockForStock && !$showConfirmation)
                        <button type="button" wire:click="previewConfirmation" class="btn btn-info btn-lg me-2 text-white" wire:loading.attr="disabled">
                            Review &amp; Confirm
                        </button>
                    @endif
                    <button type="submit" class="btn btn-primary btn-lg me-2" wire:loading.attr="disabled">
                        Save Payment
                        <i wire:loading.remove wire:target="savePayment" class="fa fa-check"></i>
                        <span wire:loading wire:target="savePayment" class="spinner-border spinner-border-sm me-2" role="status"></span>
                    </button>
                    <a href="{{ route('supplier.payment.index') }}" class="btn btn-danger btn-lg">Cancel</a>
                </div>
            </div>
        </div>
    </form>

    <script>
        window.addEventListener('load', function () {
            $(document).ready(function () {
                if (typeof $.fn.select2 === 'function') {
                    $('.select2Product').select2({
                        placeholder: 'Select Supplier',
                        width: '100%'
                    });

                    $('.select2Product').on('change', function () {
                        var data = $(this).val();
                        @this.set('payment_data.supplier_id', data);
                    });
                }
            });
        });
    </script>
</div>
