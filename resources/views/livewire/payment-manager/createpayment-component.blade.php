<div x-data="createPayment">
    <div class="row">
        @if(userCanView('payment.createInvoicePayment'))
            <div class="col-7 offset-3">
                <div class="card">
                    <div class="card-body">
                        <div class="card-sales-split">
                            <h2>Add Invoice Payment</h2>
                        </div>
                        <div class="col-12">
                            <p>Enter Invoice Number and make payment
                            </p>
                            <br/>
                            <div class="form-group">
                                <label for="credit_payment">Enter Invoice Number</label>
                                <input type="text" wire:model="invoice_number" x-model="invoice_number" class="form-control input-lg d-block" name="invoice_number" placeholder="Enter Invoice Number">
                            </div>
                            <button  x-on:click="generateInvoicePayment" wire:target="createInvoicePayment" wire:loading.attr="disabled" class="btn btn-primary btn-lg d-block mt-3" style="width: 100%">Continue

                                <i wire:loading.remove wire:target="createInvoicePayment" class="fa fa-angle-double-right"></i>
                                <span wire:loading wire:target="createInvoicePayment" class="spinner-border spinner-border-sm me-2" role="status"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        @if(userCanView('payment.createCreditPayment'))
            <div class="col-7 offset-3">
                <div class="card">
                    <div class="card-body">
                        <div class="card-sales-split">
                            <h2>Add Credit Payment</h2>
                        </div>
                        <div class="col-12">
                            <p>Search and select customer, add amount the customer wants to pay, click on generate payment to  make credit payment </p>

                            <div class="row">

                                <div class="col-6" style="position:relative;">
                                    <div class="form-group">
                                        <label for="credit_payment">Search for Customer</label>
                                        <input type="text"  class="form-control input-lg d-block" name="invoice_number" x-model="searchCustomerString" x-on:keyup.debounce="searchCustomer(this.value)" placeholder="Search for customer by phone number, name or email address">
                                    </div>
                                    <template x-if="(searchCustomers.length > 0)">
                                        <div  class="np-result-container" style="margin-top: -20px">
                                            <template x-for="customer in searchCustomers">
                                                <div  x-on:click="selectCus(customer)">
                                                    <div class="np-result-item">
                                                        <div class="np-ib np-text-container">
                                                            <div x-text="(customer.firstname)+ ' '+(customer.lastname)"></div>
                                                            <div class="np-result-description">
                                                                Phone Number : <span x-html="customer.phone_number"></span>
                                                                &nbsp; &nbsp;
                                                                Company Name : <span x-text="customer.company_name"></span>
                                                                &nbsp; &nbsp;
                                                                <span x-text="customer.address"></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="customer_id.firstname !== ''">
                                        <address class="bg-success text-white rounded-3 bg-gradient mt-2 p-2" style="font-size: 12px;">
                                            <h6 style="font-size: 12px;">Selected Customer :</h6>
                                            <span class="d-block"><b x-html="customer_id.firstname +' '+customer_id.lastname"></b></span>
                                            <span class="d-block" x-text="customer_id.company_name"></span>
                                            <span class="d-block" x-text="customer_id.email"></span>  <span x-text="customer_id.phone_number"></span>
                                        </address>
                                    </template>
                                </div>

                                <div class="col-6" >
                                    <div class="form-group">
                                        <label for="credit_payment">Enter Amount</label>
                                        <input type="text" wire:model="amount" x-model="amount" class="form-control input-lg d-block" name="invoice_number" placeholder="Enter Amount">
                                    </div>
                                </div>

                            </div>

                            <button  x-on:click="generateCreditPayment"  wire:target="createCreditPayment" wire:loading.attr="disabled"  class="btn btn-success btn-lg d-block mt-3" style="width: 100%">Generate Payment

                                <i wire:loading.remove wire:target="createCreditPayment" class="fa fa-redo"></i>

                                <span wire:loading wire:target="createCreditPayment" class="spinner-border spinner-border-sm me-2" role="status"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if(userCanView('payment.createDepositPayment'))
                <div class="col-7 offset-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-sales-split">
                                <h2>Add Deposit Payment</h2>
                            </div>
                            <div class="col-12">
                                <p>Search and select customer, add amount the customer wants to deposit, click on generate payment to  make deposit payment </p>

                                <div class="row">

                                    <div class="col-6" style="position:relative;">
                                        <div class="form-group">
                                            <label for="deposit_payment">Search for Customer</label>
                                            <input type="text"  class="form-control input-lg d-block" name="invoice_number" x-model="deposit_searchCustomerString" x-on:keyup.debounce="deposit_searchCustomer(this.value)" placeholder="Search for customer by phone number, name or email address">
                                        </div>
                                        <template x-if="(deposit_searchCustomers.length > 0)">
                                            <div  class="np-result-container" style="margin-top: -20px">
                                                <template x-for="customer in deposit_searchCustomers">
                                                    <div  x-on:click="deposit_selectCus(customer)">
                                                        <div class="np-result-item">
                                                            <div class="np-ib np-text-container">
                                                                <div x-text="(customer.firstname)+ ' '+(customer.lastname)"></div>
                                                                <div class="np-result-description">
                                                                    Phone Number : <span x-html="customer.phone_number"></span>
                                                                    &nbsp; &nbsp;
                                                                    Company Name : <span x-text="customer.company_name"></span>
                                                                    &nbsp; &nbsp;
                                                                    <span x-text="customer.address"></span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                        <template x-if="deposit_customer_id.firstname !== ''">
                                            <address class="bg-success text-white rounded-3 bg-gradient mt-2 p-2" style="font-size: 12px;">
                                                <h6 style="font-size: 12px;">Selected Customer :</h6>
                                                <span class="d-block"><b x-html="deposit_customer_id.firstname +' '+deposit_customer_id.lastname"></b></span>
                                                <span class="d-block" x-text="deposit_customer_id.company_name"></span>
                                                <span class="d-block" x-text="deposit_customer_id.email"></span>  <span x-text="deposit_customer_id.phone_number"></span>
                                            </address>
                                        </template>
                                    </div>

                                    <div class="col-6" >
                                        <div class="form-group">
                                            <label for="deposit_payment">Enter Deposit Amount</label>
                                            <input type="text" wire:model="deposit_amount" x-model="deposit_amount" class="form-control input-lg d-block" name="invoice_number" placeholder="Enter Amount">
                                        </div>
                                    </div>

                                </div>

                                <button  x-on:click="generateDepositPayment"  wire:target="createDepositPayment" wire:loading.attr="disabled"  class="btn btn-dark btn-lg d-block mt-3" style="width: 100%">Generate Deposit

                                    <i wire:loading.remove wire:target="createDepositPayment" class="fa fa-redo"></i>

                                    <span wire:loading wire:target="createDepositPayment" class="spinner-border spinner-border-sm me-2" role="status"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
        @endif
    </div>



    @if(userCanView('payment.createCreditPayment'))
        <div  class="modal fade" id="creditPaymentModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered" role="document">

                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Credit Payment</h5>
                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">

                        <livewire:payment-manager.addcredit-component wire:key="222"/>

                    </div>
                </div>
            </div>
        </div>
    @endif
    @if(userCanView('payment.createInvoicePayment'))
        <div  class="modal fade" id="invoicePaymentModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered" role="document">

                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Invoice Payment</h5>
                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">

                        <livewire:payment-manager.addpayment-component wire:key="1111"   :invoice="$this->invoice"/>

                    </div>
                </div>
            </div>
        </div>
    @endif
    @if(userCanView('payment.createDepositPayment'))
        <div  class="modal fade" id="depositPaymentModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered" role="document">

                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Deposit Payment</h5>
                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">

                        <livewire:payment-manager.adddeposit-component wire:key="333"/>

                    </div>
                </div>
            </div>
        </div>
    @endif
    <script>
        window.onload = function (){
            let creditPaymentModal = "";
            let invoicePaymentModal = "";
            let depositPaymentModal = "";
            $(document).ready(function(){
                @if(userCanView('payment.createCreditPayment'))
                    creditPaymentModal = new bootstrap.Modal(document.getElementById("creditPaymentModal"), {
                    'backdrop' : 'static',
                    'keyboard' : false,
                    'focus' : true
                });
                @endif
                @if(userCanView('payment.createInvoicePayment'))
                    invoicePaymentModal = new bootstrap.Modal(document.getElementById("invoicePaymentModal"), {
                    'backdrop' : 'static',
                    'keyboard' : false,
                    'focus' : true
                });
                @endif

               @if(userCanView('payment.createDepositPayment'))
                    depositPaymentModal = new bootstrap.Modal(document.getElementById("depositPaymentModal"), {
                    'backdrop' : 'static',
                    'keyboard' : false,
                    'focus' : true
                });
                @endif

            });
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

        function createPayment()
        {
            return {
                customer_id : {"firstname" : ""},
                searchCustomers : [],
                searchCustomerString : "",
                invoice_number : "",
                amount : "",

                deposit_searchCustomers : [],
                deposit_searchCustomerString : "",
                deposit_amount : "",
                deposit_customer_id : {"firstname" : ""},

                async searchCustomer()
                {
                    if (this.searchCustomerString !== "" && this.searchCustomerString.length > 3) {
                        this.searchCustomers = await (await fetch('{{ route('findcustomer') }}?query=' + this.searchCustomerString
                        )).
                        json();
                    }
                    else{
                        this.searchCustomers = [];
                    }
                },
                async deposit_searchCustomer()
                {
                    if (this.deposit_searchCustomerString !== "" && this.deposit_searchCustomerString.length > 3) {
                        this.deposit_searchCustomers = await (await fetch('{{ route('findcustomer') }}?query=' + this.deposit_searchCustomerString
                        )).
                        json();
                    }
                    else{
                        this.deposit_searchCustomers = [];
                    }
                },

                selectCus(customer)
                {
                    this.customer_id = customer;
                    this.searchCustomerString = "";
                    this.searchCustomers = [];
                    @this.set('customer_id', customer.id, true);
                },
                deposit_selectCus(customer)
                {
                    this.deposit_customer_id = customer;
                    this.deposit_searchCustomerString = "";
                    this.deposit_searchCustomers = [];
                    @this.set('deposit_customer_id', customer.id, true);
                },

                generateDepositPayment(){
                    if(this.deposit_customer_id.firstname === "")
                    {
                        alert("Please select a customer to generate deposit payment");
                        return false;
                    }

                    if(this.deposit_amount === "")
                    {
                        alert("Please enter the amount the customer wants to deposit");
                        return false;
                    }

                    @this.createDepositPayment();
                },

                generateCreditPayment(){
                    if(this.customer_id.firstname === "")
                    {
                        alert("Please select a customer to generate credit payment");
                        return false;
                    }

                    if(this.amount === "")
                    {
                        alert("Please enter the amount the customer wants to pay");
                        return false;
                    }

                    @this.createCreditPayment();
                },
                generateInvoicePayment(){
                    if(this.invoice_number === "")
                    {
                        alert("Please enter the invoice number");
                        return false;
                    }
                    @this.set('invoice_number', this.invoice_number, true);
                    @this.createInvoicePayment();
                }

            }
        }


    </script>
</div>
