@extends('layouts.app')

@section('content')

    <h3>Hello ! {{ auth()->user()->name }}, Welcome, Back </h3>
    <p>System Date and Time : {{ todaysDate() }} {{ \Carbon\Carbon::now()->toDateTimeLocalString() }}</p>
@endsection
