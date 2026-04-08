@extends('layouts.app')

@section('content')
    <livewire:settings.classification.manage-classification-stocks :classification="$classification" />
@endsection
