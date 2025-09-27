@section('pageHeaderTitle','Customer '.$this->type.' List')
@section('pageHeaderDescription','List Customer '.$this->type)

@section('pageHeaderAction')
    @if(userCanView('customer.create'))
        <div class="row">
            <div class="col-6">
                <div class="mb-4">
                    <button  wire:click="new" wire:target="new" wire:loading.attr="disabled" type="button" class="btn btn-primary waves-effect waves-light">
                        <i wire:loading.remove wire:target="new" class="bx bx-plus me-1"></i>
                        <span wire:loading wire:target="new" class="spinner-border spinner-border-sm me-2" role="status"></span>
                        New Customer
                    </button>
                </div>
            </div>

        </div>
    @endif
@endsection

<div>
    @if(View::hasSection('pageHeaderTitle'))
        @include('shared.pageheader')
    @endif

    <livewire:customer.datatable.customer-data-table :filters="$this->filters"/>

    <div wire:ignore.self class="modal fade" id="simpleComponentModal" tabindex="-1" role="dialog" aria-hidden="true">
        <form method="post" wire:submit="saveCustomers()">
            <div class="modal-dialog modal-dialog-centered" role="document">

                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $this->modalTitle }} {{ $this->modalName }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">

                            <div class="col-12">
                                <div class="row">
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label>Firstname</label>
                                            <input class="form-control" type="text" wire:model="firstname"  name="firstname" value="{{ $this->firstname }}" placeholder="Firstname">
                                            @error('firstname') <span class="text-danger">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label>Lastname</label>
                                            <input class="form-control" type="text" wire:model="lastname"  name="lastname" value="{{ $this->lastname }}" placeholder="Lastname">
                                            @error('lastname') <span class="text-danger">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">


                                    <div class="mb-3">
                                        <label>Email Address</label>
                                        <input class="form-control" type="email" wire:model="email"  name="email" value="{{ $this->email }}" placeholder="Email Address">
                                    </div>

                                    <div class="mb-3">
                                        <label>Phone Number</label>
                                        <input class="form-control" type="text" wire:model="phone_number"  name="phone_number" value="{{ $this->phone_number }}" placeholder="Phone Number">
                                        @error('phone_number') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label>City</label>
                                       <select class="form-control" wire:model="city_id">
                                           <option value="">Select City</option>
                                           @foreach($this->cities as $city)
                                               <option value="{{ $city->id }}">{{ $city->name }}</option>
                                           @endforeach
                                       </select>
                                    </div>

                                    <div class="mb-3">
                                        <label>Address</label>
                                        <textarea class="form-control" wire:model="address"  name="address" placeholder="Address"></textarea>
                                        @error('address') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>

                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="modal-footer ">
                        <button type="submit" wire:target="saveCustomers" wire:loading.attr="disabled" class="btn btn-primary">
                            <span wire:loading wire:target="saveCustomers" class="spinner-border spinner-border-sm me-2" role="status"></span>
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
