@extends('layouts.app')

@section('content')
    <livewire:settings.manufacturer.manage-manufacturer-stocks :manufacturer="$manufacturer" />
@endsection
