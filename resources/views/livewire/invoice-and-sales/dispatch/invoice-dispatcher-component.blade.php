<div>

    <div class="row  border-bottom">
        <div class="col-12">
            <div class="card-sales-split" style="border-bottom: none">
                <h2>Invoice Reference : {{ $this->invoice->invoice_number }}</h2>
            </div>
        </div>
        <div class="col-12">

            <div class="mb-3">
                <label>Picked By</label>
                <select class="form-control form-control-lg"   wire:model="data.picked_by">
                    <option value="">Select User</option>
                    @foreach($this->users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
                @error('data.picked_by') <span class="text-danger">{{ $message }}</span>  @enderror
            </div>

            <div class="mb-3">
                <label>Checked By</label>
                <select class="form-control form-control-lg"   wire:model="data.checked_by">
                    <option value="">Select User</option>
                    @foreach($this->users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
                @error('data.checked_by') <span class="text-danger">{{ $message }}</span>  @enderror
            </div>

            <div class="mb-3">
                <label>Packed By</label>
                <select class="form-control form-control-lg"   wire:model="data.packed_by">
                    <option value="">Select User</option>
                    @foreach($this->users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
                @error('data.packed_by') <span class="text-danger">{{ $message }}</span>  @enderror
            </div>

            <div class="mb-3">
                <label>Dispatched By</label>
                <select class="form-control form-control-lg"  wire:model="data.dispatched_by">
                    <option value="">Select User</option>
                    @foreach($this->users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
                @error('data.dispatched_by') <span class="text-danger">{{ $message }}</span>  @enderror
            </div>

            <div class="mb-3">
                <label>Carton Number</label>
                <input type="text" wire:model="data.carton_no"  class="form-control">
                @error('data.carton_no') <span class="text-danger">{{ $message }}</span>  @enderror
            </div>

            <br/>  <br/>
            <button wire:click="dispatchedInvoice" wire:target="dispatchedInvoice" wire:loading.attr="disabled" style="width: 100%"  class="btn btn-primary btn-lg d-block bottom-100">
                Dispatched Invoice
                <i wire:loading.remove wire:target="dispatchedInvoice" class="fa fa-check"></i>
                <span wire:loading wire:target="dispatchedInvoice" class="spinner-border spinner-border-sm me-2" role="status"></span>
            </button>

        </div>
    </div>

</div>
