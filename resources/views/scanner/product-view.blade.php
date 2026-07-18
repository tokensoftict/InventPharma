<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="{{ asset('css/product-scanner-view-style.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @livewireStyles

</head>

<body>

<livewire:product-scanner.product-view/>

@livewireScripts
<script src="{{ asset('libs/jquery/jquery.min.js') }}"></script>
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