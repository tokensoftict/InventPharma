<div wire:poll.15s>
    <div class="container-fluid py-5">
        <div class="text-center mb-5">
            <h1 class="screen-title text-warning"><img src="{{ asset('images/logo.jpg') }}" width="90"/> Customer Order Status</h1>
            <p class="sub-text">Customers in progress – updated live</p>
        </div>
        <span id="server-time" data-server-time="{{ $serverTime }}"></span>
        <div class="card shadow-lg rounded-4 overflow-hidden">
            <div class="card-body bg-dark bg-opacity-75 p-0 table-wrapper" id="scrollContainer" style="max-height: 70vh; overflow-y: auto;">
                <table class="table bg-white text-dark table-hover table-bordered mb-0 text-center">
                    <thead class="table-warning text-dark fs-5">
                    <tr>
                        <th>#</th>
                        <th>Customer</th>
                        <th>Invoice</th>
                        <th>Dispatch Area</th>
                        <th>Items</th>
                        <th>Status</th>
                        <th>Time</th>
                        <th>Waiting Time</th>
                    </tr>
                    </thead>
                    <tbody id="queue-list">
                    @foreach ($waitingList as $index => $item)
                        <tr class="animate__animated animate__fadeInDown" data-id="{{ $item->id }}">
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $item->invoice->customer->fullname ?? 'N/A' }}</td>
                            <td>#{{ $item->invoice->invoice_number }}</td>
                            <td>{{ \App\Livewire\InvoiceAndSales\WaitingListScreen::$dispatchArea[\App\Classes\Settings::$department[$item->invoice->department]] }}</td>
                            <td>{{ $item->invoice->invoiceitems->count() }}</td>
                            <td><span class="badge bg-warning text-dark">{{ ucfirst($item->status) }}</span></td>
                            <td>{{ $item->entered_at->format('h:i:s A') }}</td>
                            <td>
                                <span
                                        class="live-duration"
                                        id="duration-{{ $item->id }}"
                                        data-id="{{ $item->id }}"
                                        data-time="{{ $item->entered_at->timestamp }}">0h 0m 0s
                                </span>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
