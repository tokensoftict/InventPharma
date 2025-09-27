@section('pageHeaderTitle','Supplier Manager')
@section('pageHeaderDescription','Manage All Suppliers')

@section('pageHeaderAction')
    @if(userCanView('supplier.create'))
        <div class="row">
            <div class="col-sm">
                <div class="mb-4">
                    <button  wire:click="new" wire:target="new" wire:loading.attr="disabled" type="button" class="btn btn-primary waves-effect waves-light">
                        <i wire:loading.remove wire:target="new" class="bx bx-plus me-1"></i>
                        <span wire:loading wire:target="new" class="spinner-border spinner-border-sm me-2" role="status"></span>
                        New Supplier
                    </button>
                </div>
            </div>
            <div class="col-sm-auto">

            </div>
        </div>
    @endif
@endsection

<div>
    @if(View::hasSection('pageHeaderTitle'))
        @include('shared.pageheader')
    @endif

    <div class="table-responsive">
        <livewire:purchase-order.datatable.supplier-balance-report :filters="[]"/>
    </div>

    <div wire:ignore.self class="modal fade" id="simpleComponentModal" tabindex="-1" role="dialog" aria-hidden="true">
        <form method="post" wire:submit="{{ $this->modalTitle === "New" ? 'saveSupplier()' : 'update('.$this->modelId.')' }}">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $this->modalTitle }} {{ $this->modalName }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">

                        <div class="col-12">
                            <div class="row">
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label>Name</label>
                                        <input class="form-control" type="text" wire:model="name"  name="name" value="{{ $this->name }}" placeholder="Name">
                                        @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label>Phone Number</label>
                                        <input class="form-control" type="text" wire:model="phonenumber"  name="phonenumber" value="{{ $this->phonenumber }}" placeholder="Phone Number">
                                        @error('phonenumber') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label>Email Address</label>
                                        <input class="form-control" type="text" wire:model="email"  name="email" value="{{ $this->email }}" placeholder="Email Address">
                                        @error('email') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="mb-3">
                                        <label>Address</label>
                                        <input class="form-control" type="text" wire:model="address"  name="address" value="{{ $this->address }}" placeholder="Address">
                                        @error('address') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>


                    </div>
                    <div class="modal-footer ">
                        <button type="submit" wire:target="saveSupplier,update" wire:loading.attr="disabled" class="btn btn-primary">
                            <span wire:loading wire:target="saveSupplier,update" class="spinner-border spinner-border-sm me-2" role="status"></span>
                            {{ $this->saveButton }}
                        </button>
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        window.onload = function (){
            let myModal = "";
            $(document).ready(function(){
                myModal = new bootstrap.Modal(document.getElementById("simpleComponentModal"), {});
            });
            window.addEventListener('openModal', (e) => {
                myModal.show();
            });
            window.addEventListener('closeModal', (e) => {
                myModal.hide();
            });
        }
    </script>
</div>
