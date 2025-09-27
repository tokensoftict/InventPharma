@extends('layouts.app')

@section('pageHeaderTitle1', $title.' Invoice')
@section('pageHeaderDescription', $subtitle.' Invoice')

@section('css')
    <link rel="stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('libs/flatpickr/flatpickr.min.css') }}"/>
    <style>
        .form-control-lg {
            height: 46px !important;
            padding: 10px 16px !important;
            font-size: 18px !important;
            line-height: 1.3333333 !important;
            border-radius: 6px !important;
        }

        .np-title {
            margin-left: 20px;
            margin-top: 30px;
            font-size: 18px;
            color: rgb(0, 64, 255);
        }

        .np-input-search:hover {
            background: rgb(225, 225, 225);
            transition: all 0.4s;
        }
        .np-result-container {
            margin-top: -10px;
            text-align: left;
            position: absolute;
            width: 95%;
            max-height: 350px;
            overflow: scroll;
            border-radius: 4px;
            background-color: #fff;
            z-index: 1000000;
            box-shadow: 0px 1px 6px 1px rgb(0 0 0 / 40%);
        }
        .np-result-item {
            width: 100%;
            border: 1px solid #eee;
            padding: 4px 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .np-result-item:hover {
            background: #eee;
            transition: all 0.3s;
        }
        .np-result-description {
            font-size: 11px;
        }
        .np-ib {
            display: inline-block;
        }

        .np-text-container {
            width: 100%;
            vertical-align: top;
            padding-left: 5px;
            color: black;
        }


        .np-result-details-title {
            font-size: 20px;
            padding: 8px 0px;
            font-weight: 500;
        }
        .np-result-details-description {
            font-size: 16px;
        }

        .slashed{
            text-decoration: line-through;
        }

    </style>

@endsection

@section('js')
    <script src="{{ asset('plugins/select2/js/select2.min.js') }}"></script>
    <script src="{{ asset('libs/flatpickr/flatpickr.min.js') }}"></script>
    <script src="{{ asset('js/barcode.js') }}"></script>

    <script>
        $(document).ready(function(){
           $('body').attr('class', 'pace-done sidebar-enable');
           $('body').attr('data-sidebar-size', 'sm');
        });
    </script>
@endsection

@section('contentInvoice')

    <livewire:invoice-and-sales.invoice-form-component :invoice="$invoice" :department="$department"/>

@endsection
