@extends('layouts.app')

@section('content')
    <livewire:settings.stock-group.manage-stock-group-stocks :stockgroup="$stockgroup" />
@endsection
