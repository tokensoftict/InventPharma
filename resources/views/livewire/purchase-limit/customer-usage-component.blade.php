@section('pageHeaderTitle', 'Purchase Usage — ' . $customer->fullname)
@section('pageHeaderDescription', 'Current purchase limit usage for this customer')

<div>
    @if(View::hasSection('pageHeaderTitle'))
        @include('shared.pageheader')
    @endif

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="bx bx-user me-1"></i> {{ $customer->fullname }}
                @if($customer->phone_number)
                    <small class="text-muted ms-2">{{ $customer->phone_number }}</small>
                @endif
            </h5>
        </div>
        <div class="card-body">
            @if($usage->isEmpty())
                <div class="alert alert-info mb-0">
                    <i class="bx bx-info-circle me-1"></i>
                    No active purchase limits apply to this customer's products.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered table-nowrap mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Product</th>
                                <th>Limit Rule</th>
                                <th>Period</th>
                                <th>Limit</th>
                                <th>Used</th>
                                <th>Remaining</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($usage as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $item['product_name'] }}</td>
                                    <td>{{ $item['limit_name'] }}</td>
                                    <td>{{ $item['period_label'] }}</td>
                                    <td>{{ number_format($item['max_quantity']) }}</td>
                                    <td>{{ number_format($item['used']) }}</td>
                                    <td>
                                        <strong class="{{ $item['remaining'] <= 0 ? 'text-danger' : 'text-success' }}">
                                            {{ number_format($item['remaining']) }}
                                        </strong>
                                    </td>
                                    <td>
                                        @if($item['remaining'] <= 0)
                                            <span class="badge bg-danger">Limit Reached</span>
                                        @elseif($item['used'] > 0)
                                            <span class="badge bg-warning text-dark">Partially Used</span>
                                        @else
                                            <span class="badge bg-success">Available</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="mt-2">
        <a href="{{ url()->previous() }}" class="btn btn-secondary">
            <i class="bx bx-arrow-back me-1"></i> Back
        </a>
    </div>
</div>
