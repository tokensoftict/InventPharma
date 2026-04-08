@extends('layouts.app')

@section('content')
    <livewire:settings.category.manage-category-stocks :category="$category" />
@endsection
