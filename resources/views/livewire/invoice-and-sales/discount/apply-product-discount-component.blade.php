<div x-data="applyProduct" x-init="totalInvoice(),customEvent()">
    <div class="row  border-bottom">
        <div class="col-4">
            <div class="card-sales-split" style="border-bottom: none">
                <h2>Invoice Reference : {{ $this->invoice->invoice_number }}</h2>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-4">


            <span class="d-block pb-1 pt-1"> Status : </span>
            <strong  class="d-block pb-1 pt-1">{!! showStatus($this->invoice->status) !!}</strong>
            <br/>
            <span class="d-block pb-1 pt-1"> Invoice Date : </span>
            <strong  class="d-block pb-1 pt-1">{!! convert_date($this->invoice->invoice_date) !!}</strong>
            <br/>

            <span class="d-block pb-1 pt-1"> Invoice Time : </span>
            <strong  class="d-block pb-1 pt-1">{!! twelveHourClock($this->invoice->sales_time) !!}</strong>
            <br/>

            <hr/>
            <h3>Invoice Properties</h3>
            <hr/>
            <span class="d-block pb-1 pt-1"> Created By : </span>
            <strong  class="d-block pb-1 pt-1">{{ $this->invoice->create_by->name ?? "Not Available" }}</strong>
            <br/>
            <span class="d-block pb-1 pt-1"> Last Updated By : </span>
            <strong  class="d-block pb-1 pt-1">{{ $this->invoice->last_updated->name ?? "Not Available" }}</strong>
            <br/>
            <span class="d-block pb-1 pt-1"> Picked By : </span>
            <strong  class="d-block pb-1 pt-1">{{ $this->invoice->picked->name ?? "Not Available" }}</strong>

            <br/>
            <span class="d-block pb-1 pt-1"> Checked By : </span>
            <strong  class="d-block pb-1 pt-1">{{ $this->invoice->checked->name ?? "Not Available" }}</strong>
            <br/>
            <span class="d-block pb-1 pt-1"> Packed By : </span>
            <strong  class="d-block pb-1 pt-1">{{ $this->invoice->packed->name ?? "Not Available" }}</strong>
            <br/>
            <span class="d-block pb-1 pt-1"> Dispatched By : </span>
            <strong  class="d-block pb-1 pt-1">{{ $this->invoice->dispatched->name ?? "Not Available" }}</strong>



        </div>
        <div class="col-8">
            <div class="row mt-3">
                <div class="col-12">
                    <hr/>
                    <h3>Set Product(s) Discount</h3>
                    <hr/>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-bordered" >
                            <thead>
                            <tr>
                                <th class="text-left" style="width: 40%;font-size: 14px">Name</th>
                                <th class="text-center" style="width: 10%;font-size: 14px">Quantity</th>
                                <th class="text-center" style="width: 10%;font-size: 14px">Rate</th>
                                <th class="text-center" style="width: 15%;font-size: 14px">Discount Type</th>
                                <th class="text-center" style="width: 15%;font-size: 14px">Discount value</th>
                                <th class="text-center" style="width: 15%;font-size: 14px">Discount Amount</th>
                                <th class="text-right" style="width: 10%;font-size: 14px">Total</th>
                            </tr>
                            </thead>
                            <thead>
                            <tr>
                                <th colspan="3">Apply Discount To All</th>
                                <th>
                                    <select x-model="alldiscount" x-on:change="setDiscountToAll()" class="form-control">
                                        <option value="None">None</option>
                                        <option value="Percentage">Percentage</option>
                                        <option value="Fixed">Fixed</option>
                                    </select>
                                </th>
                                <th>
                                    <input x-model="alldiscountValue" x-on:keyup="setDiscountToAll()"  class="form-control input-sm child_value" value="0" />
                                </th>
                                <th></th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>

                            @foreach($invoice->invoiceitems as $item)
                                <tr>
                                    <td class="text-left">{{ $item->stock->name }}</td>
                                    <td class="text-center">{{ $item->quantity }}</td>
                                    <td class="text-center" x-html="numberFormat({{ $item->selling_price }})">{{ number_format($item->selling_price,2) }}</td>
                                    <td>
                                        <select x-on:change="setDiscountType({{ $item->id }})" wire:model="discounts.{{ $item->id }}.discount_type"  class="form-control" x-model="discounts[{{ $item->id }}]['discount_type']" >
                                            <option value="None">None</option>
                                            <option value="Percentage">Percentage</option>
                                            <option value="Fixed">Fixed</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input x-on:keyup="calculateDiscount({{ $item->id }})" x-model="discounts[{{ $item->id }}]['discount_value']" wire:model="discounts.{{ $item->id }}.discount_value" class="form-control input-sm child_value" value="{{ $item->discount_value }}" />
                                    </td>
                                    <td class="text-center" x-html="numberFormat(discounts[{{ $item->id }}]['discount_amount'])">
                                        {{ money($item->discount_amount) }}
                                    </td>
                                    <td class="text-right" x-html="numberFormat((discounts[{{ $item->id }}]['quantity'] * (discounts[{{ $item->id }}]['selling_price'] - discounts[{{ $item->id }}]['discount_amount'])))">{{ money(($item->quantity * $item->selling_price) - $item->discount_amount) }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                            <tfoot>
                            <tr>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <th class="text-right">Sub Total</th>
                                <th class="text-right" x-html="netTotal">{{ money($invoice->sub_total) }}</th>
                            </tr>
                            <tr>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <th class="text-right">Discount</th>
                                <th class="text-right" x-html="'-'+numberFormat({{ $invoice->discount_amount }})">-{{ money($invoice->discount_amount,2) }}</th>
                            </tr>
                            <tr>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <th class="text-right">Total</th>
                                <th class="text-right" style="font-size: 16px;" x-html="allTotal">{{ money(($invoice->sub_total -$invoice->discount_amount),2) }}</th>
                            </tr>
                            </tfoot>
                        </table>
                    </div>

                    <br/>
                    <button type="button" wire:target="applyProductDiscount"  wire:loading.attr="disabled"  class="btn btn-lg btn-success" x-on:click="applyDiscount">
                        <span wire:loading wire:target="applyProductDiscount" class="spinner-border spinner-border-sm me-2" role="status"></span>
                        Apply Product Discount
                    </button>
                </div>
            </div>
        </div>


    </div>



    <script>


        function applyProduct()
        {
            return {
                discounts : @this.get('_discounts') ? JSON.parse( @this.get('_discounts')) : [],
            alldiscount : "Fixed",
            alldiscountValue : 0,
            netTotal : 0.00,
            allTotal : 0.00,
            setDiscountType(index){
            this.calculateDiscount(index);
        },
            calculateDiscount(index){
            let value = 0;
            if(this.discounts[index]['discount_type'] === "Percentage")
            {
                value = (this.discounts[index]['discount_value'] / 100) * this.discounts[index].selling_price;

            }else if(this.discounts[index]['discount_type'] === "Fixed")
            {
                value = this.discounts[index]['discount_value'];

            }else{
                value = 0;
            }
            this.discounts[index]['discount_amount'] = value;
            this.discounts[index]['total_selling_price'] =  (this.discounts[index].quantity * (this.discounts[index].selling_price - this.discounts[index]['discount_amount']));
            this.discounts[index]['profit'] =  (this.discounts[index].selling_price - this.discounts[index]['discount_amount']) -this.discounts[index].cost_price;
            this.discounts[index]['total_profit'] = (this.discounts[index].quantity * (this.discounts[index].selling_price - this.discounts[index]['discount_amount'])) - (this.discounts[index].cost_price * this.discounts[index].quantity)

            this.totalInvoice();
        },
            totalInvoice() {
            let total = 0;
            for (var key of Object.keys(this.discounts)) {
                this.discounts[key]['total_selling_price'] = (this.discounts[key].quantity * (this.discounts[key].selling_price - this.discounts[key].discount_amount))
                total += (this.discounts[key].quantity * (this.discounts[key].selling_price - this.discounts[key].discount_amount))
            }
            this.allTotal = this.numberFormat(total - {{ $invoice->discount_amount }});
            this.netTotal = this.numberFormat(total);
        },
            numberFormat(amount, decimalCount = 2, decimal = ".", thousands = ",") {
            try {
                decimalCount = Math.abs(decimalCount);
                decimalCount = isNaN(decimalCount) ? 2 : decimalCount;

                const negativeSign = amount < 0 ? "-" : "";

                let i = parseInt(amount = Math.abs(Number(amount) || 0).toFixed(decimalCount)).toString();
                let j = (i.length > 3) ? i.length % 3 : 0;

                return "&#8358;"+negativeSign + (j ? i.substr(0, j) + thousands : '') + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousands) + (decimalCount ? decimal + Math.abs(amount - i).toFixed(decimalCount).slice(2) : "");
            } catch (e) {
                console.log(e)
            }
        },

            applyDiscount()
            {
                @this.set('_discounts', JSON.stringify(this.discounts));
                @this.applyProductDiscount();
            },

            customEvent()
            {
                window.addEventListener('refreshBrowser', (e) => {
                    setTimeout(()=>{
                        window.location.href = e.detail[0].link;
                    }, 2000);
                });
            },
            setDiscountToAll()
            {
                for(var index of Object.keys(this.discounts)){
                    this.discounts[index]['discount_type'] = this.alldiscount;
                    this.discounts[index]['discount_value'] = this.alldiscountValue;
                    this.calculateDiscount(index);
                }
            }

        };
        }


    </script>

</div>
