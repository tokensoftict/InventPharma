@section('pageHeaderTitle',$this->title)
@section('pageHeaderDescription', $this->subtitle)

@section('pageHeaderAction')
    <div class="row mb-3">
        <div class="col-sm-7"></div>
        <div class="col-sm-5">
            <div class="float-end">

                @if(auth()->user()->can('update', $this->stocktransfer))
                    <a class="btn btn-success btn-sm" href="{{ route('transfer.edit', $this->stocktransfer->id) }}"><i class="fa fa-pencil"></i> Edit Transfer</a>
                @endif
                @if(auth()->user()->can('complete', $this->stocktransfer))
                    <button type="button"  class="btn btn-primary btn-sm" onclick="confirm('Are you sure you want to complete this transfer ?, this can not be reversed') || event.stopImmediatePropagation()"  wire:loading.attr="disabled"  wire:click="complete" wire:target="complete,delete" >
                        <span wire:loading wire:target="complete,delete" class="spinner-border spinner-border-sm me-2" role="status"></span>
                        <i wire:loading.remove wire:target="complete,delete" class="fa fa-check"></i>
                        Complete Transfer
                    </button>
                @endif
                @if(auth()->user()->can('delete', $this->stocktransfer))
                    <button class="btn btn-danger btn-sm" onclick="confirm('Are you sure you want to delete this transfer ?, this can not be reversed') || event.stopImmediatePropagation()" wire:loading.attr="disabled"  wire:click="delete" wire:target="delete" >

                        <i  wire:loading.remove wire:target="complete,delete" class="fa fa-trash"></i>

                        <span wire:loading wire:target="complete,delete" class="spinner-border spinner-border-sm me-2" role="status"></span>

                        Delete Transfer
                    </button>
                @endif

                <a href="#" onclick="return open_print_window(this);" class="btn btn-info btn-sm"><i class="fa fa-print"></i> Print</a>

            </div>
        </div>
    </div>
@endsection

<div>
    @if(View::hasSection('pageHeaderTitle'))
        @include('shared.pageheader')
    @endif
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-sm-6">
                    <strong>Transfer From </strong><br/>
                    <span class="d-block pb-1 pt-1 text-start">{{ \App\Classes\Settings::$department[$this->stocktransfer->from] }}</span>
                    <br/>
                    <strong>Status</strong>
                    <span class="d-block pb-1 pt-1 text-start">{!!  showStatus($this->stocktransfer->status_id)  !!}</span>

                </div>
                <div class="col-sm-6">
                    <span class="d-block pb-1 pt-1 text-end"><strong>Transfer To</strong></span>
                    <span class="d-block pb-1 pt-1 text-end">{{ \App\Classes\Settings::$department[$this->stocktransfer->to] }}</span>
                    <br/>
                    <h6 class="text-end">Date</h6>
                    <span class="d-block pb-1 pt-1 text-end">{!!  convert_date($this->stocktransfer->transfer_date)  !!}</span>
                </div>
            </div>

            <div class="row">
                <h5>Items List</h5>
                <table class="table table-bordered table-striped table-hover">
                    <thead>
                    <tr>
                        <th class="text-start">Name</th>
                        <th class="text-center">Location</th>
                        <th class="text-center">Selling Price</th>
                        <th class="text-center">Remaining Quantity</th>
                        @if($this->stocktransfer->to == "retail")
                            <th class="text-center">Quantity(Retail Quantity)</th>
                        @else
                            <th class="text-center">Quantity</th>
                        @endif
                        @if($this->stocktransfer->status_id == status("Complete"))
                            <th class="text-center">Rem. Quantity</th>
                        @endif
                        <th class="text-end">Total Selling Price</th>
                    </tr>
                    </thead>
                    <tbody>
                    @php
                        $total = 0;
                        $errors = session('errors')
                    @endphp
                    @foreach($this->stocktransfer->stocktransferitems as $trans)
                        @php
                            $total +=($trans->quantity * $trans->selling_price)
                        @endphp
                        <tr>
                            <td class="text-start">
                                {{ $trans->stock->name }}
                                @if(isset($this->errors[$trans->stock->id]))
                                    {!! error($this->errors[$trans->stock->id]) !!}
                                @endif
                            </td>
                            <td class="text-center">{{ $trans->stock->location }}</td>
                            <td class="text-center">{{ number_format($trans->selling_price,2) }}</td>
                            <td class="text-center">{{ $trans->rem_quantity }}</td>
                            @if($this->stocktransfer->to == "retail")
                                <td class="text-center">{{ $trans->quantity }}({{ $trans->quantity * $trans->stock->box }})</td>
                            @else
                                <td class="text-center">{{ $trans->quantity }}</td>
                            @endif
                            @if($this->stocktransfer->status_id == status('Complete'))
                                <td class="text-center">{{ $trans->rem_quantity }}</td>
                            @endif
                            <td class="text-end">{{ number_format(($trans->quantity * $trans->selling_price),2) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <th class="text-end">Total</th>
                        <th class="text-end">{{ number_format($total,2) }}</th>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
