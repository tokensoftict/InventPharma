<div x-data="addPayment" x-init="init();">
    <div class="row">
        <div class="col-6 text-left border-end">
            <h5><strong>Credit Payment</strong></h5>
            <hr/>
            <div class="mb-3">
                <span><strong>Payment Date</strong></span>
                <span class="form-control"><strong>{{ convert_date(dailyDate()) }}</strong></span>
            </div>
            <div class="mb-3">
                <span><strong>Payment User</strong></span><br/>
                <span class="form-control"><strong>{{ auth()->user()->name }}</strong></span>
            </div>
            <h5><strong>Bill To</strong> </h5>
            <hr/>
            <address class="pt-1">
                <strong>CUSTOMER:</strong><br>
                <span class="d-block pb-1 pt-1"> Name :  <strong>{{ $this->customer->firstname ?? "" }} {{ $this->customer->lastname ?? "" }}</strong></span>
                <span class="d-block pb-1">Company : <strong>{{ $this->customer->company_name ?? "" }}</strong></span>
                <span class="d-block pb-1"> Phone Number: <strong>{{ $this->customer->phone_number ?? "" }}</strong></span>
                <span class="d-block pb-1"> Address : <strong>{{ $this->customer->address ?? "" }}</strong></span>
            </address>
            <br/>
            <div class="mb-3">
                <span><strong>Amount</strong></span><br/>
                <span class="form-control"><strong>{{ money($this->amount) }}</strong></span>
            </div>

        </div>
        <div class="col-6">
            <h5><strong>Payment Information</strong></h5>
            <hr/>
            @if($this->pendingOnlineInvoices->count() > 0)
                <div class="mb-3">
                    <span class="d-block text-center" style="font-size: 18px"><strong>Select Pending Online Invoice</strong></span>
                    <select class="form-control form-control-lg" wire:model="online_credit_invoice">
                        <option value="">Select Online Invoice</option>
                        @foreach($this->pendingOnlineInvoices as $invoice)
                            <option data-amt="{{ $invoice->total_amount_paid }}"  value="{{ $invoice->id }}">Invoice #{{ $invoice->id }} - {{ money($invoice->total_amount_paid) }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <br/>
            <div class="form-group" wire:ignore>
                <span class="d-block text-center" style="font-size: 18px"><strong>Select Payment Method</strong></span>
                <select class="select mt-1 form-control form-control-lg" id="payment_method{{  $this->select2key }}"  wire:model.live="payment_method">
                    <option value="">Select Payment Method</option>
                    @foreach($this->payments->filter(function($item){ return $item->id !== 8; }) as $payment)
                        <option value="{{ $payment->id }}">{{ $payment->name }}</option>
                    @endforeach
                </select>
            </div>

            @if($this->payment_method === "1")

                <div class="mb-3 mt-2">
                    <span class="d-block text-center" style="font-size: 18px">Cash Tendered</span>
                    <input type="number" placeholder="Cash Tendered" class="form-control-lg form-control" wire:model.live.debounce="cash_tendered" step="0.0000000001">
                </div>
                <label>Customer Change</label>
                <div class="bg-primary p-2 rounded-3 text-center d-block">
                    <strong  class="text-white" style="font-size: 29px">{{ money($this->change) }}</strong>
                </div>

            @endif

            @if($this->payment_method === "3" || $this->payment_method === "2")
                <div class="mb-3 mt-2">
                    <span class="d-block text-center"   style="font-size: 18px">Select Bank Account</span>
                    <select class="select form-control form-control-lg" wire:model.live="bank_account_id">
                        <option value="">Select Bank Account</option>
                        @foreach($this->bankAccounts as $account)
                            <option value="{{ $account->id }}">{{ $account->account_name }} ( {{ $account->account_number }})</option>
                        @endforeach
                    </select>
                </div>
            @endif


            @if($this->payment_method === "4")
                <label>Current Credit Amount</label>
                <div class="bg-primary p-2 rounded-3 text-center d-block">
                    <strong  class="text-white" style="font-size: 29px">{{ money($this->totalCredit) }}</strong>
                </div>
            @endif


            @if($this->payment_method === "5")
                <label>Total Deposit</label>
                <div class="bg-success p-2 rounded-3 text-center d-block">
                    <strong  class="text-white" style="font-size: 29px">{{ money(0) }}</strong>
                </div>
            @endif

            @if($this->payment_method === "6")

                <table class="table table-bordered table-striped mt-4">
                    @foreach($this->payments->filter(function($item){ return $item->id !== 8; }) as $payment)
                        @if($payment->id !=6)
                            <tr>
                                <td>{{ $payment->name }}</td>
                                <td><input class="form-control form-control-lg" wire:model.live.debounce="split_payments.{{ $payment->id }}.amount"></td>
                                <td>
                                    @if(in_array($payment->id,[2,3]))
                                        <select class="select form-control form-control-lg" wire:model.live="split_payments.{{ $payment->id }}.bank_account_id">
                                            <option value="">Select Bank Account</option>
                                            @foreach($this->bankAccounts as $account)
                                                <option value="{{ $account->id }}"> {{ $account->account_name }} ( {{ $account->account_number }})</option>
                                            @endforeach
                                        </select>
                                    @endif
                                </td>
                            </tr>
                        @endif
                    @endforeach
                    <tr>
                        <th></th>
                        <th class="text-end" style="font-size: 22px !important; font-weight: bolder">Total</th>
                        <td class="text-start" style="font-size: 22px !important; font-weight: bolder">{{ $this->totalSplitAmount }}</td>
                    </tr>
                </table>
            @endif


            <br/>  <br/>
            <button wire:click="saveCreditPayment" wire:target="saveCreditPayment" wire:loading.attr="disabled" style="width: 100%"   {{ $this->btnEnabled ===true ? '' : 'disabled="disabled"' }} class="btn btn-primary btn-lg d-block bottom-100">
                Save Payment
                <i wire:loading.remove wire:target="saveCreditPayment" class="fa fa-check"></i>
                <span wire:loading wire:target="saveCreditPayment" class="spinner-border spinner-border-sm me-2" role="status"></span>
            </button>

        </div>
    </div>
    <script>
        function addPayment()
        {
            return {
                select2Alpine(referred, model) {
                    this.select2 = $(document.getElementById(referred)).select2();
                    @this.set(model,this.select2.val());
                    this.select2.on("select2:select", (event) => {
                        @this.set(model,this.select2.val());
                    });

                },
                init()
                {
                    window.addEventListener('opencreditPaymentModal', (e) => {
                        creditPaymentModal.show();
                    });
                    window.addEventListener('clasecreditPaymentModal', (e) => {
                        creditPaymentModal.hide();
                    });

                    window.addEventListener('openinvoicePaymentModal', (e) => {
                        invoicePaymentModal.show();
                    });
                    window.addEventListener('claseinvoicePaymentModal', (e) => {
                        invoicePaymentModal.hide();
                    });

                    window.addEventListener('opendepositPaymentModal', (e) => {
                        depositPaymentModal.show();
                    });
                    window.addEventListener('closedepositPaymentModal', (e) => {
                        depositPaymentModal.hide();
                    });

                    window.addEventListener('openPaymentPage', (e) => {
                        window.location.href = e.detail[0].link
                    });
                }
            };
        }
    </script>
</div>
