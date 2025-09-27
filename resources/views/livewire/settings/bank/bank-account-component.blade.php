@section('pageHeaderTitle','Bank Account Manager')
@section('pageHeaderDescription','Manage all Bank Account')

@section('pageHeaderAction')

    @if(userCanView('bank.create'))
        <div class="row">
            <div class="col-sm">
                <div class="mb-4">
                    <button  wire:click="new" wire:target="new" wire:loading.attr="disabled" type="button" class="btn btn-primary waves-effect waves-light">
                        <i wire:loading.remove wire:target="new" class="bx bx-plus me-1"></i>
                        <span wire:loading wire:target="new" class="spinner-border spinner-border-sm me-2" role="status"></span>
                        New Bank Account
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
                        <th>Bank</th>
                        <th>Bank Account Name</th>
                        <th>Bank Account Number</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($this->get() as $bank)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $bank->name }}</td>
                            <td>{{ $bank->account_name }}</td>
                            <td>{{ $bank->account_number }}</td>
                            <td>
                                @if(userCanView('bank.toggle'))
                                    <div class="form-check form-switch mb-3" dir="ltr">
                                        <input wire:change="toggle({{ $bank->id }})" id="user{{ $bank->id }}" type="checkbox" class="form-check-input" id="customSwitch1" {{ $bank->status ? 'checked' : '' }}>
                                        <label class="form-check-label" for="customSwitch1">{{ $bank->status ? 'Active' : 'Inactive' }}</label>
                                    </div>
                                @else
                                    {{ $bank->status ? 'Active' : 'Inactive' }}
                                @endif

                            </td>
                            <td>
                                @if(userCanView('bank.update'))
                                    <a class="btn btn-outline-primary btn-sm edit" wire:click="edit({{ $bank->id }})" href="javascript:void(0);" >

                                        <span wire:loading wire:target="edit({{ $bank->id }})" class="spinner-border spinner-border-sm me-2" role="status"></span>

                                        <i wire:loading.remove wire:target="edit({{ $bank->id }})" class="fas fa-pencil-alt"></i>

                                    </a>
                                @endif
                                @if(userCanView('bank.destroy'))
                                    <a class="btn btn-outline-primary btn-sm delete confirm-text"  wire:click="destroy({{ $bank->id }})" href="javascript:void(0);">

                                        <span wire:loading wire:target="destroy({{ $bank->id }})" class="spinner-border spinner-border-sm me-2" role="status"></span>

                                        <i wire:loading.remove wire:target="destroy({{ $bank->id }})" class="fas fa-trash"></i>

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
