@extends('layouts.app')

@section('pageHeaderTitle1', $title)
@section('pageHeaderDescription', $subtitle)

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
        });

        jQuery.fn.dataTable.Api.register( 'sum()', function ( ) {
            return this.flatten().reduce( function ( a, b ) {
                if ( typeof a === 'string' ) {
                    a = a.replace(/[^\d.-]/g, '') * 1;
                }
                if ( typeof b === 'string' ) {
                    b = b.replace(/[^\d.-]/g, '') * 1;
                }

                return a + b;
            }, 0 );
        } );

        $(function () {
            var table =  $('#invoice-list').DataTable({
                processing: true,
                serverSide: true,
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
                ajax: '{!! route('profitandlossdatatablebydepartment') !!}?from={{ $filters['filters']['between.invoice_date'][0] }}&to={{ $filters['filters']['between.invoice_date'][1] }}&department={{ $filters['filters']['department'] }}',
                columns: [

                    {data: 'product_name', name: 'product_name',orderable: true, searchable: true},
                    {data: 'category', name: 'category',orderable: true, searchable: false},
                    {data: 'total_qty', name: 'total_qty',orderable: true, searchable: false},
                    {data: 'selling_price', name: 'selling_price',orderable: true, searchable: false},
                    {data: 'cost_price', name: 'cost_price',orderable: true, searchable: false},
                    {data: 'tt_selling_price', name: 'tt_selling_price',orderable: true, searchable: false},
                    {data: 'tt_cost_price', name: 'tt_cost_price',orderable: true, searchable: false},
                    {data: 'profit', name: 'profit',orderable: true, searchable: false},
                ],
            });

            $('#invoice-list').on( 'draw.dt', function () {
                var tablesum = table.column(7).data().sum();
                $('#profit').html(formatMoney(tablesum));
                var tablesum = table.column(6).data().sum();
                $('#total_cost').html(formatMoney(tablesum));
                var tablesum = table.column(5).data().sum();
                $('#total_selling').html(formatMoney(tablesum));
            } );
        });

    </script>
@endsection

@section('pageHeaderAction')

    <x-report-filter-component :filters="$filters"/>

@endsection

@section('content')
    <div class="table-responsive">
    <table class="table table-bordered table-striped m-t-40" id="invoice-list">
        <thead>
        <tr>
            <th>Product Name</th>
            <th>Category</th>
            <th>Total Qty Sold</th>
            <th>Av. Selling Price</th>
            <th>Av. Cost Price</th>
            <th  class="text-right">Total Selling Price</th>
            <th  class="text-right">Total Cost Price</th>
            <th  class="text-right">Profit</th>
        </tr>
        </thead>
        <tfoot>
        <tr>
            <th></th>
            <th></th>
            <th></th>
            <th></th>
            <th></th>
            <th class="text-right" id="total_selling"></th>
            <th class="text-right"  id="total_cost"></th>
            <th class="text-right" id="profit"></th>
        </tr>
        </tfoot>
    </table>
    </div>
@endsection
