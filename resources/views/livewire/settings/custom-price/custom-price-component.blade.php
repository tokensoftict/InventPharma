@section('pageHeaderTitle','Custom Price Manager')
@section('pageHeaderDescription','Manage All Custom Price')

@section('pageHeaderAction')
    @if(userCanView('customprice.create'))
        <div class="row">
            <div class="col-sm">
                <div class="mb-4">
                    <button  wire:click="new" wire:target="new" wire:loading.attr="disabled" type="button" class="btn btn-primary waves-effect waves-light">
                        <i wire:loading.remove wire:target="new" class="bx bx-plus me-1"></i>
                        <span wire:loading wire:target="new" class="spinner-border spinner-border-sm me-2" role="status"></span>
                        New Custom Price
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

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Is Default Price</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($this->get() as $custom_price)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $custom_price->name }}</td>
                            <td>{{ $custom_price->department == "retail" ? "Retail Department" : "Wholesales Department" }}</td>
                            <td>
                                @if(userCanView('customprice.toggle_default_price'))
                                    <div class="form-check form-switch mb-3" dir="ltr">
                                        <input wire:change="toggle_default_price({{ $custom_price->id }})" id="user{{ $custom_price->id }}" type="checkbox" class="form-check-input" id="customSwitch2" {{ $custom_price->default_price ? 'checked' : '' }}>
                                        <label class="form-check-label" for="customSwitch1">{{ $custom_price->default_price ? 'Yes' : 'No' }}</label>
                                    </div>
                                @else
                                    {{ $custom_price->default_price ? 'Yes' : 'No' }}
                                @endif
                            </td>
                            <td>
                                @if(userCanView('customprice.toggle'))
                                    <div class="form-check form-switch mb-3" dir="ltr">
                                        <input wire:change="toggle({{ $custom_price->id }})" id="user{{ $custom_price->id }}" type="checkbox" class="form-check-input" id="customSwitch1" {{ $custom_price->status ? 'checked' : '' }}>
                                        <label class="form-check-label" for="customSwitch1">{{ $custom_price->status ? 'Active' : 'Inactive' }}</label>
                                    </div>
                                @else
                                    {{ $custom_price->status ? 'Active' : 'Inactive' }}
                                @endif
                            </td>
                            <td>
                                @if(userCanView('customprice.update'))
                                    <a class="btn btn-outline-primary btn-sm edit" wire:click="edit({{ $custom_price->id }})" href="javascript:void(0);" >
                                        <span wire:loading wire:target="edit({{ $custom_price->id }})" class="spinner-border spinner-border-sm me-2" role="status"></span>
                                        <i wire:loading.remove wire:target="edit({{ $custom_price->id }})" class="fas fa-pencil-alt"></i>

                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @include('component.include.modal')
</div>