@section('pageHeaderTitle', 'Manage Stocks for ' . $brand->name)
@section('pageHeaderDescription', 'Add or remove stocks for this brand')

@section('pageHeaderAction')
    <a href="{{ route('brand.index') }}" class="btn btn-secondary waves-effect waves-light">
        <i class="bx bx-arrow-back me-1"></i> Back to Brands
    </a>
@endsection

<div>
    @include('shared.pageheader')

    <div class="row">
        <!-- Add Stock Section -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Add Stock to Brand</h4>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Search Stock</label>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control"
                            placeholder="Search by name...">
                    </div>

                    @if(!empty($search))
                        <div class="list-group">
                            @forelse($availableStocks as $stock)
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">{{ $stock->name }}</h6>
                                        <small class="text-muted">
                                            @if($stock->brand)
                                                Brand: {{ $stock->brand->name }}
                                            @else
                                                No Brand
                                            @endif
                                        </small>
                                    </div>
                                    <button wire:click="addStock({{ $stock->id }})" wire:loading.attr="disabled" wire:target="addStock({{ $stock->id }})" class="btn btn-primary btn-sm">
                                        <i wire:loading.remove wire:target="addStock({{ $stock->id }})" class="bx bx-plus"></i>
                                        <span wire:loading wire:target="addStock({{ $stock->id }})" class="spinner-border spinner-border-sm" role="status"></span>
                                    </button>
                                </div>
                            @empty
                                <div class="list-group-item text-center">No stocks found</div>
                            @endforelse
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Current Stocks Section -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Stocks by {{ $brand->name }}</h4>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Search in List</label>
                        <input type="text" wire:model.live.debounce.300ms="currentSearch" class="form-control" placeholder="Search stocks...">
                    </div>

                    @if (session()->has('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Stock Name</th>
                                    <th>Code</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($currentStocks as $stock)
                                    <tr>
                                        <td>{{ ($currentStocks->currentPage() - 1) * $currentStocks->perPage() + $loop->iteration }}
                                        </td>
                                        <td>{{ $stock->name }}</td>
                                        <td>{{ $stock->code }}</td>
                                        <td>
                                            <button wire:click="removeStock({{ $stock->id }})" wire:loading.attr="disabled" wire:target="removeStock({{ $stock->id }})"
                                                class="btn btn-outline-danger btn-sm">
                                                <i wire:loading.remove wire:target="removeStock({{ $stock->id }})" class="fas fa-trash me-1"></i>
                                                <span wire:loading wire:target="removeStock({{ $stock->id }})" class="spinner-border spinner-border-sm me-1" role="status"></span>
                                                Remove
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center">No stocks found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $currentStocks->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
