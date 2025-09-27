<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>{{ config('app.name', 'Peace Factory') }}: Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="{{ asset('css/bootstrap.min.css') }}" id="bootstrap-style" rel="stylesheet" type="text/css" />
    @yield('css')
    <link href="{{ asset('css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('css/app.min.css') }}" id="app-style" rel="stylesheet" type="text/css" />
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
<body>
<script>
    const BASE_URL = '{{ asset('') }}';
</script>
<style>
    [x-cloak] { display: none !important; }
</style>

<div id="layout-wrapper">
    <div class="main-content" style="margin-left:0px">

        <div class="page-content" style="padding:10px">
            <div class="container-fluid">
                <div align="center"> <img src="{{ asset('images/logo.jpg') }}" alt="" class="col-3"></div><br/>
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title text-center">SCAN INVOICE QR CODE TO CHECKOUT</h4>
                        <p class="card-title-desc text-center">Please Scan the Qr Code on the Invoice to Checkout Invoice</p>
                    </div>
                    <div class="card-body p4">

                    <livewire:invoice-and-sales.scan.invoice-checkout-scan-bar-code/>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script defer src="{{ asset('js/focus.min.js') }}"></script>
<script defer src="{{ asset('js/alpine.min.js') }}"></script>

<script src="{{ asset('libs/jquery/jquery.min.js') }}"></script>

<script src="{{ asset('js/sweetalert2.js') }}"></script>


<script src="{{ asset('js/html5-qrcode.min.js') }}"></script>
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

</script>


</body>

</html>
