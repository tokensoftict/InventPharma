@extends('layouts.app')

@section('pageHeaderTitle1', $title)
@section('pageHeaderDescription', $subtitle)

@section('css')
    <link rel="stylesheet" href="{{ asset('css/pikaday.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}">
@endsection

@section('js')
    <script src="{{ asset('plugins/select2/js/select2.min.js') }}"></script>

    <script src="{{ asset('js/moment.min.js') }}"></script>
    <script src="{{ asset('js/pikaday.js') }}"></script>
@endsection

@section('content')
    <div class="col-lg-12">
        @if(session('success'))
            {!! alert_success(session('success')) !!}
        @elseif(session('error'))
            {!! alert_error(session('error')) !!}
        @endif
    </div>
    <div class="row">
        <div class="col-sm-6">
            <div class="login-bg">
                <h3>Export Stock to Excel</h3>
                <p>Click on the button below to Excel</p>
                <br><br>
                <a style="margin-top: 14px;" href="{{ route("product.export_stock") }}?export=true" class="btn btn-primary btn-block btn-lg"><i class="fa fa-database"> Export Stock</i></a>
            </div>
        </div>

        <div class="col-sm-6">
            <div class="login-bg">
                <h3>Import Stock Update From Excel</h3>
                <p>Upload excel file and click on Import and Update</p>
                <form enctype="multipart/form-data" action="{{ route("product.export_stock") }}" method="POST">
                    {{ csrf_field() }}
                    <input type="file" class="form-control" name="excel_file"><br>
                    <button type="submit" name="restore_btn" class="btn btn-danger btn-block btn-lg"><i class="fa fa-database"> Import and Update</i></button>
                </form>
            </div>
        </div>

    </div>
@endsection
