@extends('layouts.app')
@section('pageHeaderTitle1','Quickly Batch Stock')
@section('pageHeaderDescription','Quickly Adjust Incorrect Stock Quantity')

@section('pageHeaderAction')

@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('libs/flatpickr/flatpickr.min.css') }}"/>
    <link rel="stylesheet" href="{{ asset('libs/choices.js/public/assets/styles/choices.min.css') }}">
@endsection

@section('js')
    <script src="{{ asset('plugins/select2/js/select2.min.js') }}"></script>
    <script src="{{ asset('libs/flatpickr/flatpickr.min.js') }}"></script>
    <script>
        window.onload = function (){
            $(document).ready(function(){
                var path = '{{ route('findpurchasestock') }}'+"?select2=yes"
                var obj = this;
                var select =  $('#select2').select2({
                    placeholder: 'Select for product',
                    ajax: {
                        url: path,
                        dataType: 'json',
                        delay: 250,
                        data: function (data) {
                            return {
                                searchTerm: data.term // search term
                            };
                        },
                        processResults: function (response) {
                            return {
                                results:response
                            };
                        },
                    }
                })

            });
        }
    </script>
@endsection


@section('content')

    <div class="row">
        <div class="col-lg-12">
            <form action="" method="post">
                @csrf
                <div class="col-lg-9">
                    <div class="row">
                        <div class="col-lg-5">
                            <div class="mb-3">
                                <label class="form-label font-size-13 text-muted"> Search For Stock</label>
                                <select name="stock_id" id="select2"  class="form-control">
                                    @if(isset($stock))
                                        <option value="{{ $stock->id }}" selected>{{ $stock->name }}</option>
                                    @endif
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="mb-3">
                                <label class="form-label font-size-13 text-muted"> Department</label>
                                <select name="department_id"   class="form-control">
                                    <option value="">Select Department</option>
                                    @foreach($departments as $department)
                                        <option {{ $selectedDepartment === $department->quantity_column ? 'selected' : ''  }} value="{{ $department->quantity_column }}">{{ $department->label }}</option>
                                    @endforeach
                                </select>
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
            @if(isset($stock))
                <livewire:product-module.batch.stock-batch :selectedDepartment="$selectedDepartment" :stock="$stock"/>
            @endif
        </div>
    </div>

@endsection
