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

        $(function () {
            var table =  $('#invoice-list').DataTable({
                buttons: [
                    'copy', 'excel', 'pdf'
                ],

            });


        });

    </script>
    <script>
        window.onload = function (){
            $(document).ready(function(){
                var path = '{{ route('findcustomer') }}'+"?select2=yes"
                var obj = this;
                var select =  $('.select2Product').select2({
                    placeholder: 'Select for Customer',
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

@section('pageHeaderAction')

    <x-report-filter-component :filters="$filters"/>

@endsection

@section('content')

    <h5><b>Opening Balance</b> : {{ number_format($opening,2) }}</h5>
    <div class="table-responsive">
    <table id="invoice-list" class="table table-bordered  table table-striped">
        <thead>
        <tr>
            <th>#</th>
            <th>Invoice</th>
            <th>Credit</th>
            <th>Payment</th>
            <th>Mode of payment</th>
            <th>Description</th>
            <th>Return or refund</th>
            <th>Date</th>
            <th>Balance</th>
        </tr>
        </thead>
        <tbody>
        @foreach($histories as $history)
            @php
                $opening = ($opening + $history->amount);
                $linkedInvoice = ($history->isCreditRecord() || $history->isCreditBalanceRefundRecord()) ? $history->resolveInvoice() : null;
                $isReturnedInvoiceRow = $history->isReturnedInvoice();
                $isCreditRefundRow = $history->isCreditBalanceRefundRecord();
                $invoiceReturns = ($isReturnedInvoiceRow || $isCreditRefundRow) ? $history->invoiceReturns() : collect();
            @endphp
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>
                    @if($linkedInvoice && userCanView('invoiceandsales.view'))
                        <a href="{{ route('invoiceandsales.view', $linkedInvoice->id) }}" target="_blank">{{ $linkedInvoice->invoice_number }}</a>
                    @else
                        {{ $history->invoiceNumberLabel() }}
                    @endif
                    @if($isReturnedInvoiceRow)
                        <span class="badge bg-warning text-dark ms-1">Returned</span>
                    @elseif($isCreditRefundRow)
                        <span class="badge bg-info text-dark ms-1">Return Refund</span>
                    @endif
                </td>
                <td>{{ $history->isCreditRecord() ? number_format($history->amount, 2) : "" }}</td>
                <td>{{ $history->isPaymentRecord() ? number_format($history->amount, 2) : "" }}</td>
                <td>{{ $history->modeOfPaymentLabel() }}</td>
                <td>
                    @if($isReturnedInvoiceRow && $invoiceReturns->isNotEmpty())
                        @php
                            $firstReturn = $invoiceReturns->first();
                            $lastReturn = $invoiceReturns->last();
                            $totalReturnedDiff = (float) $invoiceReturns->sum('return_difference');
                            $returnReasons = $invoiceReturns->map(function ($ret) {
                                return ($ret->return_reason === \App\Models\InvoiceReturn::REASON_OTHER && !empty($ret->other_reason_explanation))
                                    ? trim((string) $ret->other_reason_explanation)
                                    : trim((string) $ret->return_reason);
                            })->filter()->unique()->values()->all();
                        @endphp
                        <div>
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle mb-1">Invoice Returned</span>
                            @if(trim((string) $history->description) !== '')
                                <div class="mb-1">{{ $history->description }}</div>
                            @endif
                            <div class="small text-muted">
                                <strong>Original:</strong> {{ number_format((float) $firstReturn->original_invoice_amount, 2) }}
                                &bull; <strong>After Return:</strong> {{ number_format((float) $lastReturn->current_invoice_amount, 2) }}
                                &bull; <strong>Returned Value:</strong> {{ number_format($totalReturnedDiff, 2) }}
                            </div>
                            @if(!empty($returnReasons))
                                <div class="small text-muted">
                                    <strong>Reason:</strong> {{ implode('; ', $returnReasons) }}
                                </div>
                            @endif
                            @if(!empty($lastReturn->return_date))
                                <div class="small text-muted">
                                    <strong>Return Date:</strong> {{ eng_str_date($lastReturn->return_date) }}
                                </div>
                            @endif
                        </div>
                    @elseif($isCreditRefundRow && $invoiceReturns->isNotEmpty())
                        @php
                            $retRecord = $invoiceReturns->first();
                        @endphp
                        <div>
                            <div>{{ $history->descriptionLabel() }}</div>
                            <div class="small text-muted">
                                <strong>Original:</strong> {{ number_format((float) $retRecord->original_invoice_amount, 2) }}
                                &bull; <strong>After Return:</strong> {{ number_format((float) $retRecord->current_invoice_amount, 2) }}
                                &bull; <strong>Refunded to Credit Balance:</strong> +{{ number_format((float) $history->amount, 2) }}
                            </div>
                        </div>
                    @else
                        {{ $history->descriptionLabel() }}
                    @endif
                </td>
                <td>
                    @if($isReturnedInvoiceRow)
                        <div>
                            <span class="badge bg-warning text-dark">Returned</span>
                            @if($history->returnOrRefundLabel() !== '')
                                <div class="mt-1">{{ $history->returnOrRefundLabel() }}</div>
                            @endif
                        </div>
                    @elseif($isCreditRefundRow)
                        <div>
                            <span class="badge bg-success">Refunded</span>
                            <div class="mt-1">{{ $history->returnOrRefundLabel() }}</div>
                        </div>
                    @else
                        {{ $history->returnOrRefundLabel() }}
                    @endif
                </td>
                <td>{{ eng_str_date($history->payment_date) }}</td>
                <td>{{ number_format(($opening), 2) }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <th>Total Balance</th>
            <th>{{ number_format($opening, 2) }}</th>
        </tr>
        </tfoot>
    </table>
    </div>
@endsection
