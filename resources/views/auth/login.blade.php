<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>

    <meta charset="utf-8" />
    <title>{{ config('app.name', 'Peace Factory') }}: Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ asset('images/favicon.ico') }}">

    <!-- preloader css -->
    <link rel="stylesheet" href="{{ asset('css/preloader.min.css') }}" type="text/css" />

    <!-- Bootstrap Css -->
    <link href="{{ asset('css/bootstrap.min.css') }}" id="bootstrap-style" rel="stylesheet" type="text/css" />
    <!-- Icons Css -->
    <link href="{{ asset('css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="{{ asset('css/app.min.css') }}" id="app-style" rel="stylesheet" type="text/css" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

</head>
<script>
    const BASE_URL = '{{ asset('') }}';
</script>

@if(config('app.loginBG') != false)
<style>
    .auth-bg {
        background-image: url({{ config('app.loginBG') }}) !important;
    }
</style>
@else
    <style>
        .auth-bg {
            background-image: none !important;
            background-color: blue !important;
        }
    </style>
@endif
<body>

<!-- <body data-layout="horizontal"> -->
<div class="auth-page">
    <div class="container-fluid p-0">
        <div class="row g-0">
            <div class="col-xxl-3 col-lg-4 col-md-5">
                <div class="auth-full-page-content d-flex p-sm-5 p-4">
                    <div class="w-100">
                        <div class="d-flex flex-column h-100">
                            <div class="mb-4 mb-md-5 text-center">
                                <a href="{{ asset('') }}" class="d-block auth-logo">
                                    <img src="{{ asset("logo/".$store->logo) }}" alt="" style="width: 60%"><br/>
                                    <span class="logo-txt">{{ config('app.name', 'Tokensoft Inventory') }}</span>
                                </a>
                            </div>
                            <div class="auth-content my-auto">
                                <div class="text-center">
                                    <h5 class="mb-0">Welcome Back !</h5>
                                    <p class="text-muted mt-2">Sign in to continue to {{ config('app.name', 'Tokensoft Inventory') }}.</p>
                                </div>
                                @if(session()->has('message'))
                                    <span class="text-danger text-center d-block">{{ session()->get('message') }}</span>
                                @endif
                                <form class="mt-4 pt-2" method="post" role="form" action="{{ route('login_process') }}">
                                    @csrf
                                    <div class="mb-3">
                                        <label class="form-label">Username</label>
                                        <input type="text" class="form-control" name="username" autocomplete="off" placeholder="Enter your username">
                                        @error('username') <span class="text-danger text-center d-block">{{ $message }}</span>  @enderror
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-grow-1">
                                                <label class="form-label">Password</label>
                                            </div>
                                            <div class="flex-shrink-0">

                                            </div>
                                        </div>

                                        <div class="input-group auth-pass-inputgroup">
                                            <input type="password" class="form-control" name="password" autocomplete="off" class="pass-input" placeholder="Enter your password" aria-describedby="password-addon">
                                            <button class="btn btn-light shadow-none ms-0" type="button" id="password-addon"><i class="mdi mdi-eye-outline"></i></button>
                                        </div>
                                        @error('password') <span class="text-danger text-center d-block">{{ $message }}</span>  @enderror
                                    </div>

                                    <div class="mb-3">
                                        <button class="btn btn-danger w-100 waves-effect waves-light" type="submit">Log In</button>
                                        <div style="margin-top:10px;">
                                        <center><a href="{{ route('waiting-list') }}">Customer Waiting List</a></center>
                                        <center><a href="{{ route('scan') }}">Scan to View Price Retail</a></center>
                                        </div>
                                    </div>
                                </form>


                            </div>
                            <div class="mt-4 mt-md-5 text-center">
                                <p class="mb-0">&copy;Licensed to {{ config('app.name', 'Tokensoft Inventory') }}  Designed By Tokensoft ICT  - 08130610626</p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- end auth full page content -->
            </div>
            <!-- end col -->
            <div class="col-xxl-9 col-lg-8 col-md-7">
                <div class="auth-bg pt-md-5 p-4 d-flex">

                </div>
            </div>
            <!-- end col -->
        </div>
        <!-- end row -->
    </div>
    <!-- end container fluid -->
</div>


<!-- JAVASCRIPT -->
<script src="{{ asset('libs/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('libs/metismenu/metisMenu.min.js') }}"></script>
<script src="{{ asset('libs/simplebar/simplebar.min.js') }}"></script>
<script src="{{ asset('libs/node-waves/waves.min.js') }}"></script>
<script src="{{ asset('libs/feather-icons/feather.min.js') }}"></script>
<!-- pace js -->
<script src="assets/libs/pace-js/pace.min.js"></script>
<!-- password addon init -->
<script src="{{ asset('js/pages/pass-addon.init.js') }}"></script>

</body>
</html>
