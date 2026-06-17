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
                                if(isset($payment->supplier->supplierStockOpening)) {
                                    $amountToPay = $payment->supplier->supplierStockOpening->total_supplier_outstanding + ($payment->supplier->supplierStockOpening->total_opening_cost_price+$payment->supplier->supplierStockOpening->total_opening_retail_cost_price);
                                }
                        @endphp
                        <tr>
                            <th>Amount To Pay</th>
                            <td>
                                <h4 class="text-primary mb-0">{{ $amountToPay ?  money($amountToPay) : "N/A" }}</h4>
                            </td>
                        </tr>
                        <tr>
                            <th>Payment Method</th>
                            <td>
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
                            </td>
                        </tr>
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
                        <div class="mt-4 p-3 bg-light rounded text-center">
                            <h5 class="mb-3 text-warning"><i class="bx bx-info-circle me-1"></i> Cheque Approval Action
                                Required</h5>
                            <button wire:click="approve" class="btn btn-success btn-lg me-3"
                                    onclick="return confirm('Are you sure you want to approve this cheque payment?');">
                                <i class="fa fa-check me-1"></i> Approve Payment
                            </button>
                            <button wire:click="decline" class="btn btn-danger btn-lg"
                                    onclick="return confirm('Are you sure you want to decline this cheque payment?');">
                                <i class="fa fa-times me-1"></i> Decline Payment
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>