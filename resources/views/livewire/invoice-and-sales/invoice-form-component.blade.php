<div x-data="invoice()" x-init="resolveMembershipLabel(); totalInvoice(); newCustomerEvent(); getInputFromBarcode(); logProductNotExist();  handleProductOptionModal();">
    <div x-show="isLoading" style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(255, 255, 255, 0.8); z-index: 999999; pointer-events: all;" x-cloak>
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); display: flex; flex-direction: column; align-items: center; justify-content: center;">
            <div class="spinner-border text-primary" role="status" style="width: 4rem; height: 4rem;">
                <span class="visually-hidden">Loading...</span>
            </div>
            <h4 class="mt-3 text-primary fw-bold">Processing, please wait...</h4>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-8">
            <div class="card">
                <div class="card-body" style="position: relative;">
                    @if($this->isReturn)
                        <div class="alert alert-warning mb-0 py-2">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div>
                                    <i class="fa fa-undo me-1"></i>
                                    <strong>Return Invoice Mode:</strong> Reduce item quantities or remove returned items. Adding new stock or increasing quantities is disabled.
                                </div>
                            </div>
                            <div class="row g-2 mt-2 pt-2 border-top border-warning-subtle text-dark">
                                <div class="col-sm-4">
                                    <small class="text-muted d-block">Original Invoice Amount</small>
                                    <strong class="fs-6">₦<span x-text="money(originalInvoiceRaw)"></span></strong>
                                </div>
                                <div class="col-sm-4">
                                    <small class="text-muted d-block">Current Invoice Amount (After Return)</small>
                                    <strong class="fs-6 text-primary">₦<span x-text="money(computeNet())"></span></strong>
                                </div>
                                <div class="col-sm-4">
                                    <small class="text-muted d-block">Return Difference (Returned Value)</small>
                                    <strong class="fs-6 text-danger">₦<span x-text="money(computeReturnDifference())"></span></strong>
                                </div>
                            </div>
                        </div>
                    @else
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
                                                    @endif
                                                    &nbsp; &nbsp;
                                                    <span x-show="(product.custom_prices.length > 0)" class="font-size-13" style="font-weight: bolder"  >Bundle Price : <span class="text-danger font-size-13"  x-html="money(product.custom_prices[0].price)+'&nbsp; = '+product.custom_prices[0].min_qty+' Quantity'"></span></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    @endif
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
                                    <th class="text-center" style="width: 7%;">Av. Qty</th>
                                    <th class="text-center" style="width: 15%;">Rate</th>
                                    <th class="text-end" style="width: 8%;">Total</th>
                                    <th class="text-end" style="width: 5%;">Action</th>
                                </tr>
                                </thead>
                                <tbody id="appender">

                                <template x-for="(item,index) in invoiceitems" :key="item.stock_id">
                                    <tr :class="item.isDependent ? 'table-warning' : ''">
                                        <td class="text-start">
                                            <span class="d-block" x-text="item.name"></span>
                                            <span class="d-block" x-show="item.selectedOptions && item.selectedOptions.length > 0" x-html="renderSelectedOptions(item.selectedOptions)"></span>
                                            <span class="d-block text-danger" x-text="errors[item.stock_id]"></span>
                                        </td>

                                        <td class="text-center">
                                            <div class="input-group form-group mb-0" style="width:120px;">
                                                <!-- If it's a dependent product or at original return max qty, disable increasing -->
                                                <a x-on:click="!item.isDependent && incrementQuantity(index)" class="btn btn-sm btn-primary" :class="{'disabled': item.isDependent || (isReturn && parseInt(item.quantity) >= parseInt(item.max_return_qty ?? item.original_quantity ?? item.quantity))}">
                                                    <i class="fa fa-plus"></i>
                                                </a>
                                                <input type="number"
                                                       min="1"
                                                       :max="isReturn ? (item.max_return_qty ?? item.original_quantity ?? item.quantity) : item.av_qty"
                                                       x-on:keyup="!item.isDependent && typeQuantity(index)"
                                                       x-on:change="!item.isDependent && typeQuantity(index)"
                                                       x-model="item.quantity"
                                                       class="form-control form-control-sm text-center"
                                                       :readonly="item.isDependent">
                                                <a x-on:click="!item.isDependent && decrementQuantity(index)" class="btn btn-sm btn-danger" :class="{'disabled': item.isDependent}">
                                                    <i class="fa fa-minus"></i>
                                                </a>
                                            </div>
                                        </td>

                                        <td class="text-center" x-text="item.box"></td>
                                        <td class="text-center" x-text="item.carton"></td>
                                        <td class="text-center" x-text="item.av_qty"></td>
                                        <td class="text-center" x-text="money((parseFloat(item.selling_price) || 0) - (parseFloat(item.discount_amount) || 0))"></td>
                                        <td class="text-end" x-text="money(((parseFloat(item.selling_price) || 0) - (parseFloat(item.discount_amount) || 0)) * (parseFloat(item.quantity) || 0))"></td>
                                        <td class="text-end">
                                            <!-- Only show delete button for parent products -->
                                            <template x-if="!item.isDependent">
                                                <button class="btn btn-sm btn-danger" x-on:click="deleteItem(item.stock_id)">Delete</button>
                                            </template>
                                        </td>
                                    </tr>
                                </template>



                                </tbody>
                                <tfoot>
                                <tr>
                                    <th class="text-start"></th>
                                    <th class="text-center"></th>
                                    <th class="text-center"></th>
                                    <th class="text-end" colspan="2">Sub Total</th>
                                    <th class="text-end" colspan="2" id="sub_total" x-text="subTotal">0.00</th>
                                    <th class="text-end"></th>
                                </tr>
                                <template x-if="membership_discount_value > 0">
                                    <tr>
                                        <th class="text-start"></th>
                                        <th class="text-center"></th>
                                        <th class="text-center"></th>
                                        <th class="text-end" colspan="2">Membership Discount (<span x-text="membership_label"></span>) - <span x-text="membership_discount_value"></span>%</th>
                                        <th class="text-end" colspan="2">-<span x-text="money(membership_discount)">0.00</span></th>
                                        <th class="text-end"></th>
                                    </tr>
                                </template>
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

                            @php $customerNameOnly = app(\App\Classes\Settings::class)->isCustomerNameOnly(); @endphp
                            <div class="mb-3">
                                <label>Search For Customer :</label>
                                <input class="form-control input-sm" id="customer-search-text" x-model="searchCustomerString"  x-on:input.debounce.400ms="searchCustomer()"  placeholder="{{ $customerNameOnly ? 'Search for customer by firstname or lastname' : 'Search for customer by phone number, name or email address' }}">

                                @if(userCanView('customer.create'))
                                    <a href="#" wire:click="newCustomer" class="text-success" style="display: block;text-align: center">Add New Customer</a>
                                @endif
                            </div>

                            {{--                            <div class="mb-3">--}}
                            {{--                                <label>Select Prescriber:</label>--}}
                            {{--                                <select class="form-control" x-model="prescriber_id" x-init="select2" id="select2">--}}
                            {{--                                    <option value="">-Select One-</option>--}}
                            {{--                                    @foreach($prescribers as $prescriber)--}}
                            {{--                                        <option value="{{ $prescriber['id'] }}">{{ $prescriber['name'] }}</option>--}}
                            {{--                                    @endforeach--}}
                            {{--                                </select>--}}
                            {{--                            </div>--}}


                            <template x-if="(searchCustomers.length > 0)">
                                <div  class="np-result-container" style="margin-top: -40px">
                                    <template x-for="customer in searchCustomers">
                                        <div  x-on:click="selectCus(customer)">
                                            <div class="np-result-item">
                                                <div class="np-ib np-text-container">
                                                    <div x-text="(customer.firstname)+ ' '+(customer.lastname)"></div>
                                                    <div class="np-result-description">
                                                        Phone Number : <span x-text="customer.phone_number"></span>
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

                        {{-- Stock exchange designation: wholesales only, and only for users granted this permission --}}
                        @if(userCanView('invoiceandsales.createStockExchangeInvoice') && $this->d !== 'retail' && $this->d !== 'retail_store' && $this->department != "4")
                            <br/>
                            <div class="mb-3">
                                <label class="fw-semibold">Invoice Designation</label>
                                <select class="form-select" x-model="invoice_type" x-on:change="is_stock_exchange = (invoice_type === 'stock_exchange'); if (is_stock_exchange) { initSupplierSelect2(); } else { supplier_id = ''; $('#stock_exchange_supplier_select').val('').trigger('change.select2'); }">
                                    <option value="normal">Normal Sales Invoice</option>
                                    <option value="stock_exchange">Stock Exchange Invoice (Stock for Stock)</option>
                                </select>
                            </div>

                            <div x-show="invoice_type === 'stock_exchange'" x-cloak class="mb-3 p-2 border border-info rounded bg-info-subtle" x-init="initSupplierSelect2()">
                                <label class="fw-semibold text-dark">Stock Exchange Supplier <span class="text-danger">*</span></label>
                                <div wire:ignore>
                                    <select id="stock_exchange_supplier_select" class="form-control select2" style="width: 100%;">
                                        <option value="">-- Search &amp; Select Supplier --</option>
                                        @foreach($this->suppliers as $sup)
                                            <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <small class="text-muted d-block mt-1">Products on this invoice will be issued to the selected supplier and available for Stock-for-Stock Purchase Order settlement.</small>
                            </div>
                        @endif

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
                                <button x-on:click="generateInvoice('{{ $this->isReturn ? $this->invoice->status_id : status('Draft') }}')" class="btn btn-lg d-block btn-dark submit_btn"   type="button" wire:target="generateInvoice" wire:loading.attr="disabled">
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
    {{-- Add Customer modal (same design as the Customer Dashboard form) --}}
    <div wire:ignore.self class="modal fade" id="simpleComponentModal" tabindex="-1" role="dialog" aria-hidden="true">
        <form method="post" wire:submit="saveCustomers">
            <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                <div class="modal-content shadow border-0">
                    <div class="modal-header bg-light py-3">
                        <h5 class="modal-title fw-bold">
                            <i class="bx bx-user-plus me-2 text-primary"></i>
                            {{ $this->modalTitle }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Firstname <span class="text-danger">*</span></label>
                                <input class="form-control" type="text" wire:model="firstname" placeholder="e.g. John" required>
                                @error('firstname') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Lastname <span class="text-danger">*</span></label>
                                <input class="form-control" type="text" wire:model="lastname" placeholder="e.g. Doe" required>
                                @error('lastname') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            @if(!$customerNameOnly)
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Phone Number <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bx bx-phone"></i></span>
                                        <input class="form-control" type="text" wire:model.live.debounce.400ms="phone_number" placeholder="e.g. 08012345678" required>
                                    </div>
                                    <div class="form-text small text-muted">Required. Uniquely identifies the customer (e.g. 080..., +234...).</div>
                                    @error('phone_number') <span class="text-danger small">{{ $message }}</span> @enderror

                                    @if($duplicatePhoneWarning)
                                        <div class="alert alert-danger py-2 px-3 small mt-2 mb-0">
                                            <i class="bx bx-error-circle me-1"></i> {{ $duplicatePhoneWarning }}
                                            <div class="mt-2 d-flex flex-wrap align-items-center gap-3">
                                                <button type="button" class="btn btn-sm btn-outline-primary" wire:click="useExistingCustomer">
                                                    <i class="bx bx-user-check me-1"></i> Use existing customer
                                                </button>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Email Address</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bx bx-envelope"></i></span>
                                        <input class="form-control" type="email" wire:model="email" placeholder="customer@example.com">
                                    </div>
                                    @error('email') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                            @endif

                            @if(department_by_quantity_column('wholesales', false)->status && department_by_quantity_column('retail', false)->status)
                                <div class="col-12">
                                    <label class="form-label fw-semibold d-block">Customer Category / Department <span class="text-danger">*</span></label>
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <div class="form-check card p-2 border {{ $customer_type === 'wholesale' ? 'border-primary bg-primary-subtle' : 'border' }} mb-0">
                                                <input class="form-check-input ms-1" type="radio" wire:model.live="customer_type" value="wholesale" id="invTypeWholesale">
                                                <label class="form-check-label ms-2 fw-semibold" for="invTypeWholesale">
                                                    <i class="bx bx-buildings text-primary me-1"></i> Wholesales
                                                    <div class="small text-muted fw-normal">Customer buys in wholesale</div>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check card p-2 border {{ $customer_type === 'retail' ? 'border-success bg-success-subtle' : 'border' }} mb-0">
                                                <input class="form-check-input ms-1" type="radio" wire:model.live="customer_type" value="retail" id="invTypeRetail">
                                                <label class="form-check-label ms-2 fw-semibold" for="invTypeRetail">
                                                    <i class="bx bx-store text-success me-1"></i> Retail
                                                    <div class="small text-muted fw-normal">Customer buys in retail</div>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check card p-2 border mb-0" style="{{ $customer_type === 'both' ? 'border-color: #6f42c1 !important; background-color: rgba(111, 66, 193, 0.08);' : '' }}">
                                                <input class="form-check-input ms-1" type="radio" wire:model.live="customer_type" value="both" id="invTypeBoth">
                                                <label class="form-check-label ms-2 fw-semibold" for="invTypeBoth">
                                                    <i class="bx bx-git-merge me-1" style="color: #6f42c1;"></i> Both
                                                    <div class="small text-muted fw-normal">Wholesale &amp; Retail</div>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    @error('customer_type') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                            @endif

                            @if(!$customerNameOnly)
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">City</label>
                                    <select class="form-select" wire:model="city_id">
                                        <option value="">Select City</option>
                                        @foreach($this->cities as $city)
                                            <option value="{{ $city->id }}">{{ $city->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Address</label>
                                    <textarea class="form-control" rows="2" wire:model="address" placeholder="Physical Address"></textarea>
                                    @error('address') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="modal-footer bg-light py-2 d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" wire:target="saveCustomers" wire:loading.attr="disabled" class="btn btn-primary btn-sm px-4">
                            <span wire:loading wire:target="saveCustomers" class="spinner-border spinner-border-sm me-2" role="status"></span>
                            <i wire:loading.remove wire:target="saveCustomers" class="bx bx-check me-1"></i>
                            {{ $this->saveButton }}
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    {{-- Invoice Return Reason & Customer Refund Confirmation Modal --}}
    @if($this->isReturn)
        <div
            x-show="showReturnModal"
            x-cloak
            style="position: fixed; inset: 0; background: rgba(15, 23, 42, 0.55); z-index: 1055; overflow-y: auto; padding: 1.5rem 1rem;"
            x-on:keydown.escape.window="if (!returnModalSubmitting) closeReturnModal()"
        >
            <div class="modal-dialog modal-lg modal-dialog-centered" style="max-width: 680px; margin: 1.5rem auto;">
                <div class="modal-content shadow-lg border-0 rounded-3 bg-white">
                    <div class="modal-header bg-warning-subtle border-bottom px-4 py-3">
                        <h5 class="modal-title fw-bold text-dark mb-0">
                            <i class="fa fa-undo me-2 text-warning-emphasis"></i>
                            Confirm Invoice Return &amp; Customer Refund (#{{ $this->invoice->invoice_number }})
                        </h5>
                        <button type="button" class="btn-close" :disabled="returnModalSubmitting" x-on:click="closeReturnModal()"></button>
                    </div>

                    <div class="modal-body p-4">
                        {{-- 1. Calculated Financial Return Difference Summary --}}
                        <div class="p-3 rounded-3 bg-light border mb-4">
                            <h6 class="fw-bold text-dark mb-2 pb-1 border-bottom">
                                1. Invoice Return Financial Summary
                            </h6>
                            <div class="row text-center g-2">
                                <div class="col-md-4">
                                    <div class="p-2 bg-white rounded border">
                                        <small class="text-muted d-block">Original Invoice Amount</small>
                                        <strong class="fs-5 text-dark">₦<span x-text="money(originalInvoiceRaw)"></span></strong>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-2 bg-white rounded border">
                                        <small class="text-muted d-block">Current Invoice Amount</small>
                                        <strong class="fs-5 text-primary">₦<span x-text="money(computeNet())"></span></strong>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-2 bg-danger-subtle rounded border border-danger-subtle">
                                        <small class="text-danger-emphasis d-block fw-semibold">Return Difference (Returned Value)</small>
                                        <strong class="fs-5 text-danger">₦<span x-text="money(computeReturnDifference())"></span></strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Validation Error Alert inside Modal --}}
                        <div x-show="returnModalError !== ''" x-cloak class="alert alert-danger py-2 px-3 mb-3">
                            <i class="fa fa-exclamation-circle me-1"></i>
                            <span x-text="returnModalError"></span>
                        </div>

                        {{-- 2. Capture Reason for Return --}}
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark mb-2">
                                2. What is the reason for this invoice return? <span class="text-danger">*</span>
                            </label>
                            <div class="d-flex flex-column gap-2">
                                <label class="p-2 border rounded d-flex align-items-center gap-2" style="cursor: pointer;" :class="{'border-primary bg-primary-subtle': returnForm.return_reason === 'Cashier / Sales Order made a mistake during invoicing.'}">
                                    <input type="radio" class="form-check-input mt-0" name="return_reason_opt" value="Cashier / Sales Order made a mistake during invoicing." x-model="returnForm.return_reason" x-on:change="returnModalError = ''">
                                    <span>1. Cashier / Sales Order made a mistake during invoicing.</span>
                                </label>
                                <label class="p-2 border rounded d-flex align-items-center gap-2" style="cursor: pointer;" :class="{'border-primary bg-primary-subtle': returnForm.return_reason === 'Customer returned item(s) on the invoice.'}">
                                    <input type="radio" class="form-check-input mt-0" name="return_reason_opt" value="Customer returned item(s) on the invoice." x-model="returnForm.return_reason" x-on:change="returnModalError = ''">
                                    <span>2. Customer returned item(s) on the invoice.</span>
                                </label>
                                <label class="p-2 border rounded d-flex align-items-center gap-2" style="cursor: pointer;" :class="{'border-primary bg-primary-subtle': returnForm.return_reason === 'Other reason.'}">
                                    <input type="radio" class="form-check-input mt-0" name="return_reason_opt" value="Other reason." x-model="returnForm.return_reason" x-on:change="returnModalError = ''">
                                    <span>3. Other reason.</span>
                                </label>
                            </div>

                            <div class="mt-2" x-show="returnForm.return_reason === 'Other reason.'" x-cloak>
                                <label class="form-label fw-semibold small text-dark mb-1">
                                    Please provide an explanation for the return <span class="text-danger">*</span>
                                </label>
                                <textarea
                                    class="form-control"
                                    rows="2"
                                    x-model="returnForm.other_reason_explanation"
                                    x-on:input="returnModalError = ''"
                                    placeholder="Describe the reason for returning this invoice..."
                                ></textarea>
                            </div>
                        </div>

                        {{-- 3. Customer Refund Confirmation --}}
                        <div class="mb-4" x-show="returnForm.return_reason !== ''" x-cloak>
                            <label class="form-label fw-bold text-dark mb-2">
                                3. Will you be refunding the customer the invoice return difference (₦<span x-text="money(computeReturnDifference())"></span>)? <span class="text-danger">*</span>
                            </label>
                            <div class="d-flex gap-3">
                                <label class="p-2 px-3 border rounded d-inline-flex align-items-center gap-2" style="cursor: pointer;" :class="{'border-success bg-success-subtle fw-semibold': returnForm.will_refund === 'yes'}">
                                    <input type="radio" class="form-check-input mt-0" name="will_refund_opt" value="yes" x-model="returnForm.will_refund" x-on:change="returnModalError = ''">
                                    <span>Yes — Refund Customer</span>
                                </label>
                                <label class="p-2 px-3 border rounded d-inline-flex align-items-center gap-2" style="cursor: pointer;" :class="{'border-secondary bg-light fw-semibold': returnForm.will_refund === 'no'}">
                                    <input type="radio" class="form-check-input mt-0" name="will_refund_opt" value="no" x-model="returnForm.will_refund" x-on:change="returnForm.paymentmethod_id = ''; returnForm.bank_account_id = ''; returnModalError = ''">
                                    <span>No — Do Not Refund</span>
                                </label>
                            </div>

                            {{-- If YES: Refund Amount (read-only) + Payment Method (from Paymentmethod model: CASH / TRANSFER) --}}
                            <div class="mt-3 p-3 border rounded bg-light" x-show="returnForm.will_refund === 'yes'" x-cloak>
                                <div class="row g-3">
                                    <div class="col-md-5">
                                        <label class="form-label fw-semibold small mb-1">Refund Amount (Auto-Calculated)</label>
                                        <input type="text" class="form-control fw-bold text-danger bg-white" :value="'₦' + money(computeReturnDifference())" readonly>
                                        <small class="text-muted">Locked to calculated return difference.</small>
                                    </div>
                                    <div class="col-md-7">
                                        <label class="form-label fw-semibold small mb-1">
                                            Refund Payment Method <span class="text-danger">*</span>
                                        </label>
                                        <div class="d-flex gap-2">
                                            @foreach($this->refundPaymentMethods as $pm)
                                                <label class="p-2 px-3 border rounded bg-white d-inline-flex align-items-center gap-2 flex-fill" style="cursor: pointer;" :class="{'border-primary bg-primary-subtle fw-semibold': String(returnForm.paymentmethod_id) === '{{ $pm['id'] }}'}">
                                                    <input type="radio" class="form-check-input mt-0" name="refund_method_opt" value="{{ $pm['id'] }}" x-model="returnForm.paymentmethod_id" x-on:change="@if(!$pm['is_transfer']) returnForm.bank_account_id = ''; @endif returnModalError = ''">
                                                    <span>
                                                        <i class="fa {{ $pm['is_transfer'] ? 'fa-university' : 'fa-money-bill-wave' }} me-1"></i>
                                                        {{ ucwords(strtolower($pm['name'])) }}
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>

                                    {{-- If Transfer: Bank Account selection --}}
                                    <div class="col-12" x-show="isTransferRefundSelected()" x-cloak>
                                        <label class="form-label fw-semibold small mb-1">
                                            Select Bank Account Used for Transfer Refund <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select" x-model="returnForm.bank_account_id" x-on:change="returnModalError = ''">
                                            <option value="">-- Select Configured Bank Account --</option>
                                            @foreach($this->bankAccounts as $ba)
                                                <option value="{{ $ba['id'] }}">
                                                    {{ $ba['name'] ?? 'Bank' }} &mdash; {{ $ba['account_name'] ?? '' }} ({{ $ba['account_number'] ?? '' }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            {{-- If NO: Not Refunded confirmation note --}}
                            <div class="mt-2 p-2 px-3 border rounded bg-light small text-muted" x-show="returnForm.will_refund === 'no'" x-cloak>
                                <i class="fa fa-info-circle me-1"></i>
                                Refund status will be saved as <strong>Not Refunded</strong> (no refund payment outflow recorded).
                            </div>
                        </div>

                        {{-- 4. Final Confirmation Summary --}}
                        <div
                            class="p-3 rounded border border-primary bg-primary-subtle"
                            x-show="returnForm.return_reason !== '' && returnForm.will_refund !== ''"
                            x-cloak
                        >
                            <h6 class="fw-bold text-primary mb-2">
                                <i class="fa fa-check-circle me-1"></i> Final Return &amp; Refund Summary
                            </h6>
                            <ul class="small mb-0 ps-3 text-dark">
                                <li>
                                    <strong>Reason:</strong>
                                    <span x-text="returnForm.return_reason"></span>
                                    <span x-show="returnForm.return_reason === 'Other reason.' && returnForm.other_reason_explanation" x-text="' (' + returnForm.other_reason_explanation + ')'"></span>
                                </li>
                                <li>
                                    <strong>Original Invoice:</strong> ₦<span x-text="money(originalInvoiceRaw)"></span>
                                    &rarr;
                                    <strong>New Invoice Total:</strong> ₦<span x-text="money(computeNet())"></span>
                                    (<strong>Return Difference:</strong> ₦<span x-text="money(computeReturnDifference())"></span>)
                                </li>
                                <li>
                                    <strong>Customer Refund:</strong>
                                    <template x-if="returnForm.will_refund === 'yes'">
                                        <span class="text-danger fw-bold">
                                            Refunding ₦<span x-text="money(computeReturnDifference())"></span>
                                            via <span x-text="selectedRefundMethodLabel() || '(select method)'"></span>
                                            <span x-show="isTransferRefundSelected() && selectedBankAccountLabel()" x-text="' — ' + selectedBankAccountLabel()"></span>
                                        </span>
                                    </template>
                                    <template x-if="returnForm.will_refund === 'no'">
                                        <span class="fw-semibold">Not Refunded (₦0.00)</span>
                                    </template>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="modal-footer bg-light px-4 py-3 d-flex justify-content-between">
                        <button
                            type="button"
                            class="btn btn-outline-secondary"
                            :disabled="returnModalSubmitting"
                            x-on:click="closeReturnModal()"
                        >
                            Cancel / Modify Items
                        </button>
                        <button
                            type="button"
                            class="btn btn-success px-4"
                            :disabled="returnModalSubmitting"
                            x-on:click="confirmAndSubmitReturn()"
                        >
                            <span x-show="returnModalSubmitting" x-cloak class="spinner-border spinner-border-sm me-2" role="status"></span>
                            <i x-show="!returnModalSubmitting" class="fa fa-check me-1"></i>
                            Confirm &amp; Process Invoice Return
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div wire:ignore.self class="modal fade" id="productOptionsModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form wire:submit="saveProductOption">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $this->selectedProductName }} Options</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">

                        @foreach($this->selectedProductOption as $productOption)
                            <div class="mb-4">
                                <h6 class="fw-bold">{{ $productOption['optionName'] }}</h6>
                                @if($productOption['option'] == "select")
                                    <select required class="form-select product-option-select" onchange="addToBasePrice(this)" id="{{ \Illuminate\Support\Str::snake($productOption['optionName'].$productOption['option_id']) }}" wire:model="selectedProductInfo.selectedOptions.{{ $productOption['option_id'] }}">
                                        <option value="">-Select {{ $productOption['optionName'] }}-</option>
                                        @foreach($productOption['options'] as $option)
                                            <option prefix="{{ $option[$this->d == "retail" ? "retail_price_prefix" : "wholesales_price_prefix"] }}" amount="{{ $option[$this->d == "retail" ? "retail_price" : "wholesales_price"] }}" value="{{ $option['id'] }}">{{ $option['name'] }}  {{ $option[$this->d == "retail" ? "retail_price_prefix" : "wholesales_price_prefix"]." ".money($option[$this->d == "retail" ? "retail_price" : "wholesales_price"]) }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>
                        @endforeach
                        <hr>
                        <!-- Price summary -->
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1 base_price" base-price="{{ $this->selectedProductInfo['selling_price'] ?? 0 }}">Base Price</p>
                                <p class="mb-1">Options Adjustment</p>
                                <p class="fw-bold">Final Price</p>
                            </div>
                            <div class="col-md-6 text-end">
                                <p class="mb-1">NGN {{ money($this->selectedProductInfo['selling_price'] ?? 0) }}</p>
                                <p class="mb-1 text-success">NGN <span class="option-increment">0.00</span></p>
                                <p class="fw-bold">NGN <span class="total_option_price">{{ money($this->selectedProductInfo['selling_price'] ?? 0) }}</span></p>
                            </div>
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Cancel
                        </button>
                        <button type="submit" wire:target="saveProductOption" wire:loading.attr="disabled" wire:dis class="btn btn-primary">
                            Apply Options
                            <span wire:loading wire:target="saveProductOption" class="spinner-border spinner-border-sm me-2" role="status"></span>
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
    <script>
        var productOptionModal = "";
        function invoice() {
            return {
                isLoading: false,
                isReturn: @js((bool) $this->isReturn),
                originalInvoiceRaw: @js((float) $this->originalInvoiceAmount),
                storedDiscountType: @js((string) ($this->invoice->discount_type ?? 'None')),
                storedDiscountValue: @js((float) ($this->invoice->discount_value ?? 0)),
                storedDiscountAmount: @js((float) ($this->invoice->discount_amount ?? 0)),
                storedMembershipValue: @js((float) ($this->invoice->membership_discount_value ?? 0)),
                currentInvoiceRaw: 0,
                returnDifferenceRaw: 0,
                returnDifferenceFormatted: '0.00',
                bankAccountsList: @js($this->bankAccounts),
                refundPaymentMethodsList: @js($this->refundPaymentMethods),
                showReturnModal: false,
                returnModalSubmitting: false,
                returnModalError: '',
                pendingReturnStatusId: '',
                returnForm: {
                    return_reason: '',
                    other_reason_explanation: '',
                    will_refund: '',
                    paymentmethod_id: '',
                    bank_account_id: '',
                    idempotency_key: 'ret_{{ $this->invoice->id ?? 0 }}_' + Date.now() + '_' + Math.random().toString(36).substring(2, 9),
                },
                invoiceitems : (@this.get('invoiceData.invoiceitems') ? JSON.parse(@this.get('invoiceData.invoiceitems')).reverse() : []).map(function(item) {
                    const origQty = parseInt(item.max_return_qty ?? item.original_quantity ?? item.quantity);
                    item.original_quantity = origQty;
                    item.max_return_qty = origQty;
                    item.original_selling_price = parseFloat(item.selling_price);
                    // On an invoice return, ignore custom_price and lock selling_price to the original invoice price
                    if (@js((bool) $this->isReturn)) {
                        item.custom_price = [];
                        item.default_selling_price = parseFloat(item.selling_price);
                    }
                    return item;
                }),
                searchString : "",
                searchproduct : [],
                searchCustomers : [],
                errors : {},
            {{--prescriber_id : @this.get('invoiceData.prescriber_id') ? @this.get('invoiceData.prescriber_id') : null,--}}
                customer_id : @this.get('invoiceData.customer_id') ? @this.get('invoiceData.customer_id') : {firstname : ""},
            is_stock_exchange : @this.get('invoiceData.is_stock_exchange') ? true : false,
                invoice_type : @this.get('invoiceData.invoice_type') ? @this.get('invoiceData.invoice_type') : 'normal',
                supplier_id : @this.get('invoiceData.supplier_id') ? @this.get('invoiceData.supplier_id') : '',
                discount_type : @this.get('invoiceData.discount_type') ? @this.get('invoiceData.discount_type') : 'None',
                discount_value : @this.get('invoiceData.discount_value') ? @this.get('invoiceData.discount_value') : 0,
                discount_amount : @this.get('invoiceData.discount_amount') ? @this.get('invoiceData.discount_amount') : 0,
                membership_discount_value : @this.get('invoiceData.membership_discount_value') ? @this.get('invoiceData.membership_discount_value') : 0,
                membership_discount : @this.get('invoiceData.membership_discount') ? @this.get('invoiceData.membership_discount') : 0,
                membership_label : '',
                subTotal : '0.00',
                netTotal :  '0.00',
                quantity : [],
                department : @this.get('d') ? @this.get('d') : "",
                searchCustomerString : "",
                selectedProduct : {},
            selectProduct(product){
                if (this.isReturn) {
                    error("Cannot add new stock when returning an invoice.");
                    return;
                }
                this.selectedProduct = product;
                this.selling_price = product.selling_price;
                this.addItem();
            },
            addItem() {
                if (this.isReturn) {
                    error("Cannot add new stock when returning an invoice.");
                    return;
                }
                if ((this.invoiceitems.filter(e => e.stock_id === this.selectedProduct.id)).length > 0) {
                    error("Stock already exists");
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
                selling_price = parseFloat(selling_price);

                //check if the selected product has option and if the user has not selected any option
                if(this.selectedProduct.hasOptions && this.selectedProduct.selectedOptions === undefined) {
                    @this.triggerProductOptionModal(this.selectedProduct);
                    return;
                }

                this.invoiceitems.unshift({
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
                    selling_price : (selling_price + this.getProductOptionsTotalAmount(this.selectedProduct.selectedOptions)),
                    profit : this.selectedProduct.selling_price - this.selectedProduct.cost_price,
                    discount_value : 0,
                    discount_amount : 0,
                    discount_type : 'Fixed',
                    custom_price : this.selectedProduct.custom_prices ?? [],
                    default_selling_price : selling_price,
                    selectedOptions : this.selectedProduct.selectedOptions ?? [],
                    dependent_products : this.selectedProduct.dependent_products ?? [],
                    isDependent: false,
                    parent_stock_id: null
            });

                this.applyDependentProducts();
                this.totalInvoice();
            },
            getPriceRange(quantity, defaultSellingPrice, customPrices, product) {
                if (this.isReturn) {
                    return parseFloat(product?.original_selling_price ?? product?.selling_price ?? defaultSellingPrice);
                }

                for (const priceRule of (customPrices ?? [])) {
                    const min = parseInt(priceRule.min_qty);
                    const max = parseInt(priceRule.max_qty);
                    @if($this->d == "retail")
                    if (quantity >= min && quantity < max) {
                        return parseFloat(priceRule.price);
                    }
                    @else
                    if ((quantity/priceRule.carton) >= min && (quantity / priceRule.carton) < max) {
                        return parseFloat(priceRule.price);
                    }
                    @endif
                }

                return parseFloat(defaultSellingPrice);
            },
            incrementQuantity(index){
                let qty =  parseInt(this.invoiceitems[index]['quantity']) + 1;
                if (this.isReturn) {
                    const maxReturnQty = parseInt(this.invoiceitems[index]['max_return_qty'] ?? this.invoiceitems[index]['original_quantity'] ?? this.invoiceitems[index]['quantity']);
                    if (qty > maxReturnQty) {
                        error("Cannot increase quantity above original invoice quantity (" + maxReturnQty + ")");
                        return;
                    }
                }
                if(this.isReturn || qty <= this.invoiceitems[index]['av_qty'])
                {
                    if (this.isReturn) {
                        this.invoiceitems[index]['selling_price'] = parseFloat(this.invoiceitems[index]['original_selling_price'] ?? this.invoiceitems[index]['selling_price']);
                    } else {
                        this.invoiceitems[index]['selling_price'] = this.getPriceRange(qty, this.invoiceitems[index]['default_selling_price'],  this.invoiceitems[index]['custom_price'], this.invoiceitems[index]);
                        this.invoiceitems[index]['selling_price'] += this.getProductOptionsTotalAmount(this.invoiceitems[index]['selectedOptions']);
                    }

                    this.invoiceitems[index]['quantity'] = qty;

                    this.invoiceitems[index]['total_cost_price'] = this.invoiceitems[index]['cost_price'] * this.invoiceitems[index]['quantity'];
                    this.invoiceitems[index]['total_selling_price'] = this.invoiceitems[index]['selling_price'] * this.invoiceitems[index]['quantity'];
                    this.invoiceitems[index]['total_profit'] =  (this.invoiceitems[index]['selling_price'] - this.invoiceitems[index]['cost_price']) * this.invoiceitems[index]['quantity'];

                    this.applyDependentProducts();
                    this.totalInvoice();
                }
            },
            typeQuantity(index){
                if (this.isReturn) {
                    const maxReturnQty = parseInt(this.invoiceitems[index]['max_return_qty'] ?? this.invoiceitems[index]['original_quantity'] ?? 1);
                    if (parseInt(this.invoiceitems[index]['quantity']) > maxReturnQty) {
                        error("Cannot increase quantity above original invoice quantity (" + maxReturnQty + ")");
                        this.invoiceitems[index]['quantity'] = maxReturnQty;
                    }
                } else if(parseInt(this.invoiceitems[index]['quantity']) > this.invoiceitems[index]['av_qty'])
                {
                    error("Total available quantity is "+this.invoiceitems[index]['av_qty']);
                    this.invoiceitems[index]['quantity'] = this.invoiceitems[index]['av_qty'];
                }

                if (this.isReturn) {
                    this.invoiceitems[index]['selling_price'] = parseFloat(this.invoiceitems[index]['original_selling_price'] ?? this.invoiceitems[index]['selling_price']);
                } else {
                    this.invoiceitems[index]['selling_price'] = this.getPriceRange(this.invoiceitems[index]['quantity'], this.invoiceitems[index]['default_selling_price'],  this.invoiceitems[index]['custom_price'], this.invoiceitems[index]);
                    this.invoiceitems[index]['selling_price'] += this.getProductOptionsTotalAmount(this.invoiceitems[index]['selectedOptions']);
                }

                this.invoiceitems[index]['total_cost_price'] = this.invoiceitems[index]['cost_price'] * this.invoiceitems[index]['quantity'];
                this.invoiceitems[index]['total_selling_price'] = this.invoiceitems[index]['selling_price'] * this.invoiceitems[index]['quantity'];
                this.invoiceitems[index]['total_profit'] =  (this.invoiceitems[index]['selling_price'] - this.invoiceitems[index]['cost_price']) * this.invoiceitems[index]['quantity'];
                this.applyDependentProducts();
                this.totalInvoice();
            },
            decrementQuantity(index) {
                let qty =  parseInt(this.invoiceitems[index]['quantity']) - 1;
                if(qty > 0)
                {
                    if (this.isReturn) {
                        this.invoiceitems[index]['selling_price'] = parseFloat(this.invoiceitems[index]['original_selling_price'] ?? this.invoiceitems[index]['selling_price']);
                    } else {
                        this.invoiceitems[index]['selling_price'] = this.getPriceRange(qty, this.invoiceitems[index]['default_selling_price'],  this.invoiceitems[index]['custom_price'], this.invoiceitems[index]);
                        this.invoiceitems[index]['selling_price'] += this.getProductOptionsTotalAmount(this.invoiceitems[index]['selectedOptions']);
                    }

                    this.invoiceitems[index]['quantity'] = qty;
                    this.invoiceitems[index]['total_cost_price'] = this.invoiceitems[index]['cost_price'] * this.invoiceitems[index]['quantity'];
                    this.invoiceitems[index]['total_selling_price'] = this.invoiceitems[index]['selling_price'] * this.invoiceitems[index]['quantity'];
                    this.invoiceitems[index]['total_profit'] =  (this.invoiceitems[index]['selling_price'] - this.invoiceitems[index]['cost_price']) * this.invoiceitems[index]['quantity'];

                    this.applyDependentProducts();
                    this.totalInvoice();
                }
            },
            deleteItem(parentStockId) {
                this.invoiceitems = this.invoiceitems.filter(item =>
                    item.stock_id !== parentStockId &&
                    item.parent_stock_id !== parentStockId
                );

                this.totalInvoice();
            },
            select2()
            {
                $('#select2').select2();
            },
            initSupplierSelect2()
            {
                let obj = this;
                const setup = () => {
                    if (typeof window.jQuery === 'undefined' || typeof window.jQuery.fn.select2 !== 'function') {
                        setTimeout(setup, 100);
                        return;
                    }
                    const $el = window.jQuery('#stock_exchange_supplier_select');
                    if (!$el.length) {
                        return;
                    }
                    if ($el.hasClass('select2-hidden-accessible')) {
                        $el.select2('destroy');
                    }
                    $el.select2({
                        placeholder: '-- Search & Select Supplier --',
                        allowClear: true,
                        width: '100%'
                    });
                    if (obj.supplier_id) {
                        $el.val(obj.supplier_id).trigger('change.select2');
                    }
                    $el.off('change.sfs').on('change.sfs', function () {
                        obj.supplier_id = window.jQuery(this).val() || '';
                    });
                };

                this.$nextTick(() => {
                    setup();
                });
            },
            resolveMembershipLabel() {
                if (this.department === 'retail' && this.customer_id && this.customer_id.firstname !== "" && this.membership_discount_value > 0) {
                    let group = this.customer_id.retail_member_group;
                    if (group) {
                        this.membership_label = group.name;
                    }
                }
            },
            // Pure calculators (no writes) so every on-screen amount re-evaluates automatically
            // whenever any item quantity/price changes (plus, minus, typing or delete)
            computeSubTotal() {
                return (this.invoiceitems || []).reduce((result, item) => {
                    const unitNetPrice = (parseFloat(item.selling_price) || 0) - (parseFloat(item.discount_amount) || 0);
                    return result + (unitNetPrice * (parseFloat(item.quantity) || 0));
                }, 0);
            },
            computeDiscounts(sub) {
                if (this.isReturn) {
                    // Keep the discount terms the invoice was originally issued with
                    let discount = 0;
                    if (this.storedDiscountType === 'Percentage' && this.storedDiscountValue > 0) {
                        discount = Math.round(Math.abs((this.storedDiscountValue / 100) * sub));
                    } else if (this.storedDiscountAmount > 0) {
                        discount = this.storedDiscountAmount;
                    }
                    const membership = this.storedMembershipValue > 0
                        ? Math.abs((this.storedMembershipValue / 100) * sub)
                        : 0;
                    return {discount: discount, membership: membership};
                }

                const discountValue = parseFloat(this.discount_value) || 0;
                let discount = 0;
                if (this.discount_type === 'Percentage') {
                    discount = Math.round(Math.abs((discountValue / 100) * sub));
                } else if (this.discount_type === 'Fixed') {
                    discount = discountValue;
                }
                let membership = 0;
                const membershipValue = parseFloat(this.membership_discount_value) || 0;
                if (this.department === 'retail' && membershipValue > 0) {
                    membership = Math.abs((membershipValue / 100) * sub);
                }
                return {discount: discount, membership: membership};
            },
            computeNet() {
                const sub = this.computeSubTotal();
                const d = this.computeDiscounts(sub);
                return Math.max(0, sub - (d.discount + d.membership));
            },
            computeReturnDifference() {
                return Math.max(0, (parseFloat(this.originalInvoiceRaw) || 0) - this.computeNet());
            },
            totalInvoice()
            {
                let sub_total = this.computeSubTotal();

                const d = this.computeDiscounts(sub_total);
                this.discount_amount = d.discount;
                this.membership_discount = d.membership;
                this.subTotal = this.money(sub_total);
                const netRaw = Math.max(0, sub_total - (this.discount_amount + this.membership_discount));
                this.currentInvoiceRaw = netRaw;
                this.netTotal = this.money(netRaw);
                this.returnDifferenceRaw = Math.max(0, (parseFloat(this.originalInvoiceRaw) || 0) - netRaw);
                console.log(sub_total)
                this.returnDifferenceFormatted = this.money(this.returnDifferenceRaw);
                return true;
            },
            selectedRefundMethodObj() {
                if (!this.returnForm.paymentmethod_id) return null;
                return (this.refundPaymentMethodsList || []).find(pm => String(pm.id) === String(this.returnForm.paymentmethod_id)) || null;
            },
            selectedRefundMethodLabel() {
                const pm = this.selectedRefundMethodObj();
                return pm ? pm.name : '';
            },
            isTransferRefundSelected() {
                const pm = this.selectedRefundMethodObj();
                return Boolean(pm && pm.is_transfer);
            },
            selectedBankAccountLabel() {
                if (!this.returnForm.bank_account_id) return '';
                const found = (this.bankAccountsList || []).find(b => String(b.id) === String(this.returnForm.bank_account_id));
                if (!found) return '';
                return (found.name || 'Bank') + ' - ' + (found.account_name || '') + ' (' + (found.account_number || '') + ')';
            },
            openReturnModal(status_id) {
                this.totalInvoice();
                this.pendingReturnStatusId = status_id;
                this.returnModalError = '';
                this.showReturnModal = true;
            },
            closeReturnModal() {
                this.showReturnModal = false;
                this.returnModalError = '';
            },
            confirmAndSubmitReturn() {
                this.returnModalError = '';

                if (!this.returnForm.return_reason) {
                    this.returnModalError = 'Please select the reason for this invoice return.';
                    return;
                }

                if (this.returnForm.return_reason === 'Other reason.' && !(this.returnForm.other_reason_explanation || '').trim()) {
                    this.returnModalError = 'Please provide an explanation for selecting Other reason.';
                    return;
                }

                if (!this.returnForm.will_refund) {
                    this.returnModalError = 'Please select whether you will be refunding the customer.';
                    return;
                }

                if (this.returnForm.will_refund === 'yes') {
                    if (!(this.computeReturnDifference() > 0)) {
                        this.returnModalError = 'The return difference is ₦0.00. Reduce item quantity or choose "No — Do Not Refund".';
                        return;
                    }
                    if (!this.returnForm.paymentmethod_id) {
                        this.returnModalError = 'Please select the refund payment method (Cash or Transfer).';
                        return;
                    }
                    if (this.isTransferRefundSelected() && !this.returnForm.bank_account_id) {
                        this.returnModalError = 'Please select the bank account used for the Transfer refund.';
                        return;
                    }
                }

                this.returnModalSubmitting = true;
                this.generateInvoice(this.pendingReturnStatusId, true);
            },
            async searchProduct() {
                if (this.isReturn) {
                    this.searchproduct = [];
                    return;
                }
                if (this.searchString !== "" && this.searchString.length > 3) {
                    this.searchproduct = await (await fetch('{{ route('findstock') }}?query=' + this.searchString+"&column="+this.department
                    )).
                    json();
                }else
                {
                    this.searchproduct = [];
                }
            },
            newCustomerEvent(){

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
                    if (this.invoice_type === 'stock_exchange') {
                        this.initSupplierSelect2();
                    }
                });
            },
            money(amount){
                return formatMoney(amount);
            },
            async searchCustomer(){
                const term = (this.searchCustomerString || "").trim();
                // cancel any in-flight search so slow, stale responses can't overwrite newer ones
                if (this._custAbort) { this._custAbort.abort(); this._custAbort = null; }
                if (term.length < 2) {
                    this.searchCustomers = [];
                    return;
                }
                const controller = new AbortController();
                this._custAbort = controller;
                try {
                    const resp = await fetch('{{ route('findcustomer') }}?query=' + encodeURIComponent(term), {
                        signal: controller.signal,
                        headers: {'Accept': 'application/json'}
                    });
                    const data = await resp.json();
                    if (this._custAbort === controller) {
                        this.searchCustomers = Array.isArray(data) ? data.slice(0, 15) : [];
                        this._custAbort = null;
                    }
                } catch (e) {
                    if (e.name !== 'AbortError') {
                        this.searchCustomers = [];
                    }
                }
            },
            selectCus(customer) {
                this.customer_id = customer;
                this.membership_discount_value = 0;
                this.membership_label = '';

                if (this.department === 'retail') {
                    let group = customer.retail_member_group;
                    if (group && group.member_discount > 0) {
                        // Validate discount expiry if applicable
                        if (!group.discount_until || new Date(group.discount_until) >= new Date().setHours(0,0,0,0)) {
                            this.membership_discount_value = group.member_discount;
                            this.membership_label = group.name;
                            success("Membership discount of " + group.member_discount + "% has been applied!");
                        }
                    }
                }

                this.totalInvoice();
                this.searchCustomerString = "";
                this.searchCustomers = [];
            },
            generateInvoice(status_id, confirmedFromReturnModal = false) {
                const hasZero = this.invoiceitems.some(item => item.quantity <= 0);
                if(hasZero) {
                    this.returnModalSubmitting = false;
                    error("One or more items in your cart have a quantity of zero or less. Please review your input and try again")
                    return ;
                }
                @php $canStockExchange = userCanView('invoiceandsales.createStockExchangeInvoice') && $this->d !== 'retail' && $this->d !== 'retail_store' && $this->department != "4"; @endphp
                const isStockExchange = {{ $canStockExchange ? 'true' : 'false' }} && (this.invoice_type === 'stock_exchange' || this.is_stock_exchange === true);
                if (isStockExchange) {
                    if (!this.supplier_id || this.supplier_id === "") {
                        this.returnModalSubmitting = false;
                        error("Please select a Supplier for this Stock Exchange Invoice.");
                        return;
                    }
                } else {
                    @php
                        if( $this->department != "4") {
                    @endphp
                    if(this.customer_id.firstname === "") {
                        this.returnModalSubmitting = false;
                        error("You have not select a customer for this invoice,  please select a customer by searching!...")
                        return ;
                    }
                    @php
                        }
                    @endphp
                }

                if(this.invoiceitems.length === 0)
                {
                    this.returnModalSubmitting = false;
                    error("Invoice items list is empty, please add at least one product to generate invoice")
                    return ;
                }

                // In Return Invoice Mode, require the Return Reason & Customer Refund Confirmation step first
                if (this.isReturn && !confirmedFromReturnModal) {
                    this.openReturnModal(status_id);
                    return;
                }

                @php
                    if( $this->department != "4") {
                @endphp
                @this.set('invoiceData.customer_id', this.customer_id.id ?? null, true);
                @php
                    }else{
                @endphp
                if(this.customer_id.firstname !== "")
                {
                    @this.set('invoiceData.customer_id',this.customer_id.id, true);
                }else{
                    @this.set('invoiceData.customer_id', isStockExchange ? null : "1", true);
                }

                @php
                    }
                @endphp

                @this.set('invoiceData.is_stock_exchange', isStockExchange, true);
                @this.set('invoiceData.invoice_type', isStockExchange ? 'stock_exchange' : 'normal', true);
                @this.set('invoiceData.supplier_id', isStockExchange ? this.supplier_id : null, true);
                @this.set('invoiceData.status_id', status_id, true);
                @this.set('invoiceData.invoiceitems', JSON.stringify(this.invoiceitems), true);
                @this.set('invoiceData.discount_type', this.discount_type, true);
                @this.set('invoiceData.discount_value', this.discount_value, true);
                @this.set('invoiceData.discount_amount', this.discount_amount, true);
                @this.set('invoiceData.membership_discount_value', this.membership_discount_value, true);
                @this.set('invoiceData.membership_discount', this.membership_discount, true);

                if (this.isReturn) {
                    @this.set('invoiceData.return_info', {
                        return_reason: this.returnForm.return_reason,
                        other_reason_explanation: this.returnForm.other_reason_explanation,
                        will_refund: this.returnForm.will_refund,
                        paymentmethod_id: this.returnForm.will_refund === 'yes' ? this.returnForm.paymentmethod_id : null,
                        bank_account_id: (this.returnForm.will_refund === 'yes' && this.isTransferRefundSelected()) ? this.returnForm.bank_account_id : null,
                        idempotency_key: this.returnForm.idempotency_key,
                    }, true);
                }

                this.errors = {};
                let obj = this;
                @this.generateInvoice().then(function(resp){
                    obj.returnModalSubmitting = false;

                    if(!resp || resp.status !== true)
                    {
                        obj.errors = (resp && resp.errors) ? resp.errors : {};
                        const returnErrKeys = ['return_reason', 'other_reason_explanation', 'will_refund', 'refund_payment_method', 'bank_account_id', 'refund_amount', 'invoice'];
                        for (const k of returnErrKeys) {
                            if (obj.errors[k]) {
                                obj.returnModalError = Array.isArray(obj.errors[k]) ? obj.errors[k][0] : obj.errors[k];
                                break;
                            }
                        }
                    }else {
                        obj.showReturnModal = false;
                        setTimeout(() => {
                            window.location.href = '{{ route('invoiceandsales.create') }}';
                        }, 1000);
                    }

                }).catch(function() {
                    obj.returnModalSubmitting = false;
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
            async requestProductWithBarcode(barcode) {
                if (this.isReturn) {
                    error("Cannot add new stock or increase quantity when returning an invoice.");
                    return;
                }
                if (this.isLoading) return;
                this.isLoading = true;
                try {
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
                } catch (err) {
                    console.error(err);
                } finally {
                    this.isLoading = false;
                }
            },
            async parseCustomerQrCode(barcode) {
                if (this.isLoading) return;
                this.isLoading = true;
                var obj = this;

                @this.parseCustomerFromQrCode(barcode).then(function (response){
                    obj.isLoading = false;
                    if(response.status === true) {
                        obj.customer_id = response.customer;
                        if (response.membership_discount > 0) {
                            obj.membership_discount_value = response.membership_discount;
                            obj.membership_label = response.membership_label;
                            success("Membership discount of " + response.membership_discount + "% has been applied!");
                        } else {
                            obj.membership_discount_value = 0;
                            obj.membership_label = '';
                        }
                        obj.totalInvoice();
                        obj.searchCustomerString = "";
                        obj.searchCustomers = [];
                    }else {
                        if(response.message.length > 0) {
                            error(response.message);
                        }
                    }
                }).catch(function(err){
                    obj.isLoading = false;
                    console.error(err);
                });
            },
            getInputFromBarcode() {
                var obj = this;
                $(document).ready(function(){
                    $(document).scannerDetection({
                        timeBeforeScanTest: 200, // wait for the next character for upto 200ms
                        endChar: [13], // be sure the scan is complete if key 13 (enter) is detected
                        avgTimeByChar: 40, // it's not a barcode if a character takes longer than 40ms// turn off scanner detection if an input has focus
                        startChar: [16], // Prefix character for the cabled scanner (OPL6845R)
                        ignoreIfFocusOn : ['customer-search-text', 'searchText'],
                        onComplete: function(barcode){
                            if (obj.isLoading) return;
                            barcode = barcode.trim();
                            if(/^\d+$/.test(barcode) && barcode.length < 50) {
                                obj.requestProductWithBarcode(barcode);
                            } else {
                                obj.parseCustomerQrCode(barcode);
                            }

                            const input = document.getElementById('searchText');
                            $(input).val("")

                            obj.searchCustomerString = "";
                            obj.searchCustomers = [];
                        }, // main callback function
                        scanButtonKeyCode: 116, // the hardware scan button acts as key 116 (F5)
                        scanButtonLongPressThreshold: 5, // assume a long press if 5 or more events come in sequence
                        onScanButtonLongPressed: function(){

                        }, // callback for long pressing the scan button
                        onError: function(string){}
                    });
                });
            },
            logProductNotExist() {
                const input = document.getElementById('searchText');
                // The product search box is not rendered in Return Invoice mode
                if (!input) return;
                let currentObj = this;
                input.addEventListener('keydown', function(event) {
                    const cleanBarCode = $(input).val().trim();
                    if (event.key === 'Enter' && (/^\d+$/.test(cleanBarCode) === false && $(input).val().length < 100) && currentObj.searchproduct.length === 0) {
                        @this.logProductNotExist($(input).val()).then(() => {
                            success($(input).val()+" has been saved successfully!")
                        });
                    }
                });
            },
            handleProductOptionModal() {
                let obj = this;
                if(productOptionModal == "") {
                    productOptionModal = new bootstrap.Modal(document.getElementById("productOptionsModal"), {});
                }


                window.addEventListener('openProductOptionModal', (e) => {
                    productOptionModal.show();
                });

                window.addEventListener('closeProductOptionModal', (e) => {
                    obj.selectedProduct = e.detail[0]['selectedProduct'];
                    obj.addItem();
                    productOptionModal.hide();
                });
            },
            renderSelectedOptions(selectedOptions) {
                if (!Array.isArray(selectedOptions) || selectedOptions.length === 0) {
                    return '';
                }

                let html = '<div class="small text-muted mt-1">';

                selectedOptions.forEach(option => {
                    let priceText = '';

                    if (option.amount && option.amount !== 0) {
                        priceText = ` ${option.sign} NGN ${this.money(option.amount)}`;
                    }

                    html += `
            <span class="me-2">
                ${option.option_name}:
                <span class="badge bg-primary">
                    ${option.selectedValue}${priceText}
                </span>
            </span>
        `;
                });

                html += '</div>';
                return html;
            },
            getProductOptionsTotalAmount(selectedOptions) {
                if (!Array.isArray(selectedOptions)) {
                    return 0;
                }

                return selectedOptions.reduce((total, option) => {
                    let amount = parseFloat(option.amount) || 0;

                    if (option.sign === '-') {
                        amount = -amount;
                    }

                    return total + amount;
                }, 0);
            },
            applyDependentProducts() {
                const parents = this.invoiceitems.filter(i => !i.isDependent);

                parents.forEach(parentItem => {

                    if (!Array.isArray(parentItem.dependent_products)) return;

                    parentItem.dependent_products.forEach(dep => {
                        if (!dep || !Array.isArray(dep.dependent_info) || !dep.dependent_info[0]) return;
                        const info = dep.dependent_info[0];

                        const qty = Math.floor(parentItem.quantity / info.parent) * info.child;

                        const existingIndex = this.invoiceitems.findIndex(i =>
                            i.isDependent &&
                            i.stock_id === dep.id &&
                            i.parent_stock_id === parentItem.stock_id
                        );

                        if (qty > 0) {
                            if (existingIndex === -1) {
                                this.invoiceitems.push(
                                    this.buildDependentItem(dep, qty, parentItem)
                                );
                            } else {
                                this.invoiceitems[existingIndex].quantity = qty;
                            }
                        } else {
                            if (existingIndex !== -1) {
                                this.invoiceitems.splice(existingIndex, 1);
                            }
                        }
                    });
                });

                this.totalInvoice();
            },
            buildDependentItem(dep, qty, parentItem) {
                const sellingPrice = parseFloat(dep.selling_price);

                return {
                    stock_id: dep.id,
                    quantity: qty,
                    box: dep.box,
                    carton: dep.carton,
                    av_qty: dep.quantity,

                    customer_id : @this.get('invoiceData.customer_id') ?  @this.get('invoiceData.customer_id') :  {firstname : ""},

                name: dep.name + " (Dependent)",
                    added_by: this.userId, // pass from blade or alpine init
                    discount_added_by: null,

                    cost_price: parseFloat(dep.cost_price),
                    selling_price: sellingPrice,
                    profit: sellingPrice - parseFloat(dep.cost_price),

                    discount_value: 0,
                    discount_amount: 0,
                    discount_type: 'Fixed',

                    custom_price: dep.custom_prices ?? [],
                    default_selling_price: sellingPrice,

                    selectedOptions: [],             // 🚫 dependents have no options
                    dependent_products: [],           // 🚫 dependents cannot spawn others

                    // 🔑 dependency flags
                    isDependent: true,
                    parent_stock_id: parentItem.stock_id
            };
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

        function addToBasePrice() {
            let basePrice = parseFloat($('.base_price').attr('base-price'));
            let totalOption = 0;
            $('.product-option-select').each(function(index, option) {
                if($(option).val() !== "") {
                    const selectedOption = $(option).find('option:selected');
                    const prefix = selectedOption.attr('prefix');
                    const amount = parseFloat(selectedOption.attr('amount'));

                    if (prefix === "+") {
                        totalOption += amount;
                    } else {
                        totalOption -= amount;
                    }
                }
            });



            $('.option-increment').html(formatMoney(totalOption));
            basePrice = basePrice + totalOption;
            $('.total_option_price').html(formatMoney(basePrice))
        }
    </script>
</div>





