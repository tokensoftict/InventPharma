<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order #PO-{{ $purchase->id }}</title>
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
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 8px 12px;
            margin-bottom: 10px;
            background: #f4f6f9;
            border-bottom: 1px solid #d8dee6;
            font-size: 12px;
        }
        .no-print-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .no-print-bar label {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            cursor: pointer;
            user-select: none;
        }
        .no-print-bar button {
            padding: 5px 12px;
            border: 1px solid #2b4c7e;
            background: #2b4c7e;
            color: #fff;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
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
            font-size: 7.8pt;
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
            padding: 2px 4px;
            vertical-align: top;
        }
        .items-table th {
            background: #f0f0f0;
            font-weight: 700;
            font-size: 7.2pt;
            text-transform: uppercase;
            letter-spacing: 0.15px;
        }
        .items-table tfoot td,
        .items-table tfoot th {
            border-top: 1.5px solid #111;
            background: #f7f7f7;
            font-weight: 700;
            padding: 3.5px 4px;
            font-size: 8.5pt;
        }
        .over-1m {
            font-weight: 700;
            text-decoration: underline;
        }

        .text-left { text-align: left; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .nowrap { white-space: nowrap; }
        .muted { color: #444; font-size: 7.2pt; }

        /* Toggle supplier copy (hide internal 1M & selling prices) */
        body.supplier-copy .internal-col {
            display: none !important;
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

    $statusLabel = $purchase->status->name ?? ($purchase->status_id === status('Complete') ? 'Completed' : 'Draft');
    $deptLabel = \App\Classes\Settings::$department[$purchase->department] ?? $purchase->department;

    $items = $purchase->purchaseitems;
    $totalQty = $items->sum('qty');
    $totalValue = (new \App\Repositories\PurchaseOrderRepository())->totalPo($purchase);
@endphp

<div class="no-print-bar">
    <span><strong>Compact Print View</strong> &mdash; Purchase Order #{{ $purchase->id }} ({{ $items->count() }} item{{ $items->count() === 1 ? '' : 's' }})</span>
    <div class="no-print-controls">
        <label title="Hide internal 1M metrics and Bulk/Wholesale/Retail selling prices">
            <input type="checkbox" id="toggleSupplierCopy" onchange="toggleSupplierMode(this.checked)">
            Supplier Copy (hide selling prices &amp; 1M)
        </label>
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
            <div class="doc-title">Purchase Order</div>
            <div class="doc-ref">
                #{{ $purchase->id }}
                <span class="status-pill">{{ $statusLabel }}</span>
            </div>
        </div>
    </div>

    {{-- Compact 2-Row Metadata Grid --}}
    <table class="meta-table">
        <tr>
            <td style="width: 44%;">
                <span class="meta-label">Supplier:</span>
                {{ $purchase->supplier->name ?? '—' }}
                @if(!empty($purchase->supplier->phonenumber))
                    <span class="muted">({{ $purchase->supplier->phonenumber }})</span>
                @endif
            </td>
            <td style="width: 28%;">
                <span class="meta-label">Department:</span> {{ $deptLabel }}
            </td>
            <td style="width: 28%;">
                <span class="meta-label">Date Created:</span> {{ $purchase->date_created ? str_date2($purchase->date_created) : '—' }}
            </td>
        </tr>
        <tr>
            <td>
                <span class="meta-label">Created By:</span> {{ $purchase->user->name ?? '—' }}
                @if($purchase->complete_by)
                    &nbsp;|&nbsp; <span class="meta-label">Completed By:</span> {{ $purchase->complete_by->name }}
                @endif
            </td>
            <td>
                <span class="meta-label">Items / Units:</span> {{ $items->count() }} / {{ $totalQty }}
            </td>
            <td>
                <span class="meta-label">Completed:</span>
                {{ $purchase->date_completed ? str_date2($purchase->date_completed) : '—' }}
            </td>
        </tr>
    </table>

    {{-- High-Density Items Table --}}
    <table class="items-table">
        <thead>
        <tr>
            <th class="text-center" style="width: 22px;">#</th>
            <th class="text-left">Product Name</th>
            <th class="text-center internal-col" style="width: 58px;" title="1M Target / Need">1M / Need</th>
            <th class="text-center" style="width: 105px;">Batch / Expiry</th>
            <th class="text-right internal-col" style="width: 108px;" title="Bulk / Wholesale / Retail">B / W / R</th>
            <th class="text-right" style="width: 36px;">Qty</th>
            <th class="text-right" style="width: 68px;">Cost</th>
            <th class="text-right" style="width: 78px;">Total</th>
        </tr>
        </thead>
        <tbody>
        @foreach($items as $item)
            @php
                $expStr = $item->expiry_date
                    ? ($item->expiry_date instanceof \Carbon\Carbon ? $item->expiry_date->format('Y-m-d') : substr((string) $item->expiry_date, 0, 10))
                    : '';
                $batchExp = implode(' / ', array_filter([$item->batch_no ?: null, $expStr ?: null])) ?: '—';
                $isOver1m = ((float) $item->qty_to_buy_1m > 0) && ((float) $item->qty > (float) $item->qty_1m_result);
                $bPrice = $item->bulk_price ?? ($item->stock->bulk_price ?? 0);
                $wPrice = $item->whole_price ?? ($item->stock->whole_price ?? 0);
                $rPrice = $item->retail_price ?? ($item->stock->retail_price ?? 0);
            @endphp
            <tr>
                <td class="text-center">{{ $loop->iteration }}</td>
                <td class="text-left">{{ $item->name }}</td>
                <td class="text-center nowrap internal-col">
                    {{ rtrim(rtrim(number_format((float) $item->qty_to_buy_1m, 2), '0'), '.') ?: '0' }}/{{ rtrim(rtrim(number_format((float) $item->qty_1m_result, 2), '0'), '.') ?: '0' }}
                </td>
                <td class="text-center nowrap">{{ $batchExp }}</td>
                <td class="text-right nowrap internal-col muted">
                    {{ number_format((float) $bPrice, 0) }}/{{ number_format((float) $wPrice, 0) }}/{{ number_format((float) $rPrice, 0) }}
                </td>
                <td class="text-right nowrap {{ $isOver1m ? 'over-1m' : '' }}">{{ $item->qty }}</td>
                <td class="text-right nowrap">{{ number_format((float) $item->cost_price, 2) }}</td>
                <td class="text-right nowrap">{{ number_format((float) $item->qty * (float) $item->cost_price, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
        <tr>
            <th id="totalLabelCell" colspan="5" class="text-right">Total ({{ $items->count() }} item{{ $items->count() === 1 ? '' : 's' }})</th>
            <th class="text-right nowrap">{{ $totalQty }}</th>
            <th></th>
            <th class="text-right nowrap">{{ number_format($totalValue, 2) }}</th>
        </tr>
        </tfoot>
    </table>

    {{-- Compact 1-Row Signatures --}}
    <table class="sig-table">
        <tr>
            <td><div class="sig-line">Prepared By (Name &amp; Sign)</div></td>
            <td><div class="sig-line">Store / Verified By</div></td>
            <td><div class="sig-line">Approved By &amp; Date</div></td>
        </tr>
    </table>

    <div class="print-footer">
        <span>Printed: {{ now()->format('d M Y, h:i A') }} by {{ auth()->user()->name ?? 'System' }}</span>
        <span>Ref: PO #{{ $purchase->id }}</span>
    </div>
</div>

<script>
    function toggleSupplierMode(enabled) {
        document.body.classList.toggle('supplier-copy', enabled);
        var cell = document.getElementById('totalLabelCell');
        if (cell) {
            cell.setAttribute('colspan', enabled ? '3' : '5');
        }
    }

    window.addEventListener('load', function () {
        setTimeout(function () {
            window.print();
        }, 150);
    });
</script>
</body>
</html>
