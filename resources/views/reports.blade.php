@extends('layouts.app')

@section('pageHeaderTitle1','Application Reports')
@section('pageHeaderDescription','List of All Application Reports')

@section('pageHeaderAction')

@endsection

@section('content')

    <div class="row">
        @foreach($modules as $module)
            <div class="col-4">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">{{ $module->label }}</h2>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled fw-medium ">
                            @foreach($module->tasks as $task)
                                @if(userCanView($task->route))
                                    <li class="border-bottom">
                                        <a href="{{ route($task->route) }}" target="_blank" class="text-body pb-3 pt-2 d-block">{{ $task->description }}
                                            <i class="fa fa-arrow-right float-end"></i>
                                        </a>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

@endsection

