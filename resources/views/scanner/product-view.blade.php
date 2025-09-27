<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css" integrity="sha384-AYmEC3Yw5cVb3ZcuHtOA93w35dYTsvhLPVnYs9eStHfGJvOvKxVfELGroGkvsg+p" crossorigin="anonymous" />
    <link rel="stylesheet" href="{{ asset('css/product-scanner-view-style.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @livewireStyles
    @livewireScripts
</head>

<body>

<livewire:product-scanner.product-view/>

<script src="{{ asset('libs/jquery/jquery.min.js') }}"></script>
<script defer src="{{ asset('js/alpine.min.js') }}"></script>
<script src="{{ asset('js/barcode.js') }}"></script>
<script>
    function img(anything) {
        document.querySelector('.slide').src = anything;
    }

    function change(change) {
        const line = document.querySelector('.home');
        line.style.background = change;
    }
</script>
</body>

</html>