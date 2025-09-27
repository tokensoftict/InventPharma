<div x-data="addPayment">
    <div class="row">
        <div class="col-6 text-left border-end">
            <h5><strong>Invoice ID #{{ $this->invoice->id }}</strong></h5>
            <hr/>
            <div class="mb-3">
                <span><strong>Invoice Date</strong></span>
                <span class="form-control"><strong>{{ convert_date($this->invoice->invoice_date) }}</strong></span>
            </div>
            <div class="mb-3">
                <span><strong>Sales Representative</strong></span><br/>
                <span class="form-control"><strong>{{ $this->invoice?->create_by?->name }}</strong></span>
            </div>
            <h5><strong>Bill To</strong> </h5>
            <hr/>
            <address class="pt-1 mb-3">
                <strong>CUSTOMER:</strong><br>
                <span class="d-block pb-1 pt-1"> Name :  <strong>{{ $this->invoice->customer->firstname ?? "" }} {{ $this->invoice->customer->lastname ?? "" }}</strong></span>
                <span class="d-block pb-1"> Phone Number: <strong>{{ $this->invoice->customer->phone_number ?? "" }}</strong></span>
                <span class="d-block pb-1"> Address : <strong>{{ $this->invoice->customer->address ?? "" }}</strong></span>
            </address>
            <br/>
            <div class="mb-3">
                <span><strong>Invoice Sub Total</strong></span><br/>
                <span class="form-control"><strong>{{ money($this->invoice->sub_total) }}</strong></span>
            </div>

            <div class="mb-3">
                <span><strong>Invoice Discount</strong></span><br/>
                <span class="form-control"><strong>{{ money($this->invoice->discount_amount) }}</strong></span>
            </div>

            <div class="mb-3">
                <span><strong>Invoice Total</strong></span><br/>
                <span class="form-control"><strong>{{ money($this->invoice->sub_total - $this->invoice->discount_amount) }}</strong></span>
            </div>

        </div>
        <div class="col-6">
            <h5><strong>Payment Information</strong></h5>
            <hr/>
            <br/>
            @php
                //->filter(function($item) { return !in_array($item->id, [7,4,8]);})
            @endphp
            <div class="mb-3" wire:ignore>
                <span class="d-block text-center" style="font-size: 18px"><strong>Select Payment Method</strong></span>
                <select class="select mt-1 form-control form-control-lg" id="payment_method{{  $this->select2key }}"  wire:model.live="payment_method">
                    <option value="">Select Payment Method</option>
                    @foreach($this->payments  as $payment)
                        <option value="{{ $payment->id }}">{{ $payment->name }}</option>
                    @endforeach
                </select>
            </div>

            @if($this->payment_method === "1")

                <div class="mb-3">
                    <span class="d-block text-center" style="font-size: 18px">Cash Tendered</span>
                    <input type="number" placeholder="Cash Tendered" class="form-control-lg form-control" wire:model.live.debounce="cash_tendered" step="0.0000000001">
                </div>
                <label>Customer Change</label>
                <div class="bg-primary p-2 rounded-3 text-center d-block">
                    <strong  class="text-white" style="font-size: 29px">{{ money($this->change) }}</strong>
                </div>

            @endif

            @if($this->payment_method === "3" || $this->payment_method === "2")
                <div class="mb-3">
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
                <label class="font-size-15 d-block text-center">Current Credit Amount</label>
                <div class="bg-primary p-2 rounded-3 text-center d-block">
                    <strong  class="text-white" style="font-size: 29px">{{ money($this->totalCredit) }}</strong>
                </div>
            @endif


            @if($this->payment_method === "5")
                <label class="font-size-15 d-block text-center">Current Total Deposit</label>
                <div class="bg-success p-2 rounded-3 text-center d-block">
                    <strong  class="text-white" style="font-size: 29px">{{ money($this->totalDeposit) }}</strong>
                </div>
                @if($this->error_deposit !== "")
                    <span class="text-danger">{{ $this->error_deposit }}</span>
                @endif
            @endif

            @if($this->payment_method === "8")
                <div class="mb-3">
                    <span class="d-block text-center"   style="font-size: 18px">Cheque Date</span>
                    <input type="date" class="form-control-lg form-control" wire:model.live="cheque_date" id="cheque_date" placeholder="Date Written on Cheque">
                </div>
                <div class="mb-3">
                    <span class="d-block text-center"   style="font-size: 18px">Select Bank</span>
                    <select class="select form-control form-control-lg" wire:model.live="bank" id="bank" >
                        <option value="">Select Bank</option>
                        @foreach($this->banks as $bank)
                            <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <span class="d-block text-center"   style="font-size: 18px">Cheque Comment</span>
                    <textarea name="comment"  id="comment" wire:model.live.debounce="comment"  cols="30" rows="5" class="form-control form-control-lg"></textarea>
                </div>
            @endif


            @if($this->payment_method === "6")

                <table class="table table-bordered table-striped">
                    @foreach($this->payments->filter(function($item){ return $item->id !== 7; }) as $payment)
                        @if($payment->id !=6)
                            <tr>
                                <th>{{ $payment->name }}</th>
                                <th><input class="form-control form-control-lg" wire:model.live.debounce="split_payments.{{ $payment->id }}.amount"></th>
                                <th>
                                    @if(in_array($payment->id,[2,3]))
                                        <select class="select form-control form-control-lg" wire:model.live="split_payments.{{ $payment->id }}.bank_account_id">
                                            <option value="">Select Bank Account</option>
                                            @foreach($this->bankAccounts as $account)
                                                <option value="{{ $account->id }}">{{ $account->account_name }} ( {{ $account->account_number }})</option>
                                            @endforeach
                                        </select>
                                    @endif

                                    @if(in_array($payment->id,[8]))
                                        <select class="select form-control form-control-lg" wire:model.live="split_payments.{{ $payment->id }}.bank">
                                            <option value="">Select Bank</option>
                                            @foreach($this->banks as $bank)
                                                <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                                            @endforeach
                                        </select>
                                        <br/>
                                        <input type="date" class="form-control form-control-lg" placeholder="Cheque Date" wire:model.live="split_payments.{{ $payment->id }}.cheque_date">
                                        <br/>

                                    @endif

                                    @if($payment->id === 5)
                                        @if($this->error_deposit !== "")
                                            <span class="text-danger">{{ $this->error_deposit }}</span>
                                        @endif
                                    @endif
                                </th>
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
            <button wire:click="savePayment" wire:target="savePayment" wire:loading.attr="disabled" style="width: 100%"   {{ $this->btnEnabled ===true ? '' : 'disabled="disabled"' }} class="btn btn-primary btn-lg d-block bottom-100">
                Save Payment
                <i wire:loading.remove wire:target="savePayment" class="fa fa-check"></i>
                <span wire:loading wire:target="savePayment" class="spinner-border spinner-border-sm me-2" role="status"></span>
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
            };
        }
    </script>
</div>
