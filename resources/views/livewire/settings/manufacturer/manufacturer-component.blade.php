@section('pageHeaderTitle','Manufacturer Manager')
@section('pageHeaderDescription','Manage All Manufacturer Manager')

@section('pageHeaderAction')
    @if(userCanView('manufacturer.create'))
        <div class="row">
            <div class="col-sm">
                <div class="mb-4">
                    <button  wire:click="new" wire:target="new" wire:loading.attr="disabled" type="button" class="btn btn-primary waves-effect waves-light">
                        <i wire:loading.remove wire:target="new" class="bx bx-plus me-1"></i>
                        <span wire:loading wire:target="new" class="spinner-border spinner-border-sm me-2" role="status"></span>
                        New Manufacturer
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
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($this->get() as $manufacturer)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $manufacturer->name }}</td>
                            <td>
                                @if(userCanView('manufacturer.toggle'))
                                    <div class="form-check form-switch mb-3" dir="ltr">
                                        <input wire:change="toggle({{ $manufacturer->id }})" id="user{{ $manufacturer->id }}" type="checkbox" class="form-check-input" id="customSwitch1" {{ $manufacturer->status ? 'checked' : '' }}>
                                        <label class="form-check-label" for="customSwitch1">{{ $manufacturer->status ? 'Active' : 'Inactive' }}</label>
                                    </div>
                                @else
                                    {{ $manufacturer->status ? 'Active' : 'Inactive' }}
                                @endif
                            </td>
                            <td>
                                @if(userCanView('manufacturer.update'))
                                    <a class="btn btn-outline-primary btn-sm edit" wire:click="edit({{ $manufacturer->id }})" href="javascript:void(0);" >

                                        <span wire:loading wire:target="edit({{ $manufacturer->id }})" class="spinner-border spinner-border-sm me-2" role="status"></span>

                                        <i wire:loading.remove wire:target="edit({{ $manufacturer->id }})" class="fas fa-pencil-alt"></i>

                                    </a>
                                @endif
                                @if(userCanView('manufacturer.destroy'))
                                    <a class="btn btn-outline-primary btn-sm delete confirm-text"  wire:click="destroy({{ $manufacturer->id }})" href="javascript:void(0);">

                                        <span wire:loading wire:target="destroy({{ $manufacturer->id }})" class="spinner-border spinner-border-sm me-2" role="status"></span>

                                        <i wire:loading.remove wire:target="destroy({{ $manufacturer->id }})" class="fas fa-trash"></i>

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
