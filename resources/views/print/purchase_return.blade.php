<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Return #PRT-{{ $purchaseReturn->id }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 6mm 8mm;
        }

        * {
            box-sizing: border-box;
        }

        html, body {
            margin: 0;
            padding: 0;
            background: #ffffff;
            color: #111111;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            font-size: 8.5pt;
            line-height: 1.25;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Screen-only toolbar */
        .no-print-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 12px;
            margin-bottom: 10px;
            background: #f4f6f9;
            border-bottom: 1px solid #d8dee6;
            font-size: 12px;
        }
        .no-print-bar button {
            padding: 5px 12px;
            border: 1px solid #2b4c7e;
            background: #2b4c7e;
            color: #fff;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            margin-left: 6px;
        }
        .no-print-bar button.secondary {
            background: #fff;
            color: #333;
            border-color: #bbb;
        }

        .sheet {
            max-width: 194mm;
            margin: 0 auto;
            padding: 2mm 0;
        }

        /* Compact 1-row Document Header */
        .doc-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 1.5px solid #111;
            padding-bottom: 4px;
            margin-bottom: 5px;
            gap: 10px;
        }
        .store-name {
            font-size: 11.5pt;
            font-weight: 700;
            margin: 0;
            letter-spacing: 0.2px;
        }
        .store-sub {
            font-size: 7.5pt;
            color: #333;
            margin-top: 1px;
        }
        .doc-title-block {
            text-align: right;
            white-space: nowrap;
        }
        .doc-title {
            font-size: 10pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin: 0;
        }
        .doc-ref {
            font-size: 9pt;
            font-weight: 700;
            margin-top: 1px;
        }
        .status-pill {
            display: inline-block;
            font-size: 7pt;
            font-weight: 700;
            text-transform: uppercase;
            padding: 1px 5px;
            border: 1px solid #111;
            border-radius: 2px;
            margin-left: 4px;
            vertical-align: middle;
        }

        /* Compact Metadata Grid */
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            font-size: 8pt;
        }
        .meta-table td {
            border: 1px solid #888;
            padding: 2.5px 5px;
            vertical-align: top;
        }
        .meta-label {
            font-weight: 700;
            color: #222;
        }

        /* High-Density Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
            font-size: 8pt;
        }
        .items-table thead {
            display: table-header-group;
        }
        .items-table tr {
            page-break-inside: avoid;
        }
        .items-table th,
        .items-table td {
            border: 1px solid #666;
            padding: 2.5px 4px;
            vertical-align: top;
        }
        .items-table th {
            background: #f0f0f0;
            font-weight: 700;
            font-size: 7.5pt;
            text-transform: uppercase;
            letter-spacing: 0.2px;
        }
        .items-table tfoot td,
        .items-table tfoot th {
            border-top: 1.5px solid #111;
            background: #f7f7f7;
            font-weight: 700;
            padding: 3.5px 4px;
            font-size: 8.5pt;
        }

        .text-left { text-align: left; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .nowrap { white-space: nowrap; }
        .muted { color: #444; font-size: 7.5pt; }

        /* 1-line Ledger Summary */
        .ledger-strip {
            border: 1px solid #666;
            padding: 3px 6px;
            margin-bottom: 6px;
            font-size: 7.8pt;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 8px;
        }

        /* Compact 1-row Signatures */
        .sig-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            page-break-inside: avoid;
        }
        .sig-table td {
            width: 33.33%;
            padding: 12px 8px 0 0;
            vertical-align: bottom;
            font-size: 7.5pt;
        }
        .sig-table td:last-child {
            padding-right: 0;
        }
        .sig-line {
            border-top: 1px solid #333;
            padding-top: 2px;
            color: #222;
        }

        .print-footer {
            margin-top: 5px;
            font-size: 7pt;
            color: #555;
            display: flex;
            justify-content: space-between;
        }

        @media print {
            .no-print-bar {
                display: none !important;
            }
            .sheet {
                max-width: 100%;
                padding: 0;
            }
        }
    </style>
</head>
<body>
@php
    $storeName = $store->name ?? config('app.name');
    $storeAddr = trim(implode(', ', array_filter([
        $store->first_address ?? null,
        $store->second_address ?? null,
    ])));
    $storePhone = $store->contact_number ?? null;

    $isCompleted = $purchaseReturn->status_id === status('Complete');
    $statusLabel = $isCompleted ? 'Completed' : ($purchaseReturn->status->name ?? 'Draft');
    $deptLabel = \App\Classes\Settings::$department[$purchaseReturn->department] ?? $purchaseReturn->department;
    $noteText = trim((string) ($purchaseReturn->remark ?: $purchaseReturn->notes ?: ''));

    $totalQty = $purchaseReturn->items->sum('qty');
    $totalValue = $purchaseReturn->items->sum(fn ($i) => (float) $i->qty * (float) $i->cost_price);
    $ledgerEntry = $isCompleted ? $purchaseReturn->ledgerEntries->first() : null;
@endphp

<div class="no-print-bar">
    <span><strong>Compact Print View</strong> &mdash; Purchase Return #PRT-{{ $purchaseReturn->id }} ({{ $purchaseReturn->items->count() }} item{{ $purchaseReturn->items->count() === 1 ? '' : 's' }})</span>
    <div>
        <button type="button" onclick="window.print()">Print</button>
        <button type="button" class="secondary" onclick="window.close()">Close</button>
    </div>
</div>

<div class="sheet">
    {{-- Compact Header --}}
    <div class="doc-header">
        <div>
            <div class="store-name">{{ $storeName }}</div>
            @if($storeAddr || $storePhone)
                <div class="store-sub">
                    {{ $storeAddr }}@if($storeAddr && $storePhone) &nbsp;|&nbsp; @endif{{ $storePhone ? 'Tel: ' . $storePhone : '' }}
                </div>
            @endif
        </div>
        <div class="doc-title-block">
            <div class="doc-title">Purchase Return Note</div>
            <div class="doc-ref">
                #PRT-{{ $purchaseReturn->id }}
                <span class="status-pill">{{ $statusLabel }}</span>
            </div>
        </div>
    </div>

    {{-- Compact 2-Row Metadata Grid --}}
    <table class="meta-table">
        <tr>
            <td style="width: 44%;">
                <span class="meta-label">Supplier:</span>
                {{ $purchaseReturn->supplier->name ?? '—' }}
                @if(!empty($purchaseReturn->supplier->phonenumber))
                    <span class="muted">({{ $purchaseReturn->supplier->phonenumber }})</span>
                @endif
            </td>
            <td style="width: 28%;">
                <span class="meta-label">Department:</span> {{ $deptLabel }}
            </td>
            <td style="width: 28%;">
                <span class="meta-label">Return Date:</span> {{ eng_str_date($purchaseReturn->date_created) }}
            </td>
        </tr>
        <tr>
            <td>
                <span class="meta-label">Created By:</span> {{ $purchaseReturn->user->name ?? '—' }}
                @if($purchaseReturn->complete_by)
                    &nbsp;|&nbsp; <span class="meta-label">Completed By:</span> {{ $purchaseReturn->complete_by->name }}
                @endif
            </td>
            <td>
                <span class="meta-label">Linked PO:</span> {{ $purchaseReturn->purchase_id ? '#' . $purchaseReturn->purchase_id : '—' }}
            </td>
            <td>
                <span class="meta-label">Completed:</span>
                {{ $purchaseReturn->date_completed ? eng_str_date($purchaseReturn->date_completed) : '—' }}
            </td>
        </tr>
        @if($noteText !== '')
            <tr>
                <td colspan="3">
                    <span class="meta-label">Notes:</span> {{ $noteText }}
                </td>
            </tr>
        @endif
    </table>

    {{-- High-Density Items Table --}}
    <table class="items-table">
        <thead>
        <tr>
            <th class="text-center" style="width: 24px;">#</th>
            <th class="text-left">Product Name</th>
            <th class="text-center" style="width: 115px;">Batch / Expiry</th>
            <th class="text-left" style="width: 115px;">Reason</th>
            <th class="text-right" style="width: 42px;">Qty</th>
            <th class="text-right" style="width: 72px;">Cost</th>
            <th class="text-right" style="width: 82px;">Total</th>
        </tr>
        </thead>
        <tbody>
        @foreach($purchaseReturn->items as $item)
            @php
                $expStr = $item->expiry_date
                    ? ($item->expiry_date instanceof \Carbon\Carbon ? $item->expiry_date->format('Y-m-d') : substr((string) $item->expiry_date, 0, 10))
                    : '';
                $batchExp = implode(' / ', array_filter([$item->batch_no ?: null, $expStr ?: null])) ?: '—';
            @endphp
            <tr>
                <td class="text-center">{{ $loop->iteration }}</td>
                <td class="text-left">{{ $item->name }}</td>
                <td class="text-center nowrap">{{ $batchExp }}</td>
                <td class="text-left">{{ $item->reason ?: '—' }}</td>
                <td class="text-right nowrap">{{ $item->qty }}</td>
                <td class="text-right nowrap">{{ number_format((float) $item->cost_price, 2) }}</td>
                <td class="text-right nowrap">{{ number_format((float) $item->qty * (float) $item->cost_price, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
        <tr>
            <th colspan="4" class="text-right">Total ({{ $purchaseReturn->items->count() }} item{{ $purchaseReturn->items->count() === 1 ? '' : 's' }})</th>
            <th class="text-right nowrap">{{ $totalQty }}</th>
            <th></th>
            <th class="text-right nowrap">{{ number_format($totalValue, 2) }}</th>
        </tr>
        </tfoot>
    </table>

    {{-- Single-line Ledger Credit Summary (if completed) --}}
    @if($ledgerEntry)
        <div class="ledger-strip">
            <span><strong>Supplier Credit Applied:</strong> {{ $ledgerEntry->reference ?? ('PRT-' . $purchaseReturn->id) }}</span>
            <span><strong>Type:</strong> {{ $ledgerEntry->type }}</span>
            <span><strong>Amount:</strong> {{ number_format((float) $ledgerEntry->amount, 2) }}</span>
            <span><strong>Date:</strong> {{ eng_str_date($ledgerEntry->payment_date ?? $ledgerEntry->created_at) }}</span>
        </div>
    @endif

    {{-- Compact 1-Row Signatures --}}
    <table class="sig-table">
        <tr>
            <td><div class="sig-line">Prepared By (Name &amp; Sign)</div></td>
            <td><div class="sig-line">Verified By / Store Sign</div></td>
            <td><div class="sig-line">Supplier / Driver Sign &amp; Date</div></td>
        </tr>
    </table>

    <div class="print-footer">
        <span>Printed: {{ now()->format('d M Y, h:i A') }} by {{ auth()->user()->name ?? 'System' }}</span>
        <span>Ref: #PRT-{{ $purchaseReturn->id }}</span>
    </div>
</div>

<script>
    window.addEventListener('load', function () {
        setTimeout(function () {
            window.print();
        }, 150);
    });
</script>
</body>
</html>
