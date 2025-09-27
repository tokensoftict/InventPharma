<div>

    <div class="row">
        <div class="col-12">
            <div class="row">
                <div class="col-4">
                    <h5>
                        <strong>Payment ID #{{ $this->payment->id }}</strong>
                    </h5>
                </div>
                <div class="col-8">
                    <div class="float-end">
                        @if(userCanView('payment.print'))
                            <a href="{{ route('payment.print', $this->payment->id) }}" class="btn btn-success print float-end">Print Payment Receipt
                                <i class="fa fa-print"></i>
                            </a>
                        @endif
                        <!--
                        @if(userCanView('payment.destroy'))
                            <button onclick="confirm('Are you sure you want to delete this payment, stock removed will be return and invoice will be save as draft  ?') || event.stopImmediatePropagation()" wire:loading.attr="disabled" wire:click="deletePayment" style="margin-right: 20px"  class="btn btn-primary  float-end">
                                Delete Payment
                                <span wire:loading wire:target="deletePayment" class="spinner-border spinner-border-sm me-2" role="status"></span>
                                <i wire:target="deletePayment" wire:loading.remove class="fa fa-trash"></i>
                            </button>
                        @endif
                            -->
                    </div>
                </div>
            </div>
            <hr/>
        </div>
        <div class="row">
            <div class="col-6">
                <h5><strong>CUSTOMER:</strong> </h5>
                <address class="pt-1">
                    <span class="d-block pb-1 pt-1"> Name :  <strong>{{ $this->payment->customer->firstname ?? "" }} {{ $this->payment->customer->lastname ?? "" }}</strong></span>
                    <span class="d-block pb-1">Company : <strong>{{ $this->payment->customer->company_name ?? "" }}</strong></span>
                    <span class="d-block pb-1"> Phone Number: <strong>{{ $this->payment->customer->phone_number ?? "" }}</strong></span>
                    <span class="d-block pb-1"> Address : <strong>{{ $this->payment->customer->address ?? "" }}</strong></span>
                </address>
                <br/>
                <div class="mb-3">
                    <span><strong>Payment Date and Time :</strong></span><br/>
                    <span class="form-control"><strong>{{ eng_str_date($this->payment->payment_date)  }} - {{ twelveHourClock($this->payment->payment_time) }}</strong></span>
                </div>
            </div>
            <div class="col-6">
                <div class="mb-3">
                    <span><strong>Payment Type</strong></span><br/>
                    <span class="form-control"><strong>{{ \App\Livewire\PaymentManager\Datatable\PaymentListDatatable::$invoiceType[$this->payment->invoice_type] }}</strong></span>
                </div>

                <div class="mb-3">
                    <span><strong>Payment Received By :</strong></span><br/>
                    <span class="form-control"><strong>{{ $this->payment->user->name }}</strong></span>
                </div>
                <div class="mb-3">
                    <span><strong>Payment Method :</strong></span><br/>
                    @if($this->payment->paymentmethoditems->count() > 1)
                        <span class="form-control"><strong>SPLIT PAYMENT METHOD</strong></span>
                    @else
                        <span class="form-control"><strong>{{ $this->payment->paymentmethoditems->first()->paymentmethod->name }}</strong></span>
                    @endif
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <span class="d-block  text-center"  style="font-weight: bolder; font-size: 14px">Total Amount Paid</span>
                <span class="d-block bg-success mt-2 pt-3 pb-3 rounded-1 text-white text-center" style="font-weight: bolder; font-size: 30px">{{ money($this->payment->total_paid) }}</span>
            </div>
        </div>

        <div class="row mt-2">

            <div class="col-12 border-top border-bottom ">
                <h5 class="pt-2 pb-2"><strong>Payment Analysis</strong></h5>
                <br/>
                <table class="table table-bordered table-striped">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Payment Method</th>
                        <th>Amount</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($this->payment->paymentmethoditems as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->paymentmethod->name }}</td>
                            <td>{{ money($item->amount) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                    <tr>
                        <th></th>
                        <th class="text-end">Total</th>
                        <th style="font-weight: bolder;">{{ money($this->payment->paymentmethoditems->sum('amount')) }}</th>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>


</div>
