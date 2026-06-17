@extends('layouts.app')

@section('pageHeaderTitle1', $title)
@section('pageHeaderDescription', $subtitle)

@section('css')
    <link rel="stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('libs/flatpickr/flatpickr.min.css') }}"/>
    <link rel="stylesheet" href="{{ asset('libs/choices.js/public/assets/styles/choices.min.css') }}">
    <style>
        /* Chrome, Safari, Edge, Opera */
        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        /* Firefox */
        input[type=number] {
            -moz-appearance: textfield;
        }

    </style>
@endsection

@section('js')
    <script src="{{ asset('plugins/select2/js/select2.min.js') }}"></script>
    <script src="{{ asset('libs/flatpickr/flatpickr.min.js') }}"></script>
    <script src="{{ asset('libs/choices.js/public/assets/scripts/choices.min.js') }}"></script>

    <script>

        document.addEventListener("DOMContentLoaded", function () {
            flatpickr(".datepicker-basic", {  });
            var e = document.querySelectorAll("[data-trigger]");
            for (i = 0; i < e.length; ++i) {
                var a = e[i];
                new Choices(a, {  });
            }

            $('#supplier_id, #department').select2();
        });



    </script>
@endsection

@section('content')
    <div class="row mt-4">
        <form action="" id="pform" method="post" @if(!isset($supplier_id)) style="height: 60vh"  @endif>
            <div class="col-lg-10">
                <div class="row">
                    <div class="col-lg-4">
                        <div class="mb-3">
                            <label>Select Supplier</label>
                            <select class="form-control" @if(isset($supplier_id)) onchange="$('#pform').submit()" @endif id="supplier_id"  name="supplier_id">
                                <option value="">Select Supplier</option>
                                @foreach($suppliers as $supplier)
                                    <option {{ $supplier_id ==  $supplier->id ? "selected" : "" }}  value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <label>Select Department</label>
                        <select class="form-control"  @if(isset($department)) onchange="$('#pform').submit()" @endif name="department" id="department">
                            <option value="">Select Department</option>
                            @foreach($depertments as $depertment)
                                <option {{ $depertment->quantity_column ==  $department ? "selected" : "" }}  value="{{ $depertment->quantity_column }}">{{ $depertment->label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3">
                        <label>Purchase Date</label>
                        <input type="text" name="purchase_date" readonly value="{{ date("Y-m-d") }}" class="form-control">
                    </div>
                    <div class="col-lg-2">
                        <button class="btn  btn-primary"  style="margin-top: 27px;" type="submit">Go</button>
                    </div>
                </div>
            </div>
        </form>
    </div>


    @if(isset($supplier_id) and isset($department) and isset($purchase_date))
        <livewire:purchase-order.purchase-order-component :purchase="$purchase" :supplier_id="$supplier_id" :department="$department" :page-status="$status_id" :purchase_date="$purchase_date"/>
    @endif

@endsection
