@section('pageHeaderTitle','Customer Group Manager')
@section('pageHeaderDescription','Manage All Customer Groups')

@section('pageHeaderAction')
    @if(userCanView('customer_group.create'))
        <div class="row">
            <div class="col-sm">
                <div class="mb-4">
                    <button  wire:click="new" wire:target="new" wire:loading.attr="disabled" type="button" class="btn btn-primary waves-effect waves-light">
                        <i wire:loading.remove wire:target="new" class="bx bx-plus me-1"></i>
                        <span wire:loading wire:target="new" class="spinner-border spinner-border-sm me-2" role="status"></span>
                        New Customer Group
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
                        <th>Description</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($this->get() as $customerGroup)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $customerGroup->name }}</td>
                            <td>{{ $customerGroup->description }}</td>
                            <td>
                                @if(userCanView('customer_group.toggle'))
                                    <div class="form-check form-switch mb-3" dir="ltr">
                                        <input wire:change="toggle({{ $customerGroup->id }})" id="user{{ $customerGroup->id }}" type="checkbox" class="form-check-input" {{ $customerGroup->status ? 'checked' : '' }}>
                                        <label class="form-check-label" for="user{{ $customerGroup->id }}">{{ $customerGroup->status ? 'Active' : 'Inactive' }}</label>
                                    </div>
                                @else
                                    {{ $customerGroup->status ? 'Active' : 'Inactive' }}
                                @endif
                            </td>
                            <td>
                                @if(userCanView('customer_group.update'))
                                    <a class="btn btn-outline-primary btn-sm edit" wire:click="edit({{ $customerGroup->id }})" href="javascript:void(0);" >

                                        <span wire:loading wire:target="edit({{ $customerGroup->id }})" class="spinner-border spinner-border-sm me-2" role="status"></span>

                                        <i wire:loading.remove wire:target="edit({{ $customerGroup->id }})" class="fas fa-pencil-alt"></i>

                                    </a>
                                @endif
                                @if(userCanView('customer_group.destroy'))
                                    <a class="btn btn-outline-primary btn-sm delete confirm-text"  wire:click="destroy({{ $customerGroup->id }})" href="javascript:void(0);">

                                        <span wire:loading wire:target="destroy({{ $customerGroup->id }})" class="spinner-border spinner-border-sm me-2" role="status"></span>

                                        <i wire:loading.remove wire:target="destroy({{ $customerGroup->id }})" class="fas fa-trash"></i>

                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>

                {{ $this->get()->links() }}
            </div>
        </div>
    </div>
    @include('component.include.modal')
</div>
