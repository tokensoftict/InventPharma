@extends('layouts.app')

@section('pageHeaderTitle1', 'View Promotion')
@section('pageHeaderDescription', 'View all Information');


@section('css')
    <link rel="stylesheet" href="{{ asset('libs/flatpickr/flatpickr.min.css') }}"/>
    <link href="{{ asset('libs/choices.js/public/assets/styles/choices.min.css') }}" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" type="text/css" href="{{ asset('DataTables/datatables.min.css') }}"/>
@endsection

@section('js')
    <script type="text/javascript" src="{{asset('DataTables/datatables.min.js') }}"></script>
    <script src="{{ asset('libs/flatpickr/flatpickr.min.js') }}"></script>
    <script src="{{ asset('libs/choices.js/public/assets/scripts/choices.min.js') }}"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            flatpickr(".datepicker-basic", {  });
            var e = document.querySelectorAll("[data-trigger]");
            for (i = 0; i < e.length; ++i) {
                var a = e[i];
                new Choices(a, { placeholderValue: "This is a placeholder set in the config", searchPlaceholderValue: "This is a search placeholder" });
            }

            $(document).ready(function(){
                $('#invoice-list').DataTable({
                    dom: 'Bfrtip',

                    buttons: [
                        'copy', 'excel', 'pdf'
                    ],
                    fixedHeader: true,
                    responsive: true,
                    colReorder: true,
                    paging: true,
                    dom:  "<'row be-datatable-header'<'col-sm-4'l><'col-sm-4 text-right'B><'col-sm-4 text-right'f>>" +
                        "<'row be-datatable-body'<'col-sm-12'tr>>" +
                        "<'row be-datatable-footer'<'col-sm-5'i><'col-sm-7'p>>",
                    "lengthMenu": [[100, 250, 1000, -1], [100, 250, 1000, "All"]],
                });
            })

        });


    </script>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-3 col-lg-offset-3">
            <label>Promo Name</label>
            <span class="form-control">{{ $promotion->name }}</span>
        </div>
        <div class="col-md-3">
            <label>Promo Runs From</label>
            <span class="form-control">{{ $promotion->from_date->toDateString() }}</span>
        </div>
        <div class="col-md-3 ">
            <label>Promo Runs To</label>
            <span class="form-control">{{ $promotion->end_date->toDateString() }}</span>
        </div>
        <div class="col-md-3 ">
            <label>Status</label>
            <span class="form-control">{!! showStatus($promotion->status_id) !!}</span>
        </div>
    </div>
    <br/>  <br/>
    <h5>Promotions Stock(s)</h5>

    <div class="table-responsive" >
        <table id="invoice-list" class="table table-striped table-striped table-bordered">
            <thead>
            <tr>
                <th class="text-left">Stock ID</th>
                <th class="text-left">Name</th>
                <th class="text-center">Available Qty</th>
                <th class="text-center">WS Promo Price</th>
                <th class="text-center">Bulk Promo Price</th>
                <th class="text-center">Retail Promo Price</th>
            </tr>
            </thead>
            <tbody>
                @foreach($promotion->promotion_items as $item)
                    <tr>
                        <td>{{ $item->stock_id }}</td>
                        <td>{{ $item->stock->name }}</td>
                        <td>{{ $item->stock->totalBalance() }}</td>
                        <td class="text-center">
                            @if($item->whole_price > 0)
                            <span class="d-block font-size-11 text-danger" style="text-decoration: line-through;  ">{{ money($item->stock->getUneditedValues()['whole_price']) }}</span>
                            <span class="d-block font-size-13 text-primary" >{{ money($item->whole_price) }}</span>
                            @else
                                <span class="d-block font-size-13 text-primary" >{{ money($item->stock->whole_price) }}</span>
                                <span class="d-block font-size-11 text-warning" >No Promo</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($item->bulk_price > 0)
                            <span class="d-block font-size-11 text-danger" style="text-decoration: line-through ">{{ money($item->stock->getUneditedValues()['bulk_price']) }}</span>
                            <span class="d-block font-size-13 text-primary" >{{ money($item->bulk_price) }}</span>
                            @else
                                <span class="d-block font-size-13 text-primary">{{ money($item->stock->bulk_price) }}</span>
                                <span class="d-block font-size-11 text-warning" >No Promo</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($item->retail_price > 0)
                            <span class="d-block font-size-11 text-danger">{{ money($item->stock->getUneditedValues()['retail_price']) }}</span>
                            <span class="d-block font-size-13 text-primary" >{{ money($item->retail_price) }}</span>
                            @else
                                <span class="d-block font-size-13 text-primary">{{ money($item->stock->retail_price) }}</span>
                                <span class="d-block font-size-11 text-warning" >No Promo</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

@endsection