@extends('layouts.app')

@section('pageHeaderTitle1','Group Permission')
@section('pageHeaderDescription','Add / Remove User Group permission')

@section('pageHeaderAction')

@endsection


@section('content')
    <livewire:access-control.permission-component :usergroup="$usergroup"/>
@endsection

