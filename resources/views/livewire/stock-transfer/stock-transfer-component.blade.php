<div x-data="stockTransfer" x-init="totalTransfer()">

    <div class="row">
        <div class="col-lg-12">
            <div class="col-lg-10 mt-5">
                <div class="row">
                    <div class="col-lg-4">
                        <div class="mb-3">
                            <label>Select Stock</label>
                            <select name="" id="select2" x-init="select2" class="form-control">
                                <option value="">Search For Stock</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="mb-3">
                            <label>Available Quantity</label>
                            <input type="number" x-model="available_quantity" readonly class="form-control">
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="mb-3">
                            <label>Transferring Quantity</label>
                            <input type="number" x-model="transferring_quantity" class="form-control">
                        </div>
                    </div>
                    <div class="col-lg-2">
                        <div class="mb-3">
                            <br>
                            <button type="button" x-on:click="addItem" class="btn btn-primary btn waves-effect waves-light mt-2">Add Stock</button>
                        </div>
                    </div>
                </div>
            </div>
            <br>
            <div class="col-lg-4 offset-8">
                <div class="mb-3">
                    <label>Transfer date</label>
                    <input type="text" readonly class="form-control" x-model="transfer_date">
                </div>
            </div>

            <br>

            <div class="table-responsive">
                <table class="table table-striped table-striped table-bordered" id="invoice-list">
                    <thead>
                    <tr>
                        <th class="text-start">Name</th>
                        <th class="text-center">Location</th>
                        <th class="text-center">Quantity{{ ((($this->to == 'retail' || $this->from== 'retail_store') and $this->from != 'retail_store') ? "(Retail Quantity)" : "") }}</th>
                        <th class="text-end">Price</th>
                        <th class="text-end">Total</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody id="appender">
                    <template x-for="(item,index) in stocktransferitems" :key="item.stock_id">
                        <tr>
                            <td class="text-start">
                                <span class="d-block"  x-text="item.name"></span>
                                <span  class="d-block text-danger" x-text="errors[item['stock_id']]"></span>
                            </td>
                            <td class="text-center" x-text="item.location"></td>
                            <td class="text-center" x-text="item.label_qty"></td>
                            <td class="text-end" x-text="money(item.cost_price ?? item.selling_price)"></td>
                            <td class="text-end" x-text="money(item.total)"></td>
                            <td><button class="btn btn-sm btn-primary" x-on:click="deleteItem(item.stock_id)">Delete</button></td>
                        </tr>
                    </template>
                    </tbody>
                    <tfoot>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <th class="text-end">Total</th>
                        <th class="text-end" x-text="netTotal">0.00</th>
                        <td></td>
                    </tr>
                    </tfoot>
                </table>
            </div>

            <br/>

            <div class="col-lg-12 mt-3">

                <button  wire:target="draftTransfer,completeTransfer" wire:loading.attr="disabled" type="button" x-on:click="draftTransfer()"  class="btn btn-primary btn-lg me-2">
                    <i wire:loading.remove wire:target="draftTransfer,completeTransfer" class="fa fa-check"></i>
                    <span wire:loading wire:target="draftTransfer,completeTransfer" class="spinner-border spinner-border-sm me-2" role="status"></span>
                    Draft Transfer
                </button>
                @if (userCanView('transfer.complete'))
                    &nbsp; &nbsp; &nbsp;
                    <button  wire:target="draftTransfer,completeTransfer" wire:loading.attr="disabled" type="button" x-on:click="completeTransfer()"  class="btn btn-success btn-lg me-2">
                        <i wire:loading.remove wire:target="draftTransfer,completeTransfer" class="fa fa-check"></i>
                        <span wire:loading wire:target="draftTransfer,completeTransfer" class="spinner-border spinner-border-sm me-2" role="status"></span>
                        Complete Transfer
                    </button>
                @endif
                <a href="{{ route('transfer.index') }}" class="btn btn-danger btn-lg">Cancel</a>
            </div>

        </div>


    </div>


    <script>

        function stockTransfer()
        {
            return {
                transfer_date : @this.get('data.transfer_date') ?  @this.get('data.transfer_date') : '{{ todaysDate() }}',
            from : @this.get('from') ?  @this.get('from') : "",
            to : @this.get('to') ?  @this.get('to') : "",
            note : "",
            available_quantity : "",
            transferring_quantity : "",
            selectedProduct : {},
            searchString : "",
            errors : {},
            searchproduct : [],
            netTotal : 0,
            stocktransferitems : @this.get('data.stocktransferitems') ? JSON.parse(@this.get('data.stocktransferitems'))  : [],
            totalTransfer(){
            this.netTotal = formatMoney(this.stocktransferitems.length > 0 ? this.stocktransferitems.reduce((result, item) => {
                return result + item.total;
            }, 0) : 0);
            return true;
        },
            deleteItem(id) {
            this.stocktransferitems = this.stocktransferitems.filter(item => id !== item.stock_id);
            this.totalTransfer();
        },
            addItem()
            {
                if ((this.stocktransferitems.filter(e => e.stock_id === this.selectedProduct.id)).length > 0) {
                    alert("Stock already exists");
                    return;
                }

                if(parseInt(this.transferring_quantity) > parseInt(this.available_quantity))
                {
                    alert("Insufficient Quantity to transfer, please check and try again");
                    return;
                }

                this.stocktransferitems.push({
                    name : this.selectedProduct.name,
                    stock_id : this.selectedProduct.id,
                    selling_price : this.selectedProduct.selling_price,
                    location: this.selectedProduct.location,
                    quantity : this.transferring_quantity,
                    label_qty: {!!   ($this->to == 'retail' and $this->from != 'retail_store') ?  'this.transferring_quantity+"("+(this.transferring_quantity * this.selectedProduct.box)+")"' : 'this.transferring_quantity' !!}{{ "," }}
                    cost_price : this.selectedProduct.cost_price,
                    stockbatch_id : "",
                    total : this.transferring_quantity * this.selectedProduct.cost_price,
                    user_id : {{ auth()->id() }}
                });

                this.transferring_quantity = "";
                this.available_quantity = "";
                $('#select2').empty().trigger('change');
                this.totalTransfer();
            },


            select2()
            {

                var path = '{{ route('findstock') }}'+"?column={{ $this->from }}&select2=yes"
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
                    if(data[0] !== undefined) {
                        obj.available_quantity = data[0].quantity
                            obj.selectedProduct = data[0];
                    }
                });

            },
            money(amount)
            {
                return formatMoney(amount);
            },

            completeTransfer()
            {
                if( this.stocktransferitems.length == 0)
                {
                    alert("Please add atleast one item to transfer");
                    return false;
                }
                this.errors = {};
                let obj = this;
                @this.set('data.status_id', '{{ status("Complete") }}', true);
                @this.set('data.user_id', '{{ auth()->id() }}', true);
                @this.set('data.transfer_date', '{{ todaysDate() }}', true);
                @this.set('data.stocktransferitems', this.stocktransferitems, true);

                @this.completeTransfer().then(function(resp){

                    if(resp.status !== true)
                    {
                        obj.errors = resp.errors;

                    }else {
                        setTimeout(() => {
                            window.location.href = '{{ route('transfer.index') }}';
                        }, 1100);
                    }
                })
            },


            draftTransfer()
            {
                if( this.stocktransferitems.length == 0)
                {
                    alert("Please add atleast one item to transfer");
                    return false;
                }
                this.errors = {};
                let obj = this;
                @this.set('data.status_id', '{{ status("Draft") }}', true);
                @this.set('data.user_id', '{{ auth()->id() }}', true);
                @this.set('data.transfer_date', '{{ todaysDate() }}', true);
                @this.set('data.stocktransferitems',this.stocktransferitems, true);

                @this.draftTransfer().then(function(resp){

                if(resp.status !== true)
                {
                    obj.errors = resp.errors;

                }else {
                    setTimeout(() => {
                        window.location.href = '{{ route('transfer.index') }}';
                    }, 1100);
                }
                })
            }

        }



        }

    </script>

</div>
