@extends('layouts.app')

@section('pageHeaderTitle1', $title)
@section('pageHeaderDescription', $subtitle)
@section('css')
    <link rel="stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}">
    <style>
        .np-title {
            margin-left: 20px;
            margin-top: 30px;
            font-size: 18px;
            color: rgb(0, 64, 255);
        }

        .np-input-search:hover {
            background: rgb(225, 225, 225);
            transition: all 0.4s;
        }
        .np-result-container {
            text-align: left;
            position: absolute;
            width: 100%;
            border-radius: 4px;
            background-color: #fff;
            z-index: 1000000;
            box-shadow: 0px 1px 6px 1px rgb(0 0 0 / 40%);
        }
        .np-result-item {
            width: 100%;
            border: 1px solid #eee;
            padding: 4px 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .np-result-item:hover {
            background: #eee;
            transition: all 0.3s;
        }
        .np-result-description {
            font-size: 11px;
        }
        .np-ib {
            display: inline-block;
        }

        .np-text-container {
            width: 100%;
            vertical-align: top;
            padding-left: 5px;
            color: black;
        }


        .np-result-details-title {
            font-size: 20px;
            padding: 8px 0px;
            font-weight: 500;
        }
        .np-result-details-description {
            font-size: 16px;
        }
    </style>
@endsection

@section('js')
    <script src="{{ asset('plugins/select2/js/select2.min.js') }}"></script>

    <script>

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

                },
                deposit_selectCus(customer)
                {
                    this.deposit_customer_id = customer;
                    this.deposit_searchCustomerString = "";
                    this.deposit_searchCustomers = [];

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

                },
                generateInvoicePayment(){
                    if(this.invoice_number === "")
                    {
                        alert("Please enter the invoice number");
                        return false;
                    }

                },
                select2()
                {
                    var path = '{{ route('findcustomer') }}'+"?column={{ '' }}&select2=yes"
                    var obj = this;
                    var select =  $('#select2').select2({
                        placeholder: 'Select for Customer',
                        ajax: {
                            url: path,
                            dataType: 'json',
                            delay: 250,
                            data: function (data) {
                                return {
                                    searchTerm: data.term // search term
                                };
                            },
                            processResults: function (response) {
                                return {
                                    results:response
                                };
                            },
                        }
                    })

                    $('#select2').on('change',function(eventData){
                        var data = $(this).select2('data');
                        obj.stock_id = data[0].id;
                        obj.cost_price = data[0].cost_price;
                        obj.quantity = 1;
                        obj.name = data.name;
                        obj.selectStock = data[0];
                    });

                },

            }
        }

    </script>
@endsection

@section('content')

    <div class="row" x-data="createPayment">
        <div class="col-6">
            <div class="card">
                <form action="{{ route('payment.createInvoicePayment') }}" method="post">
                    @csrf
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
                            <input type="text"  class="form-control input-lg d-block" name="invoice_number" placeholder="Enter Invoice Number">
                        </div>
                        <button   class="btn btn-primary btn-lg d-block mt-3" style="width: 100%">Continue
                            <i class="fa fa-angle-double-right"></i>
                        </button>
                    </div>
                </div>
                </form>
            </div>
        </div>
        <div class="col-6">
            <div class="card">
                <form action="{{ route('payment.createCreditPayment') }}" method="post">
                    @csrf
                <div class="card-body">
                    <div class="card-sales-split">
                        <h2>Add Credit Payment</h2>
                    </div>
                    <div class="col-12">
                        <p>Search and select customer, add amount the customer wants to pay, click on generate payment to  make credit payment </p>

                        <div class="row">

                            <div class="col-6" style="position:relative;">
                                <div class="form-group">
                                    <label for="credit_payment">Select  Customer</label>
                                    <select id="select2" name="customer_id" x-init="select2" x-model="stock_id" class="form-control">
                                        <option value="">-Select One-</option>
                                    </select>
                                </div>

                            </div>

                            <div class="col-6" >
                                <div class="form-group">
                                    <label for="credit_payment">Enter Amount</label>
                                    <input type="text" wire:model="amount" x-model="amount"  class="form-control input-lg d-block" name="amount" placeholder="Enter Amount">
                                </div>
                            </div>

                        </div>

                        <button type="submit"  class="btn btn-success btn-lg d-block mt-3" style="width: 100%">Generate Payment
                        </button>
                    </div>
                </div>
                </form>
            </div>
        </div>
    </div>

@endsection
