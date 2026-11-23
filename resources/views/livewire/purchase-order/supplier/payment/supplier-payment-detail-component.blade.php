<div>
    <div class="row">
        <div class="col-md-8 offset-2">
            <div class="card">
                <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="my-0 text-primary"><i class="bx bx-receipt me-1"></i> Supplier Payment Information</h5>
                    <a href="{{ route('supplier.payment.index') }}" class="btn btn-secondary btn-sm">Back to
                        Payments</a>
                </div>
                <div class="card-body">
                    <table class="table table-bordered mb-0">
                        <tbody>
                        <tr>
                            <th style="width: 30%;">Supplier</th>
                            <td>{{ $payment->supplier->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Amount</th>
                            <td>
                                <h4 class="text-primary mb-0">{{ money($payment->amount) }}</h4>
                            </td>
                        </tr>
                        @php
                            $amountToPay = NULL;
                            $totalOpeningStock = NULL;
                            $lastSupplyDate = NULL;
                            if(isset($payment->supplier->supplierStockOpening)) {
                                $amountToPay = $payment->supplier->supplierStockOpening->total_supplier_outstanding + ($payment->supplier->supplierStockOpening->total_opening_cost_price+$payment->supplier->supplierStockOpening->total_opening_retail_cost_price);
                                $totalOpeningStock = $payment->supplier->supplierStockOpening->total_opening_cost_price + $payment->supplier->supplierStockOpening->total_opening_retail_cost_price;
                                $lastSupplyDate =  $payment->supplier->supplierStockOpening->last_supplier_date;

                            }
                        @endphp

                        <tr>
                            <th>Total Opening Stock</th>
                            <td>
                               {{ $totalOpeningStock ?  money($totalOpeningStock) : "N/A" }}
                            </td>
                        </tr>
                        <tr>
                            <th>Last Supplied Date</th>
                            <td>
                                {{ $lastSupplyDate ?  mysql_str_date($lastSupplyDate) : "N/A" }}
                            </td>
                        </tr>
                        <tr>
                            <th>Amount To Pay</th>
                            <td>
                                <h4 class="text-primary mb-0">{{ $amountToPay ?  money($amountToPay) : "N/A" }}</h4>
                            </td>
                        </tr>

                        <tr>
                            <th>Payment Method</th>
                            <td>
                                @if($payment->isStockForStock())
                                    <span class="badge bg-info text-dark">Stock for Stock</span>
                                    @if($payment->isReversed())
                                        <span class="badge bg-danger ms-1">Reversed</span>
                                    @else
                                        <span class="badge bg-success ms-1">Active</span>
                                    @endif
                                @else
                                    {{ $payment->paymentmethod->name ?? 'N/A' }}
                                    @if($payment->paymentmethod_id === 8)
                                        @php
                                            $status = $payment->payment_info['status'] ?? 'Pending';
                                        @endphp
                                        <span
                                                class="badge badge-soft-{{ $status === 'Approved' ? 'success' : ($status === 'Declined' ? 'danger' : 'warning') }} ms-1">
                                                {{ $status }}
                                            </span>
                                    @endif
                                @endif
                            </td>
                        </tr>
                        @if($payment->purchase_id)
                            <tr>
                                <th>Related Purchase Order</th>
                                <td>
                                    <a href="{{ route('purchase.show', $payment->purchase_id) }}" class="fw-bold text-primary">
                                        Purchase Order #{{ $payment->purchase_id }}
                                    </a>
                                </td>
                            </tr>
                        @endif
                        @if($payment->isStockForStock())
                            <tr>
                                <th>Related Stock Exchange Invoice</th>
                                <td>
                                    @if($payment->invoice_id && $payment->invoice)
                                        <a href="{{ route('invoiceandsales.view', $payment->invoice_id) }}" class="fw-bold text-primary">
                                            Invoice #{{ $payment->invoice->invoice_number }}
                                        </a>
                                        <span class="ms-2">({!! showStatus($payment->invoice->status_id) !!})</span>
                                    @elseif(!empty($payment->payment_info['invoice_number']))
                                        Invoice #{{ $payment->payment_info['invoice_number'] }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                            </tr>
                            @if($payment->invoice)
                                <tr>
                                    <th>Invoice Exchange Summary</th>
                                    <td>
                                        Original: <strong>₦{{ money($payment->invoice->original_invoice_total) }}</strong> |
                                        Utilised: <strong>₦{{ money($payment->invoice->total_exchange_utilised) }}</strong> |
                                        Remaining Balance: <strong>₦{{ money($payment->invoice->remaining_exchange_balance) }}</strong>
                                    </td>
                                </tr>
                            @endif
                        @endif
                        @if($payment->reference || !empty($payment->payment_info['reference']))
                            <tr>
                                <th>Reference</th>
                                <td>{{ $payment->reference ?? $payment->payment_info['reference'] }}</td>
                            </tr>
                        @endif
                        @if($payment->isReversed())
                            <tr>
                                <th>Reversal Information</th>
                                <td class="text-danger">
                                    Reversed by {{ $payment->reversedByUser->name ?? 'User' }}
                                    @if($payment->reversed_at)
                                        on {{ eng_str_date($payment->reversed_at) }}
                                    @endif
                                    @if($payment->reversal_reason)
                                        — Reason: {{ $payment->reversal_reason }}
                                    @endif
                                </td>
                            </tr>
                        @endif
                        @if(!empty($payment->payment_info['bank_name']) || !empty($payment->payment_info['account_number']) || !empty($payment->payment_info['account_name']))
                            <tr>
                                <th>Bank Name</th>
                                <td>{{ $payment->payment_info['bank_name'] ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Account Number</th>
                                <td>{{ $payment->payment_info['account_number'] ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Account Name</th>
                                <td>{{ $payment->payment_info['account_name'] ?? 'N/A' }}</td>
                            </tr>
                        @endif
                        @if(!empty($payment->payment_info['cash_received_by']))
                            <tr>
                                <th>Cash Received By</th>
                                <td>{{ $payment->payment_info['cash_received_by'] }}</td>
                            </tr>
                        @endif
                        @if($payment->paymentmethod_id === 8)
                            <tr>
                                <th>Date of Issued</th>
                                <td>{{ $payment->payment_info['date_of_issued'] ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Cheque Date</th>
                                <td>{{ $payment->payment_info['cheque_date'] ?? 'N/A' }}</td>
                            </tr>
                        @endif
                        <tr>
                            <th>Payment Date</th>
                            <td>{{ eng_str_date($payment->payment_date) }}</td>
                        </tr>
                        <tr>
                            <th>Remark</th>
                            <td>{{ $payment->remark ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Created By</th>
                            <td>{{ $payment->user->name ?? 'N/A' }}</td>
                        </tr>
                        </tbody>
                    </table>

                    @if($payment->paymentmethod_id === 8 && ($payment->payment_info['status'] ?? 'Pending') === 'Pending' and userCanView('supplier.payment.approve_supplier_cheque_payment'))
                        @if(userCanView('supplier.payment.approve_supplier_cheque_payment') || userCanView('supplier.payment.decline_supplier_cheque_payment'))
                            <div class="mt-4 p-3 bg-light rounded text-center">
                                <h5 class="mb-3 text-warning"><i class="bx bx-info-circle me-1"></i> Cheque Approval Action
                                    Required</h5>
                                @if(userCanView('supplier.payment.approve_supplier_cheque_payment'))
                                    <button wire:click="approve" class="btn btn-success btn-lg me-3"
                                            onclick="return confirm('Are you sure you want to approve this cheque payment?');">
                                        <i class="fa fa-check me-1"></i> Approve Payment
                                    </button>
                                @endif
                                @if(userCanView('supplier.payment.decline_supplier_cheque_payment'))
                                    <button wire:click="decline" class="btn btn-danger btn-lg"
                                            onclick="return confirm('Are you sure you want to decline this cheque payment?');">
                                        <i class="fa fa-times me-1"></i> Decline Payment
                                    </button>
                                @endif
                            </div>
                        @endif

                    @endif

                    @if($payment->isStockForStock() && !$payment->isReversed())
                        <div class="mt-4 p-3 bg-light rounded text-center border">
                            <h6 class="mb-2 text-dark"><i class="bx bx-undo me-1"></i> Stock Exchange Allocation Reversal</h6>
                            <p class="small text-muted mb-3">
                                Reversing this allocation will restore <strong>₦{{ money($payment->amount) }}</strong> to the stock exchange invoice's available balance and the Purchase Order's outstanding balance without modifying inventory quantities.
                            </p>
                            <button type="button"
                                    wire:click="reverseAllocation"
                                    class="btn btn-danger"
                                    onclick="confirm('Are you sure you want to reverse this stock exchange payment allocation?') || event.stopImmediatePropagation()">
                                <i class="fa fa-undo me-1"></i> Reverse Stock Exchange Allocation
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>