@section('pageHeaderTitle','Store Settings')
@section('pageHeaderDescription','Control System Behavior and other system settings')

@section('pageHeaderAction')

@endsection

<div>
    @if(View::hasSection('pageHeaderTitle'))
        @include('shared.pageheader')
    @endif

    <div class="card">
        <div class="card-body">
            <div class="col-6 offset-3">
                <div class="mb-3">
                    <label>Store Name</label>
                    <input type="text" wire:model="store.name"  class="form-control" name="name" placeholder="Store Name"/>
                    @error('store.name') <span class="text-danger">{{ $message }}</span> @enderror
                </div>

                <div class="mb-3">
                    <label>VAT</label>
                    <input  type="text"   wire:model="store.tax"  class="form-control" name="tax" placeholder="VAT"/>
                    @error('store.tax') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                <div class="mb-3">
                    <label>Threshold Days</label>
                    <input  type="number"  wire:model="store.threshold_days"  required class="form-control" name="threshold_days" placeholder="Threshold Days"/>
                    @error('store.threshold_days') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                <div class="mb-3">
                    <label>NOS Lead Time</label>
                    <input  type="number" wire:model="store.supply_days" required class="form-control" name="supply_days" placeholder="Number Of Days to Supply"/>
                    @error('store.supply_days') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                <div class="mb-3">
                    <label>Quantity to Buy Threshold</label>
                    <input  type="number" step="0.000001" wire:model="store.qty_to_buy_threshold" required class="form-control" name="qty_to_buy_threshold" placeholder="Quantity to Buy Threshold"/>
                    @error('store.qty_to_buy_threshold') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                <div class="mb-3">
                    <label>Material Near Expiry Days</label>
                    <input  type="number"  wire:model="store.material_near_expiry_days" required class="form-control" name="near_expiry_days" placeholder="Material Near Expiry Days"/>
                    @error('store.material_near_expiry_days') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                <div class="mb-3">
                    <label>Product Near Expiry Days</label>
                    <input  type="number"  wire:model="store.product_near_expiry_days" required class="form-control" name="product_near_expiry_days" placeholder="Product Near Expiry Days"/>
                    @error('store.product_near_expiry_days') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                <div class="mb-3">
                    <label>Factory Address Line</label>
                    <textarea name="first_address"  required class="form-control" wire:model="store.first_address" placeholder="Store Address"></textarea>
                    @error('store.first_address') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                <div class="mb-3">
                    <label>Factory Address Line 2</label>
                    <textarea name="second_address" class="form-control" wire:model="store.second_address"  placeholder="Store Address Line 2"></textarea>
                    @error('store.second_address') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                <div class="mb-3">
                    <label>Factory Contact Numbers</label>
                    <textarea name="contact_number" required class="form-control" wire:model="store.contact_number" placeholder="Store Contact Numbers"></textarea>
                    @error('store.contact_number') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                <div class="mb-3">
                    <label>Factory Logo</label>
                    <input type="file" id="formFile"  name="logo" wire:model="store.logo" style="width: 0;height: 0;padding: 0; margin: 0" >
                    <div class="form-control">
                        <br/>
                        <img src="{{ $this->store['logo'] !== NULL ? (is_string($this->store['logo']) ? asset('logo/'.$this->store['logo']) : $this->store['logo']->temporaryUrl()) : asset('images/brands/placholder.jpg') }}"   class="img-responsive" style="width:30%; margin: auto; display: block;"/>
                        <br/>
                        <div wire:loading wire:target="store.logo">Uploading...</div>
                        <button type="button" onclick="formFile.click()" class="btn btn-sm btn-success">Upload</button>
                    </div>
                    @error('store.logo') <span class="text-danger">{{ $message }}</span> @enderror
                </div>

                <div class="mb-3">
                    <label>Footer Receipt Notes</label>
                    <textarea name="footer_notes" class="form-control" wire:model="store.footer_notes" placeholder="Footer Receipt Notes"></textarea>
                    @error('store.footer_notes') <span class="text-danger">{{ $message }}</span> @enderror
                </div>

                @if(userCanView('store_settings.update'))
                <button type="button" wire:click="update" class="btn btn-lg btn-primary btn-block">
                    <i wire:loading.remove wire:target="update"  class="fa fa-save"></i>
                    <span wire:loading wire:target="update" class="spinner-border spinner-border-sm me-2" role="status"></span>
                    Save Changes
                </button>
                @endif

            </div>
        </div>
    </div>
</div>
