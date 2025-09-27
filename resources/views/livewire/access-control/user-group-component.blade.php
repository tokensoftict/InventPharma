@section('pageHeaderTitle','User Group')
@section('pageHeaderDescription','Group User Based on Permission')

@section('pageHeaderAction')
    @if(userCanView('user.group.create'))
        <div class="row">
            <div class="col-sm">
                <div class="mb-4">
                    <button  wire:click="new" wire:target="new" wire:loading.attr="disabled" type="button" class="btn btn-primary waves-effect waves-light">
                        <i wire:loading.remove wire:target="new" class="bx bx-plus me-1"></i>
                        <span wire:loading wire:target="new" class="spinner-border spinner-border-sm me-2" role="status"></span>
                        New Group
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
                        <th>Group Name</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($this->get() as $data)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $data->name }}</td>
                            <td>
                                @if(userCanView('user.group.toggle'))
                                    <div class="form-check form-switch mb-3" dir="ltr">
                                        <input wire:change="toggle({{ $data->id }})" id="user{{ $data->id }}" type="checkbox" class="form-check-input" id="customSwitch1" {{ $data->status ? 'checked' : '' }}>
                                        <label class="form-check-label" for="customSwitch1">{{ $data->status ? 'Active' : 'Inactive' }}</label>
                                    </div>
                                @else
                                    <label class="form-check-label" for="customSwitch1">{{ $data->status ? 'Active' : 'Inactive' }}</label>
                                @endif

                            </td>
                            <td class="text-end">

                                @if(userCanView('user.group.update') || userCanView('user.group.permission') || userCanView('user.group.destroy') )

                                    <div class="dropdown">
                                        <button style="font-size: 22px;" wire:loading.remove wire:target="edit({{ $data->id }}),destroy" class="btn btn-link font-size-16 shadow-none py-0 text-muted dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bx bx-dots-horizontal-rounded"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            @if(userCanView('user.group.update'))
                                                <li><a href="javascript:void(0);" wire:click="edit({{ $data->id }})" class="dropdown-item">Edit Group</a></li>
                                            @endif
                                            @if(userCanView('user.group.destroy'))
                                                <li><a href="javascript:void(0);" wire:click="destroy({{ $data->id }})" class="dropdown-item confirm-text">Delete Group</a></li>
                                            @endif
                                            @if(userCanView('user.group.permission'))
                                                <li><a href="{{ route('user.group.permission',$data->id) }}" class="dropdown-item">Set Permissions</a></li>
                                            @endif
                                        </ul>
                                    </div>
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
