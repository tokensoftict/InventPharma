<div x-data="addPayment">
    <style>
        .pay-card { border: 0; border-radius: 16px; box-shadow: 0 4px 24px rgba(30, 41, 59, .08); overflow: hidden; }
        .pay-summary-head { background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: #fff; padding: 22px 24px; }
        .pay-summary-head small { opacity: .8; letter-spacing: .06em; text-transform: uppercase; font-size: 11px; }
        .pay-summary-total { font-size: 36px; font-weight: 800; line-height: 1.1; margin-top: 4px; }
        .pay-line { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px dashed #e5e7eb; font-size: 14px; }
        .pay-line:last-child { border-bottom: 0; }
        .pay-line span:first-child { color: #6b7280; }
        .pay-line strong { color: #111827; text-align: right; }
        .pay-section-title { font-size: 13px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #6b7280; margin-bottom: 12px; }

        .pm-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 14px; }
        .pm-tile { position: relative; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px;
            min-height: 112px; padding: 14px 10px; border: 2px solid #e5e7eb; border-radius: 16px; background: #fff; cursor: pointer;
            transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease, background .15s ease; text-align: center; }
        .pm-tile:hover { transform: translateY(-3px); border-color: #a5b4fc; box-shadow: 0 8px 20px rgba(79, 70, 229, .15); }
        .pm-tile:active { transform: translateY(0) scale(.98); }
        .pm-icon { font-size: 34px; line-height: 1; }
        .pm-name { font-weight: 700; font-size: 14px; color: #1f2937; letter-spacing: .02em; }
        .pm-tile.active { border-color: #4f46e5; background: linear-gradient(180deg, #eef2ff 0%, #fff 100%); box-shadow: 0 8px 22px rgba(79, 70, 229, .22); }
        .pm-tile.active .pm-name { color: #4338ca; }
        .pm-tile.active::after { content: '\2713'; position: absolute; top: 8px; right: 8px; width: 22px; height: 22px; border-radius: 50%;
            background: #4f46e5; color: #fff; font-size: 13px; font-weight: 700; display: flex; align-items: center; justify-content: center; }

        .pay-form { background: #f9fafb; border-radius: 14px; padding: 18px; margin-top: 18px; }
        .pay-form label.pay-label { display: block; font-size: 13px; font-weight: 600; color: #4b5563; margin-bottom: 6px; }
        .pay-hero { border-radius: 14px; padding: 14px; text-align: center; color: #fff; }
        .pay-hero strong { font-size: 30px; font-weight: 800; }
        .pay-hero.primary { background: linear-gradient(135deg, #4f46e5, #6366f1); }
        .pay-hero.success { background: linear-gradient(135deg, #059669, #10b981); }
        .pay-save { border: 0; border-radius: 14px; padding: 16px; font-size: 18px; font-weight: 700; letter-spacing: .03em;
            background: linear-gradient(135deg, #4f46e5, #7c3aed); color: #fff; transition: opacity .15s, transform .15s; }
        .pay-save:hover:not([disabled]) { transform: translateY(-1px); color: #fff; }
        .pay-save[disabled] { opacity: .45; cursor: not-allowed; }
    </style>

    @php
        $pmIcons = [1 => '💵', 2 => '💳', 3 => '🏦', 4 => '🧾', 5 => '👛', 6 => '🔀', 7 => '🌐', 8 => '📝', 9 => '📱'];
        $invoiceTotal = $this->invoice->sub_total - ($this->invoice->discount_amount + $this->invoice->membership_discount);
    @endphp

    <div class="row g-4">
        {{-- ============ LEFT: invoice summary ============ --}}
        <div class="col-lg-5">
            <div class="card pay-card h-100">
                <div class="pay-summary-head">
                    <small>Invoice #{{ $this->invoice->id }}</small>
                    <div class="pay-summary-total">{{ money($invoiceTotal) }}</div>
                    <small>Total amount due</small>
                </div>
                <div class="card-body p-4">
                    <div class="pay-section-title">Invoice</div>
                    <div class="pay-line"><span>Invoice Date</span><strong>{{ convert_date($this->invoice->invoice_date) }}</strong></div>
                    <div class="pay-line"><span>Sales Representative</span><strong>{{ $this->invoice?->create_by?->name }}</strong></div>

                    <div class="pay-section-title mt-4">Bill To</div>
                    <div class="pay-line"><span>Name</span><strong>{{ $this->invoice->customer->firstname ?? "" }} {{ $this->invoice->customer->lastname ?? "" }}</strong></div>
                    <div class="pay-line"><span>Phone</span><strong>{{ $this->invoice->customer->phone_number ?? "" }}</strong></div>
                    <div class="pay-line"><span>Address</span><strong>{{ $this->invoice->customer->address ?? "" }}</strong></div>

                    <div class="pay-section-title mt-4">Amount</div>
                    <div class="pay-line"><span>Sub Total</span><strong>{{ money($this->invoice->sub_total) }}</strong></div>
                    <div class="pay-line"><span>Discount</span><strong>{{ money($this->invoice->discount_amount) }}</strong></div>
                    @if($this->invoice->membership_discount > 0)
                        <div class="pay-line"><span>Membership Discount</span><strong>{{ money($this->invoice->membership_discount) }}</strong></div>
                    @endif
                    <div class="pay-line"><span><strong>Total</strong></span><strong style="font-size: 18px">{{ money($invoiceTotal) }}</strong></div>
                </div>
            </div>
        </div>

        {{-- ============ RIGHT: payment ============ --}}
        <div class="col-lg-7">
            <div class="card pay-card">
                <div class="card-body p-4">
                    <div class="pay-section-title">Select Payment Method</div>

                    <div class="pm-grid">
                        @foreach($this->payments as $payment)
                            <div class="pm-tile {{ $this->payment_method === (string) $payment->id ? 'active' : '' }}"
                                 wire:click="$set('payment_method', '{{ $payment->id }}')"
                                 role="button" tabindex="0">
                                <span class="pm-icon">{{ $pmIcons[$payment->id] ?? '💰' }}</span>
                                <span class="pm-name">{{ $payment->name }}</span>
                            </div>
                        @endforeach
                    </div>

                    @if($this->payment_method !== "")
                        <div class="pay-form">

                            {{-- CASH --}}
                            @if($this->payment_method === "1")
                                <div class="mb-3">
                                    <label class="pay-label">Cash Tendered</label>
                                    <input type="number" placeholder="Cash Tendered" class="form-control-lg form-control" wire:model.live.debounce="cash_tendered" step="0.0000000001">
                                </div>
                                <label class="pay-label">Customer Change</label>
                                <div class="pay-hero primary"><strong>{{ money($this->change) }}</strong></div>
                            @endif

                            {{-- POS / TRANSFER --}}
                            @if($this->payment_method === "3" || $this->payment_method === "2")
                                <div>
                                    <label class="pay-label">Select Bank Account</label>
                                    <select class="select form-control form-control-lg" wire:model.live="bank_account_id">
                                        <option value="">Select Bank Account</option>
                                        @foreach($this->bankAccounts as $account)
                                            <option value="{{ $account->id }}">{{ $account->account_name }} ( {{ $account->account_number }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            {{-- CREDIT --}}
                            @if($this->payment_method === "4")
                                <label class="pay-label text-center">Current Credit Amount</label>
                                <div class="pay-hero primary"><strong>{{ money($this->totalCredit) }}</strong></div>
                            @endif

                            {{-- DEPOSIT --}}
                            @if($this->payment_method === "5")
                                <label class="pay-label text-center">Current Total Deposit</label>
                                <div class="pay-hero success"><strong>{{ money($this->totalDeposit) }}</strong></div>
                                @if($this->error_deposit !== "")
                                    <span class="text-danger d-block mt-2">{{ $this->error_deposit }}</span>
                                @endif
                            @endif

                            {{-- CHEQUE --}}
                            @if($this->payment_method === "8")
                                <div class="mb-3">
                                    <label class="pay-label">Cheque Date</label>
                                    <input type="date" class="form-control-lg form-control" wire:model.live="cheque_date" id="cheque_date" placeholder="Date Written on Cheque">
                                </div>
                                <div class="mb-3">
                                    <label class="pay-label">Select Bank</label>
                                    <select class="select form-control form-control-lg" wire:model.live="bank" id="bank">
                                        <option value="">Select Bank</option>
                                        @foreach($this->banks as $bank)
                                            <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="pay-label">Cheque Comment</label>
                                    <textarea name="comment" id="comment" wire:model.live.debounce="comment" cols="30" rows="4" class="form-control form-control-lg"></textarea>
                                </div>
                            @endif

                            {{-- SPLIT --}}
                            @if($this->payment_method === "6")
                                <table class="table table-bordered table-striped bg-white mb-0">
                                    @foreach($this->payments->filter(function($item){ return !in_array($item->id, [7, 9]); }) as $payment)
                                        @if($payment->id != 6)
                                            <tr>
                                                <th class="align-middle">{{ $pmIcons[$payment->id] ?? '' }} {{ $payment->name }}</th>
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

                            {{-- OPAY POS --}}
                            @if($this->payment_method === "9")
                                @php($opayKobo = (int) round(((float) $this->sub_total) * 100))
                                <div class="text-center"
                                     data-opay-kobo="{{ $opayKobo }}"
                                     data-opay-invoice="{{ $this->invoice->invoice_number }}"
                                     data-opay-url="{{ config('printing.escpos.print_agent_url') }}"
                                     data-opay-token="{{ config('printing.escpos.print_agent_token') }}"
                                     id="opayPanel">
                                    <label class="pay-label">Amount to collect on OPay POS</label>
                                    <div class="pay-hero primary mb-3"><strong>{{ money($this->sub_total) }}</strong></div>

                                    @if($this->opay_approved)
                                        <div class="alert alert-success mb-2">
                                            <i class="fa fa-check-circle"></i> Payment approved
                                            @if(!empty($this->opay_data['cashier_method'])) ({{ $this->opay_data['cashier_method'] }}) @endif
                                            <br/><small>Ref: {{ $this->opay_data['transaction_number'] ?? '' }}</small>
                                        </div>
                                    @else
                                        <button type="button" class="btn btn-success btn-lg w-100" id="opayChargeBtn" onclick="opayCharge()">
                                            📱 Send to OPay POS
                                        </button>
                                    @endif
                                    <div id="opayStatus" class="mt-2" wire:ignore></div>
                                </div>
                            @endif

                        </div>
                    @endif

                    <button wire:click="savePayment" wire:target="savePayment" wire:loading.attr="disabled"
                            {{ $this->btnEnabled === true ? '' : 'disabled="disabled"' }}
                            class="btn pay-save w-100 mt-4">
                        Save Payment
                        <i wire:loading.remove wire:target="savePayment" class="fa fa-check ms-1"></i>
                        <span wire:loading wire:target="savePayment" class="spinner-border spinner-border-sm ms-2" role="status"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function opayStatus(msg, cls) {
            const el = document.getElementById('opayStatus');
            if (el) el.innerHTML = '<div class="alert alert-' + cls + ' mb-0">' + msg + '</div>';
        }

        async function opayCharge() {
            const panel = document.getElementById('opayPanel');
            const btn = document.getElementById('opayChargeBtn');
            if (!panel || !btn) return;

            const kobo = parseInt(panel.dataset.opayKobo, 10);
            if (!kobo || kobo <= 0) { opayStatus('Invalid amount.', 'danger'); return; }

            // Unique order number per attempt (OPay limit: 32 chars)
            const suffix = '-' + Date.now().toString(36);
            const orderNo = (panel.dataset.opayInvoice || 'INV').slice(0, 32 - suffix.length) + suffix;

            const agent = new EscPosPrintAgent(panel.dataset.opayUrl || 'http://127.0.0.1:9100');
            if (panel.dataset.opayToken) agent.setToken(panel.dataset.opayToken);

            btn.disabled = true;
            opayStatus('<span class="spinner-border spinner-border-sm me-2"></span>Waiting for customer to pay on the OPay terminal...', 'info');

            try {
                const result = await agent.posPurchase(kobo, orderNo);
                if (result.success) {
                    opayStatus('Approved. Finishing...', 'success');
                    @this.call('opayApproved', result);
                } else {
                    const labels = {cancelled: 'Cancelled on terminal', failed: 'Payment failed', pending: 'Payment still pending', timeout: 'Timed out waiting for the terminal'};
                    opayStatus((labels[result.status] || 'Not approved') + (result.message ? ': ' + result.message : '') + '. You can try again.', 'danger');
                    btn.disabled = false;
                }
            } catch (e) {
                opayStatus('Could not reach the OPay terminal via the Print Agent: ' + e.message, 'danger');
                btn.disabled = false;
            }
        }

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
