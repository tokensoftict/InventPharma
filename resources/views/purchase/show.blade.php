@extends('layouts.app')


@section('content')

    <livewire:purchase-order.show.show-purchase-order :title="$title" :subtitle="$subtitle" :purchase="$purchase"/>

@endsection
