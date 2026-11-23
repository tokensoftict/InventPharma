@extends('layouts.app')

@section('pageHeaderTitle1', $title)
@section('pageHeaderDescription', $subtitle)

@section('css')
    <style>
        /* Hide number spinners for rapid typing */
        .po-v2 input[type=number]::-webkit-outer-spin-button,
        .po-v2 input[type=number]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        .po-v2 input[type=number] {
            -moz-appearance: textfield;
        }

        /* Header card strip */
        .po-v2-header {
            background: #f8f9fb;
            border: 1px solid #e3e7ed;
            border-radius: 8px;
            padding: 14px 16px;
        }

        /* Quick-add entry bar */
        .po-v2-entry {
            background: #ffffff;
            border: 1.5px solid #5156be33;
            border-radius: 8px;
            padding: 14px 16px;
            box-shadow: 0 2px 8px rgba(81, 86, 190, 0.06);
        }

        /* Floating autocomplete dropdowns */
        .po-v2-dropdown {
            position: absolute;
            top: calc(100% + 4px);
            left: 0;
            right: 0;
            background: #ffffff;
            border: 1px solid #d5dbe4;
            border-radius: 8px;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.14);
            max-height: 340px;
            overflow-y: auto;
            z-index: 1060;
        }

        .po-v2-dropdown-item {
            padding: 9px 12px;
            border-bottom: 1px solid #f1f3f7;
            cursor: pointer;
            transition: background-color 0.08s ease;
        }
        .po-v2-dropdown-item:last-child {
            border-bottom: none;
        }
        .po-v2-dropdown-item:hover,
        .po-v2-dropdown-item.is-active {
            background-color: #eef0ff;
        }

        /* Compact spreadsheet table */
        .po-v2-table th {
            white-space: nowrap;
            font-weight: 600;
            font-size: 11.5px !important;
            background: #f8f9fb;
            vertical-align: middle;
        }
        .po-v2-table td {
            vertical-align: middle;
            padding: 5px 6px !important;
        }
        .po-v2-cell-input {
            padding: 4px 7px;
            font-size: 12.5px;
            height: 31px;
            border-color: #dce1e9;
        }
        .po-v2-cell-input:focus {
            border-color: #5156be;
            box-shadow: 0 0 0 2px rgba(81, 86, 190, 0.15);
        }
        .po-v2-cell-invalid {
            border-color: #fd625e !important;
            background-color: #fff5f5 !important;
        }

        /* Highlight newly added or focused row */
        @keyframes poV2Flash {
            0% { background-color: #d7f5e8; }
            100% { background-color: transparent; }
        }
        .po-v2-row-flash {
            animation: poV2Flash 1.2s ease-out;
        }

        /* Keyboard key badge */
        .po-v2-kbd {
            display: inline-block;
            padding: 1px 5px;
            font-size: 10px;
            font-weight: 600;
            line-height: 1.4;
            color: #495057;
            background-color: #f1f3f7;
            border: 1px solid #ced4da;
            border-radius: 3px;
            vertical-align: middle;
        }

        /* Sticky footer action bar */
        .po-v2-sticky-bar {
            position: sticky;
            bottom: 0;
            z-index: 1020;
            background: #ffffff;
            border-top: 1px solid #e3e7ed;
            padding: 12px 16px;
            margin: 16px -20px -20px -20px;
            box-shadow: 0 -4px 16px rgba(15, 23, 42, 0.06);
        }

        .po-v2-stat-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            background: #f4f6fa;
            border: 1px solid #e3e7ed;
            border-radius: 6px;
            font-size: 12px;
        }
    </style>
@endsection

@section('content')
    <div class="po-v2">
        <livewire:purchase-order.purchase-order-v2-component
            :purchase="$purchase"
            :suppliers="$suppliers"
            :departments="$departments"
            :page-status="$status_id"
        />
    </div>
@endsection
