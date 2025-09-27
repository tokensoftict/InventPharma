<div x-data="invoice()" x-init="totalInvoice(); newCustomerEvent(); getInputFromBarcode()">
    <div class="row">
        <div class="col-sm-8">
            <div class="card">
                <div class="card-body" style="position: relative;">
                    <div class="mb-3">
                        <label for="ac-demo">Search For Product or Scan Bar Code</label>
                        <input id="searchText" type="text" x-model="searchString" x-on:keyup.debounce="searchProduct(this.value)" placeholder="Product Barcode, Product Name..." class="form-control form-control-lg">

                    </div>
                    <template x-if="(searchproduct.length > 0)" >
                        <div  class="np-result-container">
                            <template x-for="product in searchproduct">
                                <div  x-on:click="selectProduct(product)">
                                    <div class="np-result-item">
                                        <div class="np-ib np-text-container">
                                            <div x-text="product.name"></div>
                                            <div class="np-result-description">
                                                <span class="font-size-13" style="font-weight: bolder"  >Price : <span class="text-danger"  :class="{'font-size-13':product.promo_selling_price > 0, 'slashed':product.promo_selling_price > 0}" x-html="money(product.selling_price)"></span></span>
                                                &nbsp; <span x-show="(product.promo_selling_price != null && product.promo_selling_price > 0)" style="font-weight: bolder" class="text-primary font-size-13"> <span  x-show="(product.promo_selling_price != null)" x-html="money(product.promo_selling_price)"></span></span>
                                                &nbsp; &nbsp;
                                                Quantity : <span x-text="product.quantity"></span>
                                                &nbsp; &nbsp;
                                                Box : <span x-text="product.box"></span>
                                                &nbsp; &nbsp;
                                                Carton : <span x-text="product.carton"></span>
                                                @if($this->d == "retail")
                                                    &nbsp; &nbsp;
                                                    SuperMarket Store : <span x-text="product.retail_store"></span>
                                                    &nbsp; &nbsp;
                                                    
                                                    <span x-show="(product.custom_prices.length > 0)" class="font-size-13" style="font-weight: bolder"  >Bundle Price : <span class="text-danger font-size-13"  x-html="money(product.custom_prices[0].price)+'&nbsp; = '+product.custom_prices[0].min_qty+' Quantity'"></span></span>
                                                    
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            <div class="card mt-2">
                <div class="card-body">
                    <div class="col-lg-12" style="height: 90vh;max-height: 100vh;overflow:auto">
                        <h4>Invoice Item(s)</h4>
                        <div class="table-responsive" >
                            <table class="table table-condensed table-bordered" style="font-size: 10px;">
                                <thead>
                                <tr>
                                    <th class="text-start" style="width: 35%;">Product</th>
                                    <th class="text-center" style="width: 15%;">Qty</th>
                                    <th class="text-center" style="width: 5%;">Box</th>
                                    <th class="text-center" style="width: 5%;">Carton</th>
                                    <th class="text-center" style="width: 5%;">Av. Qty</th>
                                    <th class="text-end" style="width: 7%;">Rate</th>
                                    <th class="text-end" style="width: 8%;">Total</th>
                                    <th class="text-end" style="width: 5%;">Action</th>
                                </tr>
                                </thead>
                                <tbody id="appender">
                                <template x-for="(item,index) in invoiceitems.reverse()" :key="item.stock_id">
                                    <tr>
                                        <td class="text-start">
                                            <span class="d-block"  x-text="item.name"></span>
                                            <span class="d-block text-danger" x-text="errors[item.stock_id]"></span>
                                        </td>
                                        <td class="text-center">
                                            <div class="input-group form-group mb-0" style="width:120px;">
                                                <a x-on:click="incrementQuantity(index)" class="btn btn-sm btn-primary">
                                                    <i class="fa fa-plus"></i>
                                                </a>
                                                <input type="number" x-on:keyup="typeQuantity(index)"  x-model="invoiceitems[index]['quantity']" value="1" class="form-control form-control-sm text-center">
                                                <a x-on:click="decrementQuantity(index)" class="btn btn-sm btn-danger">
                                                    <i class="fa fa-minus"></i>
                                                </a>
                                            </div>
                                        </td>
                                        <td class="text-center" x-text="item.box"></td>
                                        <td class="text-center" x-text="item.carton"></td>
                                        <td class="text-center" x-text="item.av_qty"></td>
                                        <td class="text-end" x-text="money(item.selling_price)"></td>
                                        <td class="text-end" x-text="money((item.selling_price * item.quantity))"></td>
                                        <td class="text-end"><button class="btn btn-sm btn-danger" x-on:click="deleteItem(item.stock_id)">Delete</button> </td>
                                    </tr>
                                </template>
                                </tbody>
                                <tfoot>
                                <tr>
                                    <th class="text-start"></th>
                                    <th class="text-center"></th>
                                    <th class="text-center"></th>
                                    <th class="text-end" colspan="2">Sub Total</th>
                                    <th class="text-end" colspan="2" id="sub_total" x-text="netTotal">0.00</th>
                                    <th class="text-end"></th>
                                </tr>
                                <tr>
                                    <th class="text-start"></th>
                                    <th class="text-center"></th>
                                    <th class="text-center"></th>
                                    <th class="text-end" colspan="2">Total</th>
                                    <th class="text-end total_invoice" colspan="2" style="font-size: 15px;" x-text="netTotal">0.00</th>
                                    <th class="text-end"></th>
                                </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>


        </div>

        <div class="col-sm-4">
            <div class="card" style="height: 118vh;">
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-12">
                            <h4 class="mb-4">Bill To
                                <button href="#" wire:click="newCustomer" wire:loading.attr="disabled" wire:target="newCustomer"  class="btn btn-sm btn-success float-end">
                                    <span wire:loading wire:target="newCustomer" class="spinner-border spinner-border-sm me-2" role="status"></span>
                                    <i class="fa fa-user" wire:loading.remove wire:target="newCustomer"> Add Customer</i>
                                </button>
                            </h4>

                            <div class="mb-3">
                                <strong>Invoice Number : </strong>
                                <b>{{ $invoice_number }}</b>
                            </div>

                            <div class="mb-3">
                                <label>Search For Customer :</label>
                                <input class="form-control input-sm" id="customer-search-text" x-model="searchCustomerString" class="form-control" x-on:keyup.debounce="searchCustomer(this.value)"  placeholder="Search for customer by phone number, name or email address">

                                @if(userCanView('customer.create'))
                                    <a href="#" wire:click="newCustomer" class="text-success" style="display: block;text-align: center">Add New Customer</a>
                                @endif
                            </div>
                            <template x-if="(searchCustomers.length > 0)">
                                <div  class="np-result-container" style="margin-top: -40px">
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
                                <address class="bg-success text-white rounded-3 bg-gradient p-2" style="font-size: 12px;">
                                    <h6 style="font-size: 12px;">Selected Customer :</h6>
                                    <span class="d-block"><b x-html="customer_id.firstname +' '+customer_id.lastname"></b></span>
                                    <span class="d-block" x-text="customer_id.company_name"></span>
                                    <span class="d-block" x-text="customer_id.email"></span>  <span x-text="customer_id.phone_number"></span>
                                </address>
                            </template>
                            <br/>

                        </div>


                        <div class="mb-3">
                            <strong>Customer Information:</strong><br>
                            <div id="customer_info">
                                <small style="color: red;display: none"> Search and select Customer Above.......</small>
                            </div>
                        </div>

                        <br>
                        <div class="col-lg-12">
                            <div class="bg-primary rounded-3">
                                <h2 align="center" class="text-white pt-3 pb-3" style="margin-top: 10px;"><span>NGN </span><span class="total_invoice" x-text="netTotal">0.00</span></h2>
                            </div>
                        </div>
                        <br/>

                        <h4>Invoice Property</h4>

                        @if(userCanView('invoiceandsales.editInvoiceDate'))
                            <div class="mb-3">
                                <label>Invoice Date</label>
                                <input class="form-control datepicker-basic" x-init="initDatePicker()" wire:model.live="invoiceData.invoice_date" class="form-control" name="invoice_date" id="datepicker-basic">
                            </div>
                        @else
                            <div class="mb-3">
                                <label>Invoice Date</label>
                                <input readonly="" style="background-color: #FFF;color: #000;" wire:model.live="invoiceData.invoice_date" class="form-control" name="invoice_date">
                            </div>
                        @endif
                        @if(isset($this->invoice->id))
                            <div class="mb-3">
                                <label>Department</label>
                                <span class="form-control">{{ $this->selectedDepartment['label'] }}</span>
                                <input type="hidden" wire:model.live="department_id" name="department" id="department">
                            </div>
                        @else
                            <div class="mb-3">
                                <label>Department</label>
                                <select class="form-control" wire:model.live="department_id" name="department" id="department">
                                    @foreach($this->departments as $department)
                                        <option value="{{ $department->id }}">{{ $department->label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="mb-3">
                            <label>Sales Representative</label>
                            <input class="form-control" style="background-color: #FFF;color:#000;" value="{{ auth()->user()->name }}" disabled="">
                        </div>


                        @if(isset($this->invoice->id))
                            <div class="d-grid gap-3 mt-4">
                                <button x-on:click="generateInvoice('{{ status('Draft') }}')" class="btn btn-lg d-block btn-dark submit_btn"   type="button" wire:target="generateInvoice" wire:loading.attr="disabled">
                                    UPDATE INVOICE

                                    <i wire:loading.remove wire:target="generateInvoice" class="fa fa-save"></i>
                                    <span wire:loading wire:target="generateInvoice" class="spinner-border spinner-border-sm me-2" role="status"></span>
                                </button>
                            </div>
                        @else
                            <div class="d-grid gap-3 mt-4">

                                <button x-on:click="generateInvoice('{{ status('Draft') }}')" class="btn btn-lg d-block btn-dark submit_btn" type="button" wire:target="generateInvoice" wire:loading.attr="disabled">
                                    {{  $this->department  == "4" ? 'PAY NOW' : 'DRAFT INVOICE' }}
                                    <i wire:loading.remove wire:target="generateInvoice" class="fa fa-save"></i>
                                    <span wire:loading wire:target="generateInvoice" class="spinner-border spinner-border-sm me-2" role="status"></span>
                                </button>

                                @if (userCanview(type().'requestForDiscount'))
                                    <button x-on:click="generateInvoice('{{ status('Discount') }}')" class="btn btn-lg d-block btn-warning submit_btn" data-status="DISCOUNT"  type="button"  wire:target="generateInvoice" wire:loading.attr="disabled">
                                        REQUEST FOR DISCOUNT
                                        <i  wire:loading.remove wire:target="generateInvoice" class="fa fa-shopping-cart"></i>
                                    </button>
                                @endif

                            </div>
                        @endif


                    </div>
                </div>
            </div>
        </div>
    </div>
    <div wire:ignore.self class="modal fade" id="simpleComponentModal" tabindex="-1" role="dialog" aria-hidden="true">
        <form method="post" wire:submit="saveCustomers()">
            <div class="modal-dialog modal-dialog-centered" role="document">

                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $this->modalTitle }} {{ $this->modalName }}</h5>
                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">

                            <div class="col-12">
                                <div class="row">
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label>Firstname</label>
                                            <input class="form-control" type="text" wire:model="firstname"  name="firstname" value="{{ $this->firstname }}" placeholder="Firstname">
                                            @error('firstname') <span class="text-danger">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label>Lastname</label>
                                            <input class="form-control" type="text" wire:model="lastname"  name="lastname" value="{{ $this->lastname }}" placeholder="Lastname">
                                            @error('lastname') <span class="text-danger">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">


                                    <div class="mb-3">
                                        <label>Email Address</label>
                                        <input class="form-control" type="email" wire:model="email"  name="email" value="{{ $this->email }}" placeholder="Email Address">
                                    </div>

                                    <div class="mb-3">
                                        <label>Phone Number</label>
                                        <input class="form-control" type="text" wire:model="phone_number"  name="phone_number" value="{{ $this->phone_number }}" placeholder="Phone Number">
                                        @error('phone_number') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label>City</label>
                                        <select class="form-control" wire:model="city_id">
                                            <option value="">Select City</option>
                                            @foreach($this->cities as $city)
                                                <option value="{{ $city->id }}">{{ $city->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>


                                    <div class="mb-3">
                                        <label>Address</label>
                                        <textarea class="form-control" wire:model="address"  name="address" placeholder="Address"></textarea>
                                        @error('address') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>

                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="modal-footer ">
                        <button type="submit" wire:target="saveCustomers" wire:loading.attr="disabled" class="btn btn-primary">
                            <span wire:loading wire:target="saveCustomers" class="spinner-border spinner-border-sm me-2" role="status"></span>
                            {{ $this->saveButton }}
                        </button>
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </div>

            </div>
        </form>
    </div>
    <script>

        function invoice()
        {
            return {
                invoiceitems : @this.get('invoiceData.invoiceitems') ? JSON.parse( @this.get('invoiceData.invoiceitems')) : [],
            searchString : "",
            searchproduct : [],
            searchCustomers : [],
            errors : {},
            customer_id : @this.get('invoiceData.customer_id') ? @this.get('invoiceData.customer_id') : {firstname : ""},
            netTotal :  @this.get('InvoiceData.sub_total') ?  @this.get('InvoiceData.invoiceitems') : 0.00,
            quantity : [],
            department : @this.get('d') ? @this.get('d') : "",
            searchCustomerString : "",
            selectedProduct : {},
            selectProduct(product){
            this.selectedProduct = product;
            this.selling_price = product.selling_price;
            this.addItem();
        },
            addItem()
            {
                if ((this.invoiceitems.filter(e => e.stock_id === this.selectedProduct.id)).length > 0) {
                    $wire.warning("Stock already exists");
                    return;
                }

                if(this.selectedProduct['quantity'] === 0 || this.selectedProduct['quantity'] === "0")
                {
                    error(this.selectedProduct.name+' is out of stock, quantity remain is 0');
                    @this.logOutofStockProduct( this.selectedProduct.id)
                    return;
                }

                this.searchproduct = [];
                this.searchString = "";

                let selling_price = (this.selectedProduct.promo_selling_price && this.selectedProduct.promo_selling_price > 0) ? this.selectedProduct.promo_selling_price : this.selectedProduct.selling_price;

                this.invoiceitems.push({
                    stock_id : this.selectedProduct.id,
                    quantity : 1,
                    box : this.selectedProduct.box,
                    carton : this.selectedProduct.carton,
                    av_qty : this.selectedProduct.quantity,
                    customer_id : @this.get('invoiceData.customer_id') ?  @this.get('invoiceData.customer_id') :  {firstname : ""},
                name : this.selectedProduct.name,
                    added_by : {{ auth()->user()->id }},
                discount_added_by : null,
                cost_price : this.selectedProduct.cost_price,
                selling_price : selling_price,
                profit : this.selectedProduct.selling_price - this.selectedProduct.cost_price,
                discount_value : 0,
                discount_amount : 0,
                discount_type : 'Fixed',
                custom_price : this.selectedProduct.custom_prices ?? [],
                default_selling_price : selling_price
            });

                this.totalInvoice();
            },
            getPriceRange(quantity, defaultSellingPrice, customPrices) {
            console.log(defaultSellingPrice);
            console.log(quantity);
            console.log(customPrices);
                for (const priceRule of (customPrices ?? [])) {
                    const min = parseInt(priceRule.min_qty);
                    const max = parseInt(priceRule.max_qty);

                    if (quantity >= min && quantity < max) {
                        return parseFloat(priceRule.price);
                    }
                }

                return parseFloat(defaultSellingPrice);
            },
            incrementQuantity(index)
            {
                let qty =  parseInt(this.invoiceitems[index]['quantity']) + 1;
                if(qty <= this.invoiceitems[index]['av_qty'])
                {
                    this.invoiceitems[index]['selling_price'] = this.getPriceRange(qty, this.invoiceitems[index]['default_selling_price'],  this.invoiceitems[index]['custom_price']);

                    this.invoiceitems[index]['quantity'] = qty;
                    this.invoiceitems[index]['total_cost_price'] = this.invoiceitems[index]['cost_price'] * this.invoiceitems[index]['quantity'];
                    this.invoiceitems[index]['total_selling_price'] = this.invoiceitems[index]['selling_price'] * this.invoiceitems[index]['quantity'];
                    this.invoiceitems[index]['total_profit'] =  (this.invoiceitems[index]['selling_price'] - this.invoiceitems[index]['cost_price']) * this.invoiceitems[index]['quantity'];

                    this.totalInvoice();
                }
            },
            typeQuantity(index)
            {
                if(this.invoiceitems[index]['quantity'] > this.invoiceitems[index]['av_qty'])
                {
                    error("Total available quantity is "+this.invoiceitems[index]['av_qty']);
                    this.invoiceitems[index]['quantity'] = this.invoiceitems[index]['av_qty'];
                }

                this.invoiceitems[index]['selling_price'] = this.getPriceRange(this.invoiceitems[index]['quantity'], this.invoiceitems[index]['default_selling_price'],  this.invoiceitems[index]['custom_price']);

                this.invoiceitems[index]['total_cost_price'] = this.invoiceitems[index]['cost_price'] * this.invoiceitems[index]['quantity'];
                this.invoiceitems[index]['total_selling_price'] = this.invoiceitems[index]['selling_price'] * this.invoiceitems[index]['quantity'];
                this.invoiceitems[index]['total_profit'] =  (this.invoiceitems[index]['selling_price'] - this.invoiceitems[index]['cost_price']) * this.invoiceitems[index]['quantity'];
                this.totalInvoice();
            },
            decrementQuantity(index)
            {
                let qty =  parseInt(this.invoiceitems[index]['quantity']) - 1;
                if(qty > 0)
                {
                    this.invoiceitems[index]['selling_price'] = this.getPriceRange(qty, this.invoiceitems[index]['default_selling_price'],  this.invoiceitems[index]['custom_price']);

                    this.invoiceitems[index]['quantity'] = qty;
                    this.invoiceitems[index]['total_cost_price'] = this.invoiceitems[index]['cost_price'] * this.invoiceitems[index]['quantity'];
                    this.invoiceitems[index]['total_selling_price'] = this.invoiceitems[index]['selling_price'] * this.invoiceitems[index]['quantity'];
                    this.invoiceitems[index]['total_profit'] =  (this.invoiceitems[index]['selling_price'] - this.invoiceitems[index]['cost_price']) * this.invoiceitems[index]['quantity'];

                    this.totalInvoice();
                }
            },
            deleteItem(id)
            {
                this.invoiceitems = this.invoiceitems.filter(item => id !== item.stock_id);
                this.totalInvoice();
            },

            totalInvoice()
            {
                console.log(this.invoiceitems);
                this.netTotal = this.money(this.invoiceitems.length > 0 ? this.invoiceitems.reduce((result, item) => {
                    return result + (item.selling_price * item.quantity);
                }, 0) : 0);
                return true;
            },
            async searchProduct() {
            if (this.searchString !== "" && this.searchString.length > 3) {
                this.searchproduct = await (await fetch('{{ route('findstock') }}?query=' + this.searchString+"&column="+this.department
                )).
                json();
            }else
            {
                this.searchproduct = [];
            }
        },
            newCustomerEvent()
            {

                let myModal = "";

                myModal = new bootstrap.Modal(document.getElementById("simpleComponentModal"), {});

                window.addEventListener('openModal', (e) => {
                    myModal.show();
                });

                window.addEventListener('newCustomer', (event) => {
                    this.customer_id = event.detail[0].customer;
                    myModal.hide();
                });
                window.addEventListener('invoiceLink', (event) => {
                    setTimeout(()=>{
                        window.location.href = event.detail[0].link;
                    },1500)
                });
                window.addEventListener('departmentChange', (event) => {
                    this.department = event.detail[0].department;
                });
            },
            money(amount)
            {
                return formatMoney(amount);
            },

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
            selectCus(customer)
            {
                this.customer_id = customer;
                this.searchCustomerString = "";
                this.searchCustomers = [];
            },

            generateInvoice(status_id)
            {
                const hasZero = this.invoiceitems.some(item => item.quantity <= 0);
                if(hasZero) {
                    error("One or more items in your cart have a quantity of zero or less. Please review your input and try again")
                    return ;
                }
                 @php
                    if( $this->department != "4") {
                 @endphp
                if(this.customer_id.firstname === "") {
                     error("You have not select a customer for this invoice,  please select a customer by searching!...")
                    return ;
                }

                @php
                    }
                @endphp

                if(this.invoiceitems.length === 0)
                {
                    error("Invoice items list is empty, please add at least one product to generate invoice")
                    return ;
                }
                @php
                    if( $this->department != "4") {
                @endphp
                @this.set('invoiceData.customer_id',this.customer_id.id, true);
                @php
                    }else{
                @endphp
                if(this.customer_id.firstname !== "")
                {
                    @this.set('invoiceData.customer_id',this.customer_id.id, true);
                }else{
                    @this.set('invoiceData.customer_id',"1", true);
                }

                @php
                    }
                @endphp

                @this.set('invoiceData.status_id', status_id, true);
                @this.set('invoiceData.invoiceitems', JSON.stringify(this.invoiceitems), true);

                this.errors = {};
                let obj = this;
                @this.generateInvoice().then(function(resp){

                if(resp.status !== true)
                {
                    obj.errors = resp.errors;

                }else {
                    setTimeout(() => {
                        window.location.href = '{{ route('invoiceandsales.create') }}';
                    }, 1000);
                }

            });

            },

            initDatePicker() {
            flatpickr(".datepicker-basic", {  });
            var e = document.querySelectorAll("[data-trigger]");
            for (i = 0; i < e.length; ++i) {
                var a = e[i];
                new Choices(a, { placeholderValue: "This is a placeholder set in the config", searchPlaceholderValue: "This is a search placeholder" });
            }
        },
            async requestProductWithBarcode(barcode)
            {
                const product = await (await fetch('{{ route('findStockByBarcode') }}?barcode=' + barcode+"&column="+this.department
                )).json();
                this.searchString = "";
                if(product.hasOwnProperty('id') &&  ((this.invoiceitems.filter(e => e.stock_id === product.id)).length === 0)) {
                    this.selectedProduct = product
                    this.addItem();
                }  else if(product.hasOwnProperty('id') && ((this.invoiceitems.filter(e => e.stock_id === this.selectedProduct.id)).length > 0)) {
                    const index = this.invoiceitems.findIndex(item => item.stock_id === product.id);
                    if(index > -1) {
                        this.incrementQuantity(index);
                    }
                } else {
                    console.log("product not found");
                }
            },
            getInputFromBarcode()
            {
                var obj = this;
                $(document).ready(function(){
                    $(document).scannerDetection({
                        timeBeforeScanTest: 200, // wait for the next character for upto 200ms
                        endChar: [13], // be sure the scan is complete if key 13 (enter) is detected
                        avgTimeByChar: 40, // it's not a barcode if a character takes longer than 40ms// turn off scanner detection if an input has focus
                        startChar: [16], // Prefix character for the cabled scanner (OPL6845R)
                        endChar: [40],
                        ignoreIfFocusOn : ['customer-search-text', 'searchText'],
                        onComplete: function(barcode){
                            //window.focus();
                            obj.requestProductWithBarcode(barcode);
                        }, // main callback function
                        scanButtonKeyCode: 116, // the hardware scan button acts as key 116 (F5)
                        scanButtonLongPressThreshold: 5, // assume a long press if 5 or more events come in sequence
                        onScanButtonLongPressed: function(){

                        }, // callback for long pressing the scan button
                        onError: function(string){}
                    });
                });
            }
        }
        }

        document.addEventListener('alpine:init', () => {
            Alpine.data('dropdown', () => ({
                init() {
                    console.log('I will get evaluated when initializing each "dropdown" component.')
                },
            }))


        })


    </script>
</div>





