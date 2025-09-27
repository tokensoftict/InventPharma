@extends('layouts.app')

@section('pageHeaderTitle1', $title)
@section('pageHeaderDescription')
    {!! $subtitle.' <span style="font-weight:bolder" class="text-danger pull-right d-block mt-2 font-size-16">Last Run on '.(new \Carbon\Carbon(app(\App\Classes\Settings::class)->get('nearos_last_run')))->format('Y/m/d g:i:s A').'</span>' !!}
@endsection
@section('css')

@endsection

@section('js')


@endsection

@section('pageHeaderAction')
    @if(app(\App\Classes\Settings::class)->get('m_run_nears') !== 'running' && app(\App\Classes\Settings::class)->get('m_run_nears') !== 'run')
    <div class="row">
        <div class="col-12">
            <a href="{{ route('run_nearos') }}"  class="btn btn-primary float-end">Run Near Os</a>
            <br/> <br/>  <br/>
        </div>
    </div>
    @else
        <div class="row">
            <div class="col-12">
                {!! alert_info('Near Os has been schedule to run or currently running') !!}
            </div>
        </div>
    @endif
@endsection

@section('content')
    <div class="table-responsive">
        <livewire:product-module.near-o-s.near-os-datatable/>
    </div>
@endsection
