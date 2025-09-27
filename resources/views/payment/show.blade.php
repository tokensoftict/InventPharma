@extends('layouts.app')

@section('pageHeaderTitle1', $title)
@section('pageHeaderDescription', $subtitle)


@section('content')
    <livewire:payment-manager.viewpayment-component :payment="$payment"/>

    <script>
        window.onload = function (){
            window.addEventListener('refreshBrowser', (e) => {
                setTimeout(()=>{
                    window.location.href = '{{ route('payment.create') }}'
                }, 1700);
            });

        }
    </script>
@endsection
