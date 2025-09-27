<div x-data="purchase" x-init="totalPurchase()">
    {{-- The whole world belongs to you. --}}

    <div class="row">
        <div class="col-sm-6">
            <div class="mb-3">
                <label>Select Stock</label>
                <select id="select2" x-init="select2" x-model="stock_id" class="form-control" id="product">
                    <option value="">-Select One-</option>
                </select>
            </div>
        </div>
    </div>
    <div class="row">

        <div class="col-sm-2">
            <div class="mb-3">
                <label style="white-space: nowrap;">Av. Quantity</label>
                <input type="number" readonly class="form-control" x-model="allqty" id="av_qty"/>
            </div>
        </div>

        <div class="col-sm-2">
            <div class="mb-3">
                <label>Recent Cost Price</label>
                <input  type="number" x-model="cost_price" class="form-control" id="cost_price"/>
            </div>
        </div>

        <div class="col-sm-2">
            <div class="mb-3">
                <label style="white-space: nowrap;">Quantity</label>
                <input type="number" class="form-control" x-model="quantity" id="qty"/>
            </div>
        </div>

        <div class="col-sm-2">
            <div class="mb-3">
                <label style="white-space: nowrap;">Expiry Date</label>
                <input type="text" class="form-control datepicker-basic" x-model="expiry_date" id="expiry_date"/>
            </div>
        </div>

        <div class="col-sm-2">
            <div class="mb-3">
                <label style="white-space: nowrap;">Batch Number</label>
                <input type="text" class="form-control" x-model="batch_no" id="batch_no"/>
            </div>
        </div>

        <div class="col-sm-2">
            <button class="btn  btn-primary" x-on:click="addItem" style="margin-top: 27px;" type="button">Add Stock</button>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-lg-8 offset-3">
            <div class="row">
                <div class="col-lg-4">
                    <div class="mb-3">
                        <label>Select Supplier</label>
                        <select class="form-control" x-init="select2Alpine('supplier_id')" id="supplier_id" x-ref="supplier_id" x-model="supplier_id" name="supplier_id">
                            <option value="">Select Supplier</option>
                            @foreach($this->suppliers as $supplier)
                                <option  value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-lg-4">
                    <label>Select Department</label>
                    <select class="form-control" name="department" x-init="select2Alpine('department')" id="department" x-model="department" wire:model="data.department">
                        <option value="">Select Department</option>
                        @foreach($this->depertments as $depertment)
                            <option selected value="{{ $depertment->quantity_column }}">{{ $depertment->label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-4">
                    <label>Purchase Date</label>
                    <input type="text" readonly wire:model="data.date_created" class="form-control">
                </div>
            </div>
        </div>
    </div>

    <div class="row">
       <div class="col-lg-12">
           <br/>
           <h4>Purchase Order</h4>
           <div class="table-responsive">
               <table class="table table-striped table-striped table-bordered">
                   <thead>
                   <tr>
                       <th>Product Name</th>
                       <th class="text-center">Quantity</th>
                       <th class="text-end">Cost Price</th>
                       <th class="text-center">Expiry Date</th>
                       <th class="text-center">Batch Number</th>
                       <th class="text-end">Total</th>
                       <th>Action</th>
                   </tr>
                   </thead>
                   <tbody>
                   <template x-for="(item,index) in purchaseitems" :key="item.stock_id">
                       <tr>
                           <td class="text-start" x-text="item.name"></td>
                           <td class="text-center" x-text="item.qty"></td>
                           <td class="text-end" x-text="money(item.cost_price)"></td>
                           <td class="text-center" x-text="item.expiry_date"></td>
                           <td class="text-center" x-text="item.batch_no"></td>
                           <td class="text-end" x-text="money(item.total)"></td>
                           <td class="text-end"><button class="btn btn-sm btn-primary" x-on:click="deleteItem(item.stock_id)">Delete</button> </td>
                       </tr>
                   </template>
                   </tbody>
                   <tfoot>
                   <tr>
                       <td></td>
                       <td></td>
                       <td></td>
                       <td></td>
                       <td class="text-end">Total</td>
                       <td class="text-end" x-text="netTotal">0.00</td>
                       <td></td>
                   </tr>
                   </tfoot>
               </table>
           </div>
       </div>
    </div>

    <div class="col-lg-12 mt-3">

        <button  wire:target="draftPurchase,completePurchase" wire:loading.attr="disabled" type="button" x-on:click="draftPurchaseOrder()"  class="btn btn-primary btn-lg me-2">
            <i wire:loading.remove wire:target="draftPurchase,completePurchase" class="fa fa-check"></i>
            <span wire:loading wire:target="draftPurchase,completePurchase" class="spinner-border spinner-border-sm me-2" role="status"></span>
            Draft Purchase
        </button>
        @if (userCanView('purchase.complete'))
        &nbsp; &nbsp; &nbsp;
        <button  wire:target="draftPurchase,completePurchase" wire:loading.attr="disabled" type="button" x-on:click="completePurchaseOrder()"  class="btn btn-success btn-lg me-2">
            <i wire:loading.remove wire:target="draftPurchase,completePurchase" class="fa fa-check"></i>
            <span wire:loading wire:target="draftPurchase,completePurchase" class="spinner-border spinner-border-sm me-2" role="status"></span>
            Complete Purchase
        </button>
        @endif
        <a href="{{ route('purchase.index') }}" class="btn btn-danger btn-lg">Cancel</a>
    </div>

    <script>
        function purchase()
        {
            return {
                supplier_id : @this.get('data.supplier_id') ? @this.get('data.supplier_id') : "",
                department : @this.get('data.department') ? @this.get('data.department') : "",
                stock_id : "",
                cost_price : "",
                allqty : 0,
                quantity : "",
                expiry_date: "",
                batch_no : "",
                selectStock : {},
                netTotal :0,
                purchaseitems : @this.get('data.purchaseitems') ?  JSON.parse(@this.get('data.purchaseitems')) : [],
                select2()
                {
                    var path = '{{ route('findpurchasestock') }}'+"?column={{ '' }}&select2=yes"
                    var obj = this;
                    var select =  $('#select2').select2({
                        placeholder: 'Select for product',
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
                        if(data[0] !==undefined) {
                            console.log(data);
                            obj.stock_id = data.id;
                            obj.cost_price = data[0].cost_price;
                            obj.quantity = 1;
                            obj.name = data.name;
                            obj.selectStock = data[0];
                            obj.allqty = data[0].allqty
                        }
                    });

                },
                select2Alpine(referred) {
                    this[referred+'_select2'] = $(document.getElementById(referred)).select2();
                    this[referred+'_select2'].on("select2:select", (event) => {
                        this[referred] = event.target.value;
                    });
                    if( this[referred] === "")
                    {
                        this[referred] = this[referred+'_select2'].val();
                    }
                    if( this[referred] !== "")
                    {
                        this[referred+'_select2'].select2('val', this[referred]);
                       $( document.getElementById(referred)).val(this[referred]).trigger('change');
                    }
                },
                totalPurchase(){
                    this.netTotal = this.money(this.purchaseitems.length > 0 ? this.purchaseitems.reduce((result, item) => {
                        return result + item.total;
                    }, 0) : 0);
                    return true;
                },
                deleteItem(id) {
                    this.purchaseitems = this.purchaseitems.filter(item => id !== item.stock_id);
                    this.totalPurchase();
                },
                addItem(){

                    if ((this.purchaseitems.filter(e => e.stock_id === this.selectStock.id)).length > 0) {
                        alert("Stock already exists");
                        return;
                    }

                    if(this.stock_id === "" || this.cost_price == "" || this.expiry_date == "")
                    {
                        alert("Please search and select a stock,enter cost price and expiry date to add to purchase items")
                        return;
                    }


                    if(this.batch_no === ""){
                        alert("Please enter batch number")
                        return ;
                    }

                    this.purchaseitems.push({
                        'stock_id' : this.selectStock.id,
                        'expiry_date' : this.expiry_date,
                        'qty' :this.quantity,
                        'name' : this.selectStock.name,
                        'cost_price' : this.cost_price,
                        'batch_no' : this.batch_no,
                        'user_id' : '{{ auth()->id() }}',
                        'total' : this.quantity * this.cost_price,
                        'status_id' : '{{ status("Pending") }}'
                    });

                    this.expiry_date = "";
                    this.cost_price = "";
                    this.quantity = "";
                    this.allqty = "";
                    this.expiry_date = "";
                    this.batch_no = "";
                    this.totalPurchase();
                    $('#select2').empty().trigger('change');


                 },

                 money(amount)
                {
                        return formatMoney(amount);
                 },

                 validatePurchase(){

                    if(this.supplier_id === "")
                    {
                        alert("Please select supplier");

                        return false;
                    }

                    if(this.department === "")
                    {
                        alert("Please select department");

                        return false;
                    }

                    if(this.purchaseitems.length === 0){

                        alert("Please add item to the purchase list");

                        return false;
                    }

                    return true;

                },
                draftPurchaseOrder(){

                    if(this.validatePurchase() == false) return;

                    @this.set('data.status_id', '{{ status("Draft") }}', true);
                    @this.set('data.user_id', '{{ auth()->id() }}', true);
                    @this.set('data.department', this.department, true);
                    @this.set('data.supplier_id', this.supplier_id, true);
                    @this.set('data.date_created', '{{ todaysDate() }}', true);
                    @this.set('data.purchaseitems', this.purchaseitems, true);

                    @this.draftPurchase().then(function(){
                            setTimeout(()=>{
                                window.location.href = '{{ route('purchase.index') }}';
                            },1100);
                     });

                 },
            completePurchaseOrder(){

                    if(this.validatePurchase() == false) return;

                    @this.set('data.status_id', '{{ status("Complete") }}', true);
                    @this.set('data.user_id', '{{ auth()->id() }}', true);
                    @this.set('data.completed_by', '{{ auth()->id() }}', true);
                    @this.set('data.department', this.department, true);
                    @this.set('data.supplier_id', this.supplier_id, true);
                    @this.set('data.date_created', '{{ todaysDate() }}', true);
                    @this.set('data.date_completed', '{{ todaysDate() }}', true);
                    @this.set('data.purchaseitems', this.purchaseitems, true);

                    @this.completePurchase().then(function(){
                            setTimeout(()=>{
                                window.location.href = '{{ route('purchase.index') }}';
                            },1100);
                    });
                }
            }
        }
    </script>
</div>
