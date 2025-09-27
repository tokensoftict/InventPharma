@section('pageHeaderTitle','Payment Method Manager')
@section('pageHeaderDescription','Manage All Payment Method')

@section('pageHeaderAction')
    @if(userCanView('payment_method.create'))
        <div class="row">
            <div class="col-sm">
                <div class="mb-4">
                    <button  wire:click="new" wire:target="new" wire:loading.attr="disabled" type="button" class="btn btn-primary waves-effect waves-light">
                        <i wire:loading.remove wire:target="new" class="bx bx-plus me-1"></i>
                        <span wire:loading wire:target="new" class="spinner-border spinner-border-sm me-2" role="status"></span>
                        New Payment Methods
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
                    @foreach($this->get() as $payment_method)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $payment_method->name }}</td>
                            <td>
                                @if(userCanView('payment_method.toggle'))
                                    <div class="form-check form-switch mb-3" dir="ltr">
                                        <input wire:change="toggle({{ $payment_method->id }})" id="user{{ $payment_method->id }}" type="checkbox" class="form-check-input" id="customSwitch1" {{ $payment_method->status ? 'checked' : '' }}>
                                        <label class="form-check-label" for="customSwitch1">{{ $payment_method->status ? 'Active' : 'Inactive' }}</label>
                                    </div>
                                @else
                                    {{ $payment_method->status ? 'Active' : 'Inactive' }}
                                @endif
                            </td>
                            <td>
                                @if(userCanView('payment_method.update'))
                                    <a class="btn btn-outline-primary btn-sm edit" wire:click="edit({{ $payment_method->id }})" href="javascript:void(0);" >

                                        <span wire:loading wire:target="edit({{ $payment_method->id }})" class="spinner-border spinner-border-sm me-2" role="status"></span>

                                        <i wire:loading.remove wire:target="edit({{ $payment_method->id }})" class="fas fa-pencil-alt"></i>

                                    </a>
                                @endif
                                @if(userCanView('payment_method.destroy'))
                                    <a class="btn btn-outline-primary btn-sm delete confirm-text"  wire:click="destroy({{ $payment_method->id }})" href="javascript:void(0);">

                                        <span wire:loading wire:target="destroy({{ $payment_method->id }})" class="spinner-border spinner-border-sm me-2" role="status"></span>

                                        <i wire:loading.remove wire:target="destroy({{ $payment_method->id }})" class="fas fa-trash"></i>

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
