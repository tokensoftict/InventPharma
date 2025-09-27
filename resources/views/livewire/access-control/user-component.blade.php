@section('pageHeaderTitle','User Management')
@section('pageHeaderDescription','Manage User using the system')

@section('pageHeaderAction')
    @if(userCanView('user.create '))
        <div class="row">
        <div class="col-sm">
            <div class="mb-4">
                <button  wire:click="new" wire:target="new" wire:loading.attr="disabled" type="button" class="btn btn-primary waves-effect waves-light">
                    <i wire:loading.remove wire:target="new" class="bx bx-plus me-1"></i>
                    <span wire:loading wire:target="new" class="spinner-border spinner-border-sm me-2" role="status"></span>
                    New User
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
                <livewire:access-control.user.user-datatable :filter="[]"/>
            </div>
        </div>
    </div>

    @include('component.include.modal')

</div>
