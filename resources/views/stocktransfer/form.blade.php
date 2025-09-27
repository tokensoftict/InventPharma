@extends('layouts.app')

@section('pageHeaderTitle1', $title)
@section('pageHeaderDescription', $subtitle)

@section('css')
    <link rel="stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('libs/flatpickr/flatpickr.min.css') }}"/>
    <link rel="stylesheet" href="{{ asset('libs/choices.js/public/assets/styles/choices.min.css') }}">
@endsection

@section('js')
    <script src="{{ asset('plugins/select2/js/select2.min.js') }}"></script>
    <script src="{{ asset('libs/flatpickr/flatpickr.min.js') }}"></script>
    <script src="{{ asset('libs/choices.js/public/assets/scripts/choices.min.js') }}"></script>

    <script>


        document.addEventListener("DOMContentLoaded", function () {
            flatpickr(".datepicker-basic", {  });
            var e = document.querySelectorAll("[data-trigger]");
            for (i = 0; i < e.length; ++i) {
                var a = e[i];
                new Choices(a, {  });
            }
        });

    </script>
@endsection

@section('content')

    <div class="row">
        <div class="col-lg-12" @if(!isset($from)) style="height: 60vh"  @endif>
            <form action="" method="post">
                @csrf
                <div class="col-lg-9">
                    <div class="row">
                        <div class="col-lg-5">
                            <div class="mb-3">
                                <label class="form-label font-size-13 text-muted"> From</label>
                                <select data-trigger name="from" id="from" placeholder="Select Department" class="form-control">
                                    <option value="">Select Department</option>
                                    @foreach($departments as $department)
                                        <option {{ $from == $department->quantity_column ? "selected" : "" }} value="{{ $department->quantity_column}}">{{ $department->label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="mb-3">
                                <label class="form-label font-size-13 text-muted">To</label>
                                @if(auth()->user()->department_id == 4)
                                    <span class="form-control">Retail Department</span>
                                    <input type="hidden" value="retail" name="to">

                                @elseif(auth()->user()->department_id == 2)
                                    <span class="form-control">Whole Sales Department</span>
                                    <input type="hidden" value="wholesales" name="to">
                                @elseif(auth()->user()->department_id == 3)
                                    <span class="form-control">Bulk Sales Department</span>
                                    <input type="hidden" value="bulksales" name="to">
                                @else
                                    <select class="form-control" data-trigger name="to" id="to" placeholder="Select Department">
                                        <option value="">Select Department</option>
                                        @foreach($departments as $department)
                                            <option {{ $to == $department->quantity_column ? "selected" : "" }} value="{{ $department->quantity_column }}">{{ $department->label }}</option>
                                        @endforeach

                                    </select>
                                @endif


                            </div>
                        </div>
                        <div class="col-lg-2">
                            <div class="mb-3">
                                <br>
                                <button  type="submit" class="btn btn-primary waves-effect waves-light mt-2">
                                    Go
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            @if(isset($from) && isset($to))
                @if($from == $to)
                    {!! alert_error('You can not transfer to the same department') !!}
                @else
                <div class="row">
                    <livewire:stock-transfer.stock-transfer-component :stocktransfer="$stocktransfer" :from="$from" :to="$to"/>
                </div>
                @endif
            @endif
        </div>
    </div>
@endsection
