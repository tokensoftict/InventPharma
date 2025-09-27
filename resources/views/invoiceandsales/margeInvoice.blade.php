@extends('layouts.app')

@section('pageHeaderTitle1', $title)
@section('pageHeaderDescription', $subtitle)

@section('css')
    <link rel="stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('libs/flatpickr/flatpickr.min.css') }}"/>
@endsection

@section('js')
    <script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('plugins/select2/js/select2.min.js') }}"></script>
@endsection


@section('content')

    <div class="row">
        <div class="col-sm-8 offset-2">
            <div class="card">
                <div class="card-body">

                    <form action="" method="post" target="_blank">
                        {{ csrf_field() }}
                        <div class="row">
                            <div class="mb-3">
                                <label>Parent/Main Invoice</label>
                                <input type="text" class="form-control"  required name="main" id="products" placeholder="Parent/Main Invoice">
                            </div>

                            <div class="mb-3">
                                <label>Child Invoice</label>
                                <textarea style="height: 100px;" required class="form-control" name="child" placeholder="Child Invoice(Separate Invoice ID OR Invoice Number With Comma please)"></textarea>
                            </div>

                            <button type="submit" name="doc" value="invoice" class="btn btn-success mt-2">Print Invoice</button>
                            <button type="submit" name="doc" value="waybill" class="btn btn-primary mt-2">Print Waybill</button>
                        </div>
                        <br/>
                    </form>


                </div>
            </div>
        </div>
    </div>

@endsection