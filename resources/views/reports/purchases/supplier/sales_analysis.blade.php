@extends('layouts.app')

@section('pageHeaderTitle1', $title)
@section('pageHeaderDescription', $subtitle)

@section('css')
    <link rel="stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('libs/flatpickr/flatpickr.min.css') }}"/>
    <link href="{{ asset('libs/choices.js/public/assets/styles/choices.min.css') }}" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" type="text/css" href="{{ asset('DataTables/datatables.min.css') }}"/>
@endsection

@section('js')
    <script src="{{ asset('plugins/select2/js/select2.min.js') }}"></script>
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
                ajax: '{!! route('supplier_sales_analysis_table') !!}?from={{ $filters['filters']['between.invoice_date'][0] }}&to={{ $filters['filters']['between.invoice_date'][1] }}&department={{ $filters['filters']['custom_dropdown_id'] }}',
                columns: [

                    {data: 'supplier_name', name: 'supplier_name',orderable: true, searchable: true},
                    {data: 'supplier_email', name: 'supplier_email',orderable: true, searchable: false},
                    {data: 'total_qty', name: 'total_qty',orderable: true, searchable: false},
                    {data: 'total_selling_total', name: 'total_selling_total',orderable: true, searchable: false},
                    {data: 'total_cost_total', name: 'total_cost_total',orderable: true, searchable: false},
                    {data: 'profit', name: 'profit',orderable: true, searchable: false},
                ],
            });

            $('#invoice-list').on( 'draw.dt', function () {
                var tablesum = table.column(5).data().sum();
                $('#profit').html(formatMoney(tablesum));
                var tablesum = table.column(4).data().sum();
                $('#total_cost').html(formatMoney(tablesum));
                var tablesum = table.column(3).data().sum();
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
                <th>Supplier</th>
                <th>Supplier Email</th>
                <th>Total Qty Sold</th>
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
                <th class="text-right" id="total_selling"></th>
                <th class="text-right"  id="total_cost"></th>
                <th class="text-right" id="profit"></th>
            </tr>
            </tfoot>
        </table>
    </div>

@endsection
