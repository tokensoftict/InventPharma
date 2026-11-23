@section('pageHeaderTitle', $this->title)
@section('pageHeaderDescription', $this->subtitle)

<div class="row mb-3">
    <div class="col-sm-7"></div>
    <div class="col-sm-5">
        <div class="float-end">

            @if(auth()->user()->can('update', $this->purchaseReturn))
                <a class="btn btn-success btn-sm" href="{{ route('purchasereturn.edit', $this->purchaseReturn->id) }}">
                    <i class="fa fa-pencil"></i> Edit Return
                </a>
            @endif

            @if(auth()->user()->can('complete', $this->purchaseReturn))
                <button type="button" class="btn btn-primary btn-sm"
                    onclick="confirm('Are you sure you want to complete this return? Stock will be deducted and the supplier balance reduced. This cannot be reversed.') || event.stopImmediatePropagation()"
                    wire:loading.attr="disabled"
                    wire:click="complete"
                    wire:target="complete,delete">
                    <span wire:loading wire:target="complete,delete" class="spinner-border spinner-border-sm me-2" role="status"></span>
                    <i wire:loading.remove wire:target="complete,delete" class="fa fa-check"></i>
                    Complete Return
                </button>
            @endif

            @if(auth()->user()->can('delete', $this->purchaseReturn))
                <button class="btn btn-danger btn-sm"
                    onclick="confirm('Are you sure you want to delete this return? This cannot be reversed.') || event.stopImmediatePropagation()"
                    wire:loading.attr="disabled"
                    wire:click="delete"
                    wire:target="delete,complete">
                    <i wire:loading.remove wire:target="complete,delete" class="fa fa-trash"></i>
                    <span wire:loading wire:target="complete,delete" class="spinner-border spinner-border-sm me-2" role="status"></span>
                    Delete Return
                </button>
            @endif

            <a href="{{ route('purchasereturn.print', $this->purchaseReturn->id) }}"
               onclick="window.open(this.href, 'PrintPurchaseReturn', 'width=860,height=680'); return false;"
               class="btn btn-info btn-sm">
                <i class="fa fa-print"></i> Print
            </a>

        </div>
    </div>

    @if(View::hasSection('pageHeaderTitle'))
        <br/> <br/>
        @include('shared.pageheader')
    @endif

    <div class="card-sales-split">
        <h2>Purchase Return Reference : #PRT-{{ $this->purchaseReturn->id }}</h2>
    </div>

    <div class="row mt-3">
        <div class="col-6">
            <address class="pt-1">
                <strong>{{ config('app.name') }}:</strong><br>
                <span class="d-block pb-1 pt-1">Created By : {{ $this->purchaseReturn->user->name ?? '—' }}</span>
                <span class="d-block pb-1 pt-1">Completed By : {{ $this->purchaseReturn->complete_by->name ?? '—' }}</span>
                <span class="d-block pb-1">Return Date : {{ eng_str_date($this->purchaseReturn->date_created) }}</span>
                <span class="d-block pb-1">Date Completed : {{ $this->purchaseReturn->date_completed ? eng_str_date($this->purchaseReturn->date_completed) : '—' }}</span>
                <span class="d-block pb-1">Status: {!! showStatus($this->purchaseReturn->status_id) !!}</span>
                <span class="d-block pb-1">Department : {{ \App\Classes\Settings::$department[$this->purchaseReturn->department] ?? $this->purchaseReturn->department }}</span>
                @if($this->purchaseReturn->purchase_id)
                    <span class="d-block pb-1">
                        Linked Purchase Order :
                        @if(userCanView('purchase.show'))
                            <a href="{{ route('purchase.show', $this->purchaseReturn->purchase_id) }}">#{{ $this->purchaseReturn->purchase_id }}</a>
                        @else
                            #{{ $this->purchaseReturn->purchase_id }}
                        @endif
                    </span>
                @endif
                @if($this->purchaseReturn->notes)
                    <span class="d-block pb-1">Notes : {{ $this->purchaseReturn->notes }}</span>
                @endif
            </address>
        </div>

        <div class="col-6 text-end">
            <strong>Supplier:</strong><br>
            <address class="pt-1">
                <span class="d-block pb-1 pt-1">{{ $this->purchaseReturn->supplier->name ?? '—' }}</span>
                <span class="d-block pb-1">{{ $this->purchaseReturn->supplier->address ?? '' }}</span>
                <span class="d-block pb-1">{{ $this->purchaseReturn->supplier->email ?? '' }}</span>
                <span class="d-block pb-1">{{ $this->purchaseReturn->supplier->phonenumber ?? '' }}</span>
            </address>
        </div>
    </div>

    <hr>
    <h5>Return Items</h5>
    <hr/>

    <div class="col-md-12">
        <br/>
        <div class="table-responsive">
            <table class="table table-hover table-bordered table-striped">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Product Name</th>
                    <th class="text-end">Quantity Returned</th>
                    <th class="text-end">Cost Price</th>
                    <th class="text-end">Expiry Date</th>
                    <th class="text-end">Batch Number</th>
                    <th class="text-start">Reason</th>
                    <th class="text-end">Total</th>
                </tr>
                </thead>
                <tbody>
                @foreach($this->purchaseReturn->items as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $item->name }}</td>
                        <td class="text-end">{{ $item->qty }}</td>
                        <td class="text-end">{{ number_format($item->cost_price, 2) }}</td>
                        <td class="text-end">{{ $item->expiry_date ?? '—' }}</td>
                        <td class="text-end">{{ $item->batch_no ?? '—' }}</td>
                        <td class="text-start">{{ $item->reason ?? '—' }}</td>
                        <td class="text-end">{{ number_format($item->qty * $item->cost_price, 2) }}</td>
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
                    <th></th>
                    <th class="text-end">Sub Total</th>
                    <th class="text-end">
                        {{ number_format($this->purchaseReturn->items->sum(fn($i) => $i->qty * $i->cost_price), 2) }}
                    </th>
                </tr>
                <tr>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th class="text-end">Total Return Value</th>
                    <th class="text-end">
                        {{ number_format($this->purchaseReturn->items->sum(fn($i) => $i->qty * $i->cost_price), 2) }}
                    </th>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Supplier ledger entry info (only for completed returns) --}}
    @if($this->purchaseReturn->status_id === status('Complete'))
        @php
            $ledgerEntry = $this->purchaseReturn->ledgerEntries->first();
        @endphp
        @if($ledgerEntry)
        <hr>
        <h5>Supplier Ledger Entry</h5>
        <hr/>
        <div class="col-md-6">
            <table class="table table-sm table-bordered">
                <tbody>
                <tr>
                    <th>Reference</th>
                    <td>{{ $ledgerEntry->description ?? 'PRT-'.$this->purchaseReturn->id }}</td>
                </tr>
                <tr>
                    <th>Type</th>
                    <td><span class="badge bg-success">{{ $ledgerEntry->type }}</span></td>
                </tr>
                <tr>
                    <th>Amount Applied</th>
                    <td>{{ number_format($ledgerEntry->amount, 2) }}</td>
                </tr>
                <tr>
                    <th>Date</th>
                    <td>{{ eng_str_date($ledgerEntry->created_at) }}</td>
                </tr>
                </tbody>
            </table>
        </div>
        @endif
    @endif

    <div></div>
</div>
