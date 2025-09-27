@extends('layouts.app')



@section('content')

    <livewire:stock-transfer.show.show-stock-transfer :title="$title" :subtitle="$subtitle" :stocktransfer="$stocktransfer"/>

@endsection
