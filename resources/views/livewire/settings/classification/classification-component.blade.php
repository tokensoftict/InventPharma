@section('pageHeaderTitle','Classifications Manager')
@section('pageHeaderDescription','Manage All Product Classifications')

@section('pageHeaderAction')
    @if(userCanView('classification.create'))
    <div class="row">
        <div class="col-sm">
            <div class="mb-4">
                <button  wire:click="new" wire:target="new" wire:loading.attr="disabled" type="button" class="btn btn-primary waves-effect waves-light">
                    <i wire:loading.remove wire:target="new" class="bx bx-plus me-1"></i>
                    <span wire:loading wire:target="new" class="spinner-border spinner-border-sm me-2" role="status"></span>
                    New Classification
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
                    @foreach($this->get() as $classification)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $classification->name }}</td>
                            <td>
                                @if(userCanView('classification.toggle()'))
                                <div class="form-check form-switch mb-3" dir="ltr">
                                    <input wire:change="toggle({{ $classification->id }})" id="user{{ $classification->id }}" type="checkbox" class="form-check-input" id="customSwitch1" {{ $classification->status ? 'checked' : '' }}>
                                    <label class="form-check-label" for="customSwitch1">{{ $classification->status ? 'Active' : 'Inactive' }}</label>
                                </div>
                                @else
                                    {{ $classification->status ? 'Active' : 'Inactive' }}
                                    @endif
                            </td>
                            <td>
                                @if(userCanView('classification.update'))
                                <a class="btn btn-outline-primary btn-sm edit" wire:click="edit({{ $classification->id }})" href="javascript:void(0);" >

                                    <span wire:loading wire:target="edit({{ $classification->id }})" class="spinner-border spinner-border-sm me-2" role="status"></span>

                                    <i wire:loading.remove wire:target="edit({{ $classification->id }})" class="fas fa-pencil-alt"></i>

                                </a>
                                @endif

                                    @if(userCanView('classification.destroy'))
                                <a class="btn btn-outline-primary btn-sm delete confirm-text"  wire:click="destroy({{ $classification->id }})" href="javascript:void(0);">

                                    <span wire:loading wire:target="destroy({{ $classification->id }})" class="spinner-border spinner-border-sm me-2" role="status"></span>

                                    <i wire:loading.remove wire:target="destroy({{ $classification->id }})" class="fas fa-trash"></i>

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
