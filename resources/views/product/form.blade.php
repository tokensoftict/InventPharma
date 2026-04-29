@extends('layouts.app')

@section('pageHeaderTitle1', $title . ' Product')
@section('pageHeaderDescription', $subtitle . ' Product')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/pikaday.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}">

@endsection

@section('js')
    <script src="{{ asset('plugins/select2/js/select2.min.js') }}"></script>

    <script src="{{ asset('js/moment.min.js') }}"></script>
    <script src="{{ asset('js/pikaday.js') }}"></script>
    <script src="{{ asset('js/barcode.js') }}"></script>
@endsection

@section('content')
    @if(isset($product->id))
        <ul class="nav nav-pills nav-fill" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link active" id="home-tab" data-bs-toggle="tab" data-bs-target="#home" type="button" role="tab"
                    aria-controls="home" aria-selected="true">General</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab"
                    aria-controls="profile" aria-selected="false">Stock Options</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="barcode-tab" data-bs-toggle="tab" data-bs-target="#barcode-page" type="button"
                    role="tab" aria-controls="barcode" aria-selected="false">Barcode Options</a>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane active" id="home" role="tabpanel" aria-labelledby="home-tab">
                <fieldset>
                    <br />
                    <legend>General Information</legend>
                    <br /><br />
                    <hr />
                    <livewire:product-module.product-component :product="$product" />
                </fieldset>
            </div>
            <div class="tab-pane" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                <fieldset>
                    <br />
                    <legend>Stock Options</legend>
                    <br /><br />
                    <hr />
                    <livewire:product-module.product-option-component :product="$product" />
                </fieldset>

            </div>
            <div class="tab-pane" id="barcode-page" role="tabpanel" aria-labelledby="barcode-tab">
                <fieldset>
                    <br />
                    <legend>Barcode Printing Options</legend>
                    <br /><br />
                    <hr />
                    <livewire:product-module.product-barcode-component :product="$product" />
                </fieldset>
            </div>
        </div>

    @else
        <ul class="nav nav-pills nav-fill" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link active" id="home-tab" data-bs-toggle="tab" data-bs-target="#home" type="button" role="tab"
                    aria-controls="home" aria-selected="true">General</a>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane active" id="home" role="tabpanel" aria-labelledby="home-tab">
                <fieldset>
                    <br />
                    <legend>General Information</legend>
                    <br /><br />
                    <livewire:product-module.product-component :product="$product" />
                </fieldset>
            </div>
        </div>
    @endif
@endsection