<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>{{ config('app.name', 'Peace Factory') }}: Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="{{ asset('images/favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('css/preloader.min.css') }}" type="text/css" />
    <link href="{{ asset('css/bootstrap.min.css') }}" id="bootstrap-style" rel="stylesheet" type="text/css" />
    @yield('css')
    <link href="{{ asset('css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('css/app.min.css') }}" id="app-style" rel="stylesheet" type="text/css" />
    <link href="https://cdn.jsdelivr.net/npm/izitoast@1.4.0/dist/css/iziToast.min.css" rel="stylesheet" type="text/css" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @livewireStyles
    @livewireScripts
    <style>
        .table td{
            font-size: 12px !important;
        }
        .table th{
            font-size: 12px !important;
        }
    </style>
</head>
@php
    $now = \Carbon\Carbon::now();
    $pm6 = \Carbon\Carbon::parse(date('Y-m-d').' 6:30 PM');
@endphp
@if($now->gt($pm6))

@else
@endif

<body>
<script>
    const BASE_URL = '{{ asset('') }}';
</script>
<style>
    [x-cloak] { display: none !important; }
</style>

<div id="layout-wrapper">

    @include('shared.header')
    @include('shared.sidebar')

    <div class="main-content">

        <div class="page-content">
            <div class="container-fluid">
                @if(View::hasSection('pageHeaderTitle1'))
                    @include('shared.pageheader_layout')
                    @if(View::hasSection('contentInvoice'))
                        @yield('contentInvoice')
                    @else
                        <div class="card">
                            @if(View::hasSection('tools'))
                                <div class="row">
                                    <div class="col-lg-7">
                                        <h4 class="card-title">@yield('pageHeaderTitle1') @yield('pageHeaderTitle')</h4>
                                        <p class="card-title-desc">@yield('pageHeaderDescription')</p>
                                    </div>
                                    <div class="col-lg-5">
                                        @yield('tools')
                                    </div>
                                </div>
                             @else
                            <div class="card-header">
                                <h4 class="card-title">@yield('pageHeaderTitle1') @yield('pageHeaderTitle')</h4>
                                <p class="card-title-desc">@yield('pageHeaderDescription')</p>
                            </div>
                            <div class="card-body p4">
                                @yield('pageHeaderAction')
                                @yield('content')
                            </div>
                             @endif
                        </div>
                    @endif
                @else
                    <div class="card">
                        @if(View::hasSection('tools'))
                            <div class="row">
                                <div class="col-lg-7">
                                    <h4 class="card-title">@yield('pageHeaderTitle1') @yield('pageHeaderTitle')</h4>
                                    <p class="card-title-desc">@yield('pageHeaderDescription')</p>
                                </div>
                                <div class="col-lg-5">
                                    @yield('tools')
                                </div>
                            </div>
                        @else
                        <div class="card-header">
                            <h4 class="card-title">@yield('pageHeaderTitle1') @yield('pageHeaderTitle')</h4>
                            <p class="card-title-desc">@yield('pageHeaderDescription')</p>
                        </div>
                        @endif
                        <div class="card-body p4">
                            @yield('content')
                        </div>
                    </div>
                @endif

            </div>
        </div>

        @include('shared.footer')

        <livewire:modals/>
    </div>
</div>

<div class="right-bar">
    <div data-simplebar class="h-100">
        <div class="rightbar-title d-flex align-items-center p-3">

            <h5 class="m-0 me-2">Theme Customizer</h5>

            <a href="javascript:void(0);" class="right-bar-toggle ms-auto">
                <i class="mdi mdi-close noti-icon"></i>
            </a>
        </div>

        <!-- Settings -->
        <hr class="m-0" />

        <div class="p-4">
            <h6 class="mb-3">Layout</h6>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="layout"
                       id="layout-vertical" value="vertical">
                <label class="form-check-label" for="layout-vertical">Vertical</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="layout"
                       id="layout-horizontal" value="horizontal">
                <label class="form-check-label" for="layout-horizontal">Horizontal</label>
            </div>

            <h6 class="mt-4 mb-3 pt-2">Layout Mode</h6>

            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="layout-mode"
                       id="layout-mode-light" value="light">
                <label class="form-check-label" for="layout-mode-light">Light</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="layout-mode"
                       id="layout-mode-dark" value="dark">
                <label class="form-check-label" for="layout-mode-dark">Dark</label>
            </div>

            <h6 class="mt-4 mb-3 pt-2">Layout Width</h6>

            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="layout-width"
                       id="layout-width-fuild" value="fuild" onchange="document.body.setAttribute('data-layout-size', 'fluid')">
                <label class="form-check-label" for="layout-width-fuild">Fluid</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="layout-width"
                       id="layout-width-boxed" value="boxed" onchange="document.body.setAttribute('data-layout-size', 'boxed')">
                <label class="form-check-label" for="layout-width-boxed">Boxed</label>
            </div>

            <h6 class="mt-4 mb-3 pt-2">Layout Position</h6>

            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="layout-position"
                       id="layout-position-fixed" value="fixed" onchange="document.body.setAttribute('data-layout-scrollable', 'false')">
                <label class="form-check-label" for="layout-position-fixed">Fixed</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="layout-position"
                       id="layout-position-scrollable" value="scrollable" onchange="document.body.setAttribute('data-layout-scrollable', 'true')">
                <label class="form-check-label" for="layout-position-scrollable">Scrollable</label>
            </div>

            <h6 class="mt-4 mb-3 pt-2">Topbar Color</h6>

            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="topbar-color"
                       id="topbar-color-light" value="light" onchange="document.body.setAttribute('data-topbar', 'light')">
                <label class="form-check-label" for="topbar-color-light">Light</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="topbar-color"
                       id="topbar-color-dark" value="dark" onchange="document.body.setAttribute('data-topbar', 'dark')">
                <label class="form-check-label" for="topbar-color-dark">Dark</label>
            </div>

            <h6 class="mt-4 mb-3 pt-2 sidebar-setting">Sidebar Size</h6>

            <div class="form-check sidebar-setting">
                <input class="form-check-input" type="radio" name="sidebar-size"
                       id="sidebar-size-default" value="default" onchange="document.body.setAttribute('data-sidebar-size', 'lg')">
                <label class="form-check-label" for="sidebar-size-default">Default</label>
            </div>
            <div class="form-check sidebar-setting">
                <input class="form-check-input" type="radio" name="sidebar-size"
                       id="sidebar-size-compact" value="compact" onchange="document.body.setAttribute('data-sidebar-size', 'md')">
                <label class="form-check-label" for="sidebar-size-compact">Compact</label>
            </div>
            <div class="form-check sidebar-setting">
                <input class="form-check-input" type="radio" name="sidebar-size"
                       id="sidebar-size-small" value="small" onchange="document.body.setAttribute('data-sidebar-size', 'sm')">
                <label class="form-check-label" for="sidebar-size-small">Small (Icon View)</label>
            </div>

            <h6 class="mt-4 mb-3 pt-2 sidebar-setting">Sidebar Color</h6>

            <div class="form-check sidebar-setting">
                <input class="form-check-input" type="radio" name="sidebar-color"
                       id="sidebar-color-light" value="light" onchange="document.body.setAttribute('data-sidebar', 'light')">
                <label class="form-check-label" for="sidebar-color-light">Light</label>
            </div>
            <div class="form-check sidebar-setting">
                <input class="form-check-input" type="radio" name="sidebar-color"
                       id="sidebar-color-dark" value="dark" onchange="document.body.setAttribute('data-sidebar', 'dark')">
                <label class="form-check-label" for="sidebar-color-dark">Dark</label>
            </div>
            <div class="form-check sidebar-setting">
                <input class="form-check-input" type="radio" name="sidebar-color"
                       id="sidebar-color-brand" value="brand" onchange="document.body.setAttribute('data-sidebar', 'brand')">
                <label class="form-check-label" for="sidebar-color-brand">Brand</label>
            </div>

            <h6 class="mt-4 mb-3 pt-2">Direction</h6>

            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="layout-direction"
                       id="layout-direction-ltr" value="ltr">
                <label class="form-check-label" for="layout-direction-ltr">LTR</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="layout-direction"
                       id="layout-direction-rtl" value="rtl">
                <label class="form-check-label" for="layout-direction-rtl">RTL</label>
            </div>

        </div>

    </div> <!-- end slimscroll-menu-->
</div>

<div class="rightbar-overlay"></div>

<!-- JAVASCRIPT -->
<script defer src="{{ asset('js/focus.min.js') }}"></script>



<script src="{{ asset('libs/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('libs/metismenu/metisMenu.min.js') }}"></script>
<script src="{{ asset('libs/simplebar/simplebar.min.js') }}"></script>
<script src="{{ asset('libs/node-waves/waves.min.js') }}"></script>
<script src="{{ asset('libs/feather-icons/feather.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/izitoast@1.4.0/dist/js/iziToast.min.js"></script>


@yield('js')

<script src="{{ asset('libs/pace-js/pace.min.js') }}"></script>

<script src="{{ asset('js/app.js') }}"></script>

<script src="{{ asset('js/sweetalert2.js') }}"></script>
<x-livewire-notification::toast />

<script>

        let modalsElement = document.getElementById('livewire-bootstrap-modal');

        modalsElement.addEventListener('hidden.bs.modal', () => {

            Livewire.emit('resetModal');

        });

        Livewire.on('showBootstrapModal', () => {
            var myModal = new bootstrap.Modal(document.getElementById('livewire-bootstrap-modal'), {
                keyboard: false
            })

            myModal.show()
        });

        Livewire.on('hideModal', () => {
            var myModal = new bootstrap.Modal(document.getElementById('livewire-bootstrap-modal'), {
                keyboard: false
            })

            myModal.hide()
        });

        function notify(type, message, options = {}) {
            iziToast[type]({
                title: options.title ?? '',
                message: message,
                position: options.position ?? 'topRight',
                timeout: options.timeout ?? 3000,
                ...options
            });
        }

        function success(message, options = {}) {
            notify('success', message, options)
        }

        function error(message, options = {}) {
            notify('error', message, options)
        }

        function warning(message, options = {}) {
            notify('warning', message, options)
        }

</script>


</body>

</html>
