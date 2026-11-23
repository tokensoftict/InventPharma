@extends('layouts.app')

@section('content')
    <livewire:purchase-return.show.show-purchase-return :title="$title" :subtitle="$subtitle" :purchaseReturn="$purchaseReturn"/>
@endsection
