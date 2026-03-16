@section('pageHeaderTitle','Member Group Manager')
@section('pageHeaderDescription','Manage All Member Group')

@section('pageHeaderAction')
    @if(userCanView('member-group.create'))
        <div class="row">
            <div class="col-sm">
                <div class="mb-4">
                    <button  wire:click="new" wire:target="new" wire:loading.attr="disabled" type="button" class="btn btn-primary waves-effect waves-light">
                        <i wire:loading.remove wire:target="new" class="bx bx-plus me-1"></i>
                        <span wire:loading wire:target="new" class="spinner-border spinner-border-sm me-2" role="status"></span>
                        New Member Group
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
                        <th>Label</th>
                        <th>Status</th>
                        <th>Color</th>
                        <th>BG Color</th>
                        <th>Minimum Sales Amount</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($this->get() as $member_groups)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $member_groups->name }}</td>
                            <td>{{ $member_groups->label }}</td>
                            <td>
                                @if(userCanView('member-group.toggle'))
                                    <div class="form-check form-switch mb-3" dir="ltr">
                                        <input wire:change="toggle({{ $member_groups->id }})" id="user{{ $member_groups->id }}" type="checkbox" class="form-check-input" id="customSwitch1" {{ $member_groups->status ? 'checked' : '' }}>
                                        <label class="form-check-label" for="customSwitch1">{{ $member_groups->status ? 'Active' : 'Inactive' }}</label>
                                    </div>
                                @else
                                    {{ $member_groups->status ? 'Active' : 'Inactive' }}
                                @endif
                            </td>
                            <td>{{ $member_groups->color }}</td>
                            <td>{{ $member_groups->bg_color }}</td>
                            <td>{{ money($member_groups->min_sales_amount) }}</td>
                            <td>
                                @if(userCanView('member-group.update'))
                                    <a class="btn btn-outline-primary btn-sm edit" wire:click="edit({{ $member_groups->id }})" href="javascript:void(0);" >

                                        <span wire:loading wire:target="edit({{ $member_groups->id }})" class="spinner-border spinner-border-sm me-2" role="status"></span>

                                        <i wire:loading.remove wire:target="edit({{ $member_groups->id }})" class="fas fa-pencil-alt"></i>

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
