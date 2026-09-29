@section('pageHeaderTitle','Purchase Limits')
@section('pageHeaderDescription','Manage Purchase Limits')

@section('pageHeaderAction')
    @if(userCanView('purchaselimit.index'))
        <div class="row">
            <div class="col-6">
                <div class="mb-4">
                    <button wire:click="new" wire:target="new" wire:loading.attr="disabled" type="button" class="btn btn-primary waves-effect waves-light">
                        <i wire:loading.remove wire:target="new" class="bx bx-plus me-1"></i>
                        <span wire:loading wire:target="new" class="spinner-border spinner-border-sm me-2" role="status"></span>
                        New Purchase Limit
                    </button>
                </div>
            </div>
        </div>
    @endif
@endsection

<div>
    @if(View::hasSection('pageHeaderTitle'))
        @include('shared.pageheader')
    @endif

    {{-- Search Bar --}}
    <div class="row mb-3">
        <div class="col-md-4">
            <input type="text" class="form-control" wire:model.live.debounce.300ms="search" placeholder="Search limits by name or product...">
        </div>
    </div>

    {{-- Limits Data Table --}}
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-nowrap mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Products</th>
                            <th>Max Quantity</th>
                            <th>Period</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($limits as $index => $limit)
                            <tr>
                                <td>{{ $limits->firstItem() + $index }}</td>
                                <td>{{ $limit->name ?? '—' }}</td>
                                <td>
                                    @foreach($limit->stocks as $stock)
                                        <span class="badge bg-info text-dark me-1 mb-1">{{ $stock->name }}</span>
                                    @endforeach
                                </td>
                                <td>{{ number_format($limit->max_quantity) }}</td>
                                <td>{{ $limit->period_value }} {{ $limit->period_unit }}</td>
                                <td>{{ $limit->start_date ? $limit->start_date->format('d M, Y') : '—' }}</td>
                                <td>{{ $limit->end_date ? $limit->end_date->format('d M, Y') : '—' }}</td>
                                <td>
                                    @if($limit->isCurrentlyActive())
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-danger">Inactive</span>
                                    @endif
                                </td>
                                <td>{{ $limit->creator->name ?? '—' }}</td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button wire:click="edit({{ $limit->id }})" class="btn btn-outline-primary btn-sm" title="Edit">
                                            <i class="bx bx-edit"></i>
                                        </button>
                                        <button wire:click="toggle({{ $limit->id }})" class="btn btn-outline-{{ $limit->is_active ? 'warning' : 'success' }} btn-sm" title="{{ $limit->is_active ? 'Deactivate' : 'Activate' }}">
                                            <i class="bx bx-{{ $limit->is_active ? 'pause' : 'play' }}"></i>
                                        </button>
                                        <button wire:click="destroy({{ $limit->id }})" wire:confirm="Are you sure you want to delete this purchase limit?" class="btn btn-outline-danger btn-sm" title="Delete">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted">No purchase limits found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $limits->links() }}
            </div>
        </div>
    </div>

    {{-- Create / Edit Modal --}}
    <div wire:ignore.self class="modal fade" id="simpleComponentModal" tabindex="-1" role="dialog" aria-hidden="true">
        <form method="post" wire:submit="{{ $modelId ? 'update('.$modelId.')' : 'save' }}">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $modalTitle }} {{ $modalName }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            {{-- Rule Name --}}
                            <div class="col-12 mb-3">
                                <label class="form-label">Rule Name <small class="text-muted">(optional)</small></label>
                                <input class="form-control" type="text" wire:model="name" placeholder="e.g. Controlled Substance Limit">
                            </div>

                            {{-- Max Quantity & Period --}}
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Maximum Quantity <span class="text-danger">*</span></label>
                                <input class="form-control" type="number" wire:model="max_quantity" placeholder="e.g. 20" min="1">
                                @error('max_quantity') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Period Value <span class="text-danger">*</span></label>
                                <input class="form-control" type="number" wire:model="period_value" placeholder="e.g. 14" min="1">
                                @error('period_value') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Period Unit <span class="text-danger">*</span></label>
                                <select class="form-select" wire:model="period_unit">
                                    <option value="days">Days</option>
                                    <option value="weeks">Weeks</option>
                                    <option value="months">Months</option>
                                </select>
                                @error('period_unit') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            {{-- Summary --}}
                            @if($max_quantity && $period_value)
                                <div class="col-12 mb-3">
                                    <div class="alert alert-info mb-0 py-2">
                                        <strong>Rule:</strong> {{ $max_quantity }} units / {{ $period_value }} {{ $period_unit }}
                                    </div>
                                </div>
                            @endif

                            {{-- Date Range --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Start Date <small class="text-muted">(optional)</small></label>
                                <input class="form-control" type="date" wire:model="start_date">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">End Date <small class="text-muted">(optional)</small></label>
                                <input class="form-control" type="date" wire:model="end_date">
                            </div>

                            {{-- Active Toggle --}}
                            <div class="col-12 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" wire:model="is_active" id="isActiveSwitch">
                                    <label class="form-check-label" for="isActiveSwitch">Active</label>
                                </div>
                            </div>

                            {{-- Product Selection --}}
                            <div class="col-12 mb-3">
                                <label class="form-label">Products <span class="text-danger">*</span></label>
                                @error('selectedProducts') <span class="text-danger d-block mb-1">{{ $message }}</span> @enderror

                                {{-- Selected Products Badges --}}
                                <div class="mb-2">
                                    @foreach($selectedProducts as $product)
                                        <span class="badge bg-primary me-1 mb-1" style="font-size: 13px;">
                                            {{ $product['name'] }}
                                            <button type="button" wire:click="removeProduct({{ $product['id'] }})" class="btn-close btn-close-white ms-1" style="font-size: 8px;" aria-label="Remove"></button>
                                        </span>
                                    @endforeach
                                </div>

                                {{-- Product Search Input --}}
                                <div class="position-relative">
                                    <input class="form-control" type="text" wire:model.live.debounce.300ms="productSearch" placeholder="Search and select products...">

                                    {{-- Search Results Dropdown --}}
                                    @if(count($productSearchResults) > 0)
                                        <div class="list-group position-absolute w-100 shadow" style="z-index: 1050; max-height: 200px; overflow-y: auto;">
                                            @foreach($productSearchResults as $result)
                                                <button type="button" class="list-group-item list-group-item-action" wire:click="addProduct({{ $result['id'] }}, '{{ addslashes($result['name']) }}')">
                                                    {{ $result['name'] }}
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if(strlen($productSearch) >= 2 && count($productSearchResults) === 0)
                                        <div class="list-group position-absolute w-100 shadow" style="z-index: 1050;">
                                            <div class="list-group-item text-muted">No products found.</div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary waves-effect" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary waves-effect waves-light" wire:loading.attr="disabled">
                            <span wire:loading wire:target="save,update" class="spinner-border spinner-border-sm me-2" role="status"></span>
                            {{ $saveButton }}
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

@script
<script>
    $wire.on('openModal', () => {
        let modal = new bootstrap.Modal(document.getElementById('simpleComponentModal'));
        modal.show();
    });

    $wire.on('closeModal', () => {
        let modalEl = document.getElementById('simpleComponentModal');
        let modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) {
            modal.hide();
        }
    });
</script>
@endscript
