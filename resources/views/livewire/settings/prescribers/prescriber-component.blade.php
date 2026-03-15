@section('pageHeaderTitle','Prescribers Manager')
@section('pageHeaderDescription','Manage All Prescribers')

@section('pageHeaderAction')
    @if(userCanView('prescribers.create'))
        <div class="row">
            <div class="col-sm">
                <div class="mb-4">
                    <button  wire:click="new" wire:target="new" wire:loading.attr="disabled" type="button" class="btn btn-primary waves-effect waves-light">
                        <i wire:loading.remove wire:target="new" class="bx bx-plus me-1"></i>
                        <span wire:loading wire:target="new" class="spinner-border spinner-border-sm me-2" role="status"></span>
                        New Prescriber
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
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Company</th>
                        <th>Address</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($this->get() as $prescriber)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $prescriber->name }}</td>
                            <td>{{ $prescriber->phone }}</td>
                            <td>
                                @if(userCanView('prescribers.toggle'))
                                    <div class="form-check form-switch mb-3" dir="ltr">
                                        <input wire:change="toggle({{ $prescriber->id }})" id="user{{ $prescriber->id }}" type="checkbox" class="form-check-input" id="customSwitch1" {{ $prescriber->status ? 'checked' : '' }}>
                                        <label class="form-check-label" for="customSwitch1">{{ $prescriber->status ? 'Active' : 'Inactive' }}</label>
                                    </div>
                                @else
                                    {{ $prescriber->status ? 'Active' : 'Inactive' }}
                                @endif
                            </td>
                            <td>{{ $prescriber->company }}</td>
                            <td>{{ $prescriber->address }}</td>
                            <td>
                                @if(userCanView('prescribers.update'))
                                    <a class="btn btn-outline-primary btn-sm edit" wire:click="edit({{ $prescriber->id }})" href="javascript:void(0);" >

                                        <span wire:loading wire:target="edit({{ $prescriber->id }})" class="spinner-border spinner-border-sm me-2" role="status"></span>

                                        <i wire:loading.remove wire:target="edit({{ $prescriber->id }})" class="fas fa-pencil-alt"></i>

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
