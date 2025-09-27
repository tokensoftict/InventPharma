@section('pageHeaderTitle',$this->title)
@section('pageHeaderDescription', $this->subtitle)

<div class="row mb-3">
    <div class="col-sm-7"></div>
    <div class="col-sm-5">
        <div class="float-end">

            @if(auth()->user()->can('update', $this->purchase))
                <a class="btn btn-success btn-sm" href="{{ route('purchase.edit', $this->purchase->id) }}"><i class="fa fa-pencil"></i> Edit Purchase</a>
            @endif
            @if(auth()->user()->can('complete',$this->purchase))
                <button type="button"  class="btn btn-primary btn-sm" onclick="confirm('Are you sure you want to complete this purchase ?, this can not be reversed') || event.stopImmediatePropagation()"  wire:loading.attr="disabled"  wire:click="complete" wire:target="complete,delete" >
                    <span wire:loading wire:target="complete,delete" class="spinner-border spinner-border-sm me-2" role="status"></span>
                    <i wire:loading.remove wire:target="complete,delete" class="fa fa-check"></i>
                    Complete Purchase
                </button>
            @endif
            @if(auth()->user()->can('delete', $this->purchase))
                <button class="btn btn-danger btn-sm" onclick="confirm('Are you sure you want to delete this purchase ?, this can not be reversed') || event.stopImmediatePropagation()" wire:loading.attr="disabled"  wire:click="delete" wire:target="delete" >

                    <i  wire:loading.remove wire:target="complete,delete" class="fa fa-trash"></i>

                    <span wire:loading wire:target="complete,delete" class="spinner-border spinner-border-sm me-2" role="status"></span>

                    Delete Purchase
                </button>
            @endif

            <a href="#" onclick="return open_print_window(this);" class="btn btn-info btn-sm"><i class="fa fa-print"></i> Print</a>

        </div>
    </div>
    @if(View::hasSection('pageHeaderTitle'))
        @include('shared.pageheader')
    @endif

    <div class="card-sales-split">
        <h2>Reference ID : #{{ $this->purchase->id }}</h2>
    </div>

    <div class="row mt-3">
        <div class="col-6">
            <address class="pt-1">
                <strong>{{ config('app.name') }}:</strong><br>
                <span class="d-block pb-1 pt-1"> Created By : {{ $this->purchase->user->name }}</span>
                <span class="d-block pb-1 pt-1"> Completed By : {{ $this->purchase->complete_by->name ?? "" }}</span>
                <span class="d-block pb-1">Date Created : {{ str_date2($this->purchase->date_created) }}</span>
                <span class="d-block pb-1">Date Completed : {{ str_date2($this->purchase->date_completed) }}</span>
                <span class="d-block pb-1"> Status: {!! showStatus($this->purchase->status_id) !!}</span>
                <span class="d-block pb-1"> Department : {{ \App\Classes\Settings::$department[$this->purchase->department ] }}</span>
            </address>
        </div>

        <div class="col-6 text-end"><strong>Supplier:</strong><br>
            <address class="pt-1">
                <span class="d-block pb-1 pt-1">  {{ $this->purchase->supplier->name }}</span>
                <span class="d-block pb-1"> {{ $this->purchase->supplier->address }}</span>
                <span class="d-block pb-1"> {{ $this->purchase->supplier->email }}</span>
                <span class="d-block pb-1"> {{ $this->purchase->supplier->phonenumber }}</span>
            </address>
        </div>
    </div>

    <hr>
    <h5>Purchase Order Items</h5>
    <hr/>

    <div class="col-md-12">
        <br/>
        <div class="table-responsive">
            <table class="table table-hover table-bordered table-striped">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Quantity</th>
                    <th>Cost Price</th>
                    <th>Expiry Date</th>
                    <th>Batch Number</th>
                    <th class="text-end">Total</th>
                </tr>
                </thead>
                <tbody>
                @foreach($this->purchase->purchaseitems as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $item->name }} </td>
                        <td>{{ $item->qty }}</td>
                        <td>{{ number_format($item->cost_price,2) }}</td>
                        <td>{{ $item->expiry_date ?? eng_str_date($item->expiry_date) }}</td>
                        <td>{{ $item->batch_no }}</td>
                        <td class="text-end">{{ number_format(($item->qty * $item->cost_price),2) }}</td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot>
                <tr>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th class="text-end">Sub Total</th>
                    <th class="text-end">{{ number_format((new \App\Repositories\PurchaseOrderRepository())->totalPo($this->purchase),2) }}</th>
                </tr>
                <tr>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th class="text-end">Total</th>
                    <th class="text-end">{{ number_format((new \App\Repositories\PurchaseOrderRepository())->totalPo($this->purchase),2) }}</th>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div>
</div>





