@section('pageHeaderTitle','Product option values fields')
@section('pageHeaderDescription','Manage product option fields')

@section('pageHeaderAction')
    @if(userCanView('product_option.create'))
        <div class="row">
            <div class="col-sm">
                <div class="mb-4">
                    <button  wire:click="new" wire:target="new" wire:loading.attr="disabled" type="button" class="btn btn-primary waves-effect waves-light">
                        <i wire:loading.remove wire:target="new" class="bx bx-plus me-1"></i>
                        <span wire:loading wire:target="new" class="spinner-border spinner-border-sm me-2" role="status"></span>
                        New Product Option Field
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
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Search...">
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($this->get() as $type)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $type->name }}</td>
                            <td>
                                @if(userCanView('product_option.toggle_product_option_fields'))
                                    <div class="form-check form-switch mb-3" dir="ltr">
                                        <input wire:change="toggle({{ $type->id }})" id="user{{ $type->id }}" type="checkbox" class="form-check-input" id="customSwitch1" {{ $type->status ? 'checked' : '' }}>
                                        <label class="form-check-label" for="customSwitch1">{{ $type->status ? 'Active' : 'Inactive' }}</label>
                                    </div>
                                @else
                                    {{ $type->status ? 'Active' : 'Inactive' }}
                                @endif
                            </td>
                            <td>
                                @if(userCanView('product_option.update_product_option_fields'))
                                    <a class="btn btn-outline-primary btn-sm edit" wire:click="edit({{ $type->id }})" href="javascript:void(0);" >

                                        <span wire:loading wire:target="edit({{ $type->id }})" class="spinner-border spinner-border-sm me-2" role="status"></span>

                                        <i wire:loading.remove wire:target="edit({{ $type->id }})" class="fas fa-pencil-alt"></i>

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
