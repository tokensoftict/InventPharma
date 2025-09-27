<div>

    <div class="row  border-bottom">
        <div class="col-4">
            <div class="card-sales-split" style="border-bottom: none">
                <h2>Invoice Reference : {{ $this->invoice->invoice_number }}</h2>
            </div>
        </div>
        <div class="col-8">

            <div class="float-end">
                @if(can(['edit','showPayment','pay','dispatched','printAfour','printThermal','printWaybill','delete', 'return', 'applyForCredit', 'applyForCheque', 'approveChequePayment' ,'approveCreditPayment', 'processOnlineInvoice', 'packOnlineInvoice'],$this->invoice))
                    <div class="btn-group" role="group">
                        <button id="btnGroupDrop1" type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            Invoice  Action <i class="mdi mdi-chevron-down"></i>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="btnGroupDrop1" style="">
                            @can('edit', $this->invoice)
                                <li>
                                    <a href="{{ route('invoiceandsales.edit',$this->invoice->id) }}" class="dropdown-item">Edit Invoice</a>
                                </li>
                            @endcan

                            @can('processOnlineInvoice', $this->invoice)
                                <li>
                                    <a class="dropdown-item confirm_action" data-msg="Are you sure, you want to Process/Pack Invoice this invoice, this can not be reversed" href="{{ route('invoiceandsales.processOnlineInvoice',$this->invoice->id) }}">Process/Pack Invoice</a>
                                </li>
                            @endcan

                            @can('packOnlineInvoice', $this->invoice)
                                <li>
                                    <a class="dropdown-item confirm_action" data-msg="Are you sure, you want to Mark the Invoice has Packed, this can not be reversed" href="{{ route('invoiceandsales.packOnlineInvoice',$this->invoice->id) }}">Mark Invoice has Packed</a>
                                </li>
                            @endcan

                            @can('return', $this->invoice)
                                <li>
                                    <a href="{{ route('invoiceandsales.return',$this->invoice->id) }}" class="dropdown-item">Return Invoice</a>
                                </li>
                            @endcan

                            @can('applyForCredit', $this->invoice)
                                <li>
                                    <a href="{{ route('invoiceandsales.applyForCredit',$this->invoice->id) }}" class="dropdown-item">Apply For Credit Payment</a>
                                </li>
                            @endcan

                            @can('applyForCheque', $this->invoice)
                                <li>
                                    <a href="{{ route('invoiceandsales.applyForCheque',$this->invoice->id) }}" class="dropdown-item">Apply For Cheque Payment</a>
                                </li>
                            @endcan

                            @can('approveCreditPayment', $this->invoice)
                                <li>
                                    <a href="#" id="showConfirmCreditModal" class="dropdown-item">Approve/Decline Credit Payment</a>
                                </li>
                            @endcan

                            @can('approveChequePayment', $this->invoice)
                                <li>
                                    <a href="#" id="showConfirmChequeModal" class="dropdown-item">Approve/Decline Cheque Payment</a>
                                </li>
                            @endcan

                            @can('showPayment', $this->invoice)
                                <li>
                                    <a href="javascript:void(0);" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#showPaymentModal">Show Payments</a>
                                </li>
                            @endcan

                            @can('pay', $this->invoice)
                                <li>
                                    <a href="javascript:void(0);" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#addPaymentModal">Add Payment</a>
                                </li>
                            @endcan

                            @can('dispatched', $this->invoice)
                                <li>
                                    <a href="javascript:void(0);" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#dispatchInvoice">Dispatched Invoice</a>
                                </li>
                            @endcan


                            @can('printAfour', $this->invoice)
                                <li>
                                    <a href="{{ route('invoiceandsales.print_afour',$this->invoice->id) }}" class="dropdown-item print">Print A4</a>
                                </li>

                                @if($this->onlineinvoice !== "")
                                    <li>
                                        <a target="_new" href="{{ $this->onlineinvoice }}" class="dropdown-item print">Print Online Invoice</a>
                                    </li>
                                @endif

                                @if($this->onlinewaybill !== "")
                                    <li>
                                        <a target="_new" href="{{ $this->onlinewaybill }}" class="dropdown-item print">Print Online Waybill</a>
                                    </li>
                                @endif

                            @endcan
                            @can('printThermal', $this->invoice)
                                <li>
                                    <a href="{{ route('invoiceandsales.pos_print',$this->invoice->id) }}" class="dropdown-item print">Print Thermal</a>
                                </li>
                            @endcan
                            @can('printWaybill', $this->invoice)
                                <li>
                                    <a href="{{ route('invoiceandsales.print_way_bill',$this->invoice->id) }}" class="dropdown-item print">Print Waybill</a>
                                </li>
                            @endcan
                            @can('canAddToWaitingList', $this->invoice)
                                <li>
                                    <a href="{{ route('invoiceandsales.addToWaitingList',$this->invoice->id) }}" href="javascript:void(0);" onclick="return confirm('Are you sure want to add this invoice to waiting list, this can not be reversed');" class="dropdown-item confirm-text">Add To Waiting List</a>
                                </li>
                            @endcan

                            @can('canRemoveToWaitingList', $this->invoice)
                                <li>
                                    <a href="{{ route('invoiceandsales.removeFromWaitingList',$this->invoice->id) }}" href="javascript:void(0);" onclick="return confirm('Are you sure want to remove this invoice to waiting list, this can not be reversed');" class="dropdown-item confirm-text">Remove Waiting List</a>
                                </li>
                            @endcan


                            @can('setWaitingListInvoiceToPacking', $this->invoice)
                                <li>
                                    <a href="{{ route('invoiceandsales.packWaitingListInvoice',$this->invoice->id) }}" href="javascript:void(0);" onclick="return confirm('Are you sure you want set the invoice queue status to packing, this can not be reversed');" class="dropdown-item confirm-text">Start Packing</a>
                                </li>
                            @endcan

                            @can('setWaitingListInvoiceToPacked', $this->invoice)
                                <li>
                                    <a href="{{ route('invoiceandsales.packedWaitingListInvoice',$this->invoice->id) }}" href="javascript:void(0);" onclick="return confirm('Are you sure you want set the invoice queue status to packed, this can not be reversed');" class="dropdown-item confirm-text">Complete Packing</a>
                                </li>
                            @endcan

                            @can('setWaitingListInvoiceToPicking', $this->invoice)
                                <li>
                                    <a href="{{ route('invoiceandsales.pickWaitingListInvoice',$this->invoice->id) }}" href="javascript:void(0);" onclick="return confirm('Are you sure you want set the invoice queue status to picking, this can not be reversed');" class="dropdown-item confirm-text">Start Picking</a>
                                </li>
                            @endcan

                            @can('setWaitingListInvoiceToCompletePicking', $this->invoice)
                                <li>
                                    <a href="{{ route('invoiceandsales.completePickingWaitingListInvoice',$this->invoice->id) }}" href="javascript:void(0);" onclick="return confirm('Are you sure you want set the invoice queue status to complete picking, this can not be reversed');" class="dropdown-item confirm-text">Complete Picking</a>
                                </li>
                            @endcan

                        </ul>
                    </div>
                @endif

                @if(can(['applyInvoiceDiscount','applyProductDiscount'],$this->invoice))
                    <div class="btn-group" role="group">
                        <button id="btnGroupDrop1" type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            Add Discounts <i class="mdi mdi-chevron-down"></i>
                        </button>

                        <ul class="dropdown-menu" aria-labelledby="btnGroupDrop1" style="">
                            @can('applyInvoiceDiscount', $this->invoice)
                                <li>
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#invoiceDiscountModal" class="dropdown-item">Invoice Discount</a>
                                </li>
                            @endcan
                            @can('applyProductDiscount', $this->invoice)
                                <li>
                                    <a href="{{ route('invoiceandsales.applyProductDiscount', $this->invoice->id) }}" class="dropdown-item" >Product Discount</a>
                                </li>
                            @endcan
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-4">


            <span class="d-block pb-1 pt-1"> Status : </span>
            <strong  class="d-block pb-1 pt-1">{!! showStatus($this->invoice->status) !!}</strong>
            <br/>
            <span class="d-block pb-1 pt-1"> Invoice Date : </span>
            <strong  class="d-block pb-1 pt-1">{!! convert_date($this->invoice->invoice_date) !!}</strong>
            <br/>

            <span class="d-block pb-1 pt-1"> Invoice Time : </span>
            <strong  class="d-block pb-1 pt-1">{!! twelveHourClock($this->invoice->sales_time) !!}</strong>
            <br/>

            <hr/>
            <h3>Invoice Properties</h3>
            <hr/>

            <span class="d-block pb-1 pt-1"> Created By : </span>
            <strong  class="d-block pb-1 pt-1">{{ $this->invoice->create_by->name ?? "Not Available" }}</strong>
            <br/>
            <span class="d-block pb-1 pt-1"> Last Updated By : </span>
            <strong  class="d-block pb-1 pt-1">{{ $this->invoice->last_updated->name ?? "Not Available" }}</strong>
            <br/>
            <span class="d-block pb-1 pt-1"> Department : </span>
            <strong  class="d-block pb-1 pt-1">{{ \App\Classes\Settings::$department[$this->invoice->in_department] ?? "Not Available" }}</strong>
            <br/>

            <span class="d-block pb-1 pt-1"> Picked By : </span>
            <strong  class="d-block pb-1 pt-1">{{ $this->invoice->picked->name ?? "Not Available" }}</strong>

            <br/>
            <span class="d-block pb-1 pt-1"> Checked By : </span>
            <strong  class="d-block pb-1 pt-1">{{ $this->invoice->checked->name ?? "Not Available" }}</strong>
            <br/>
            <span class="d-block pb-1 pt-1"> Packed By : </span>
            <strong  class="d-block pb-1 pt-1">{{ $this->invoice->packed->name ?? "Not Available" }}</strong>
            <br/>

            <span class="d-block pb-1 pt-1"> No Of Cartons : </span>
            <strong  class="d-block pb-1 pt-1">{{ $this->invoice->carton_no ?? "Not Available" }}</strong>
            <br/>

            <span class="d-block pb-1 pt-1">Checkout Scan By : </span>
            <strong  class="d-block pb-1 pt-1">{{ $this->invoice->scan_by->name ?? "Not Available" }}</strong>
            <br/>

            <span class="d-block pb-1 pt-1">Checkout Scan Date : </span>
            <strong  class="d-block pb-1 pt-1">{!! $this->invoice->scan_date ?  convert_date($this->invoice->scan_date) : "Not Available" !!}</strong>
            <br/>

            <span class="d-block pb-1 pt-1">Checkout Scan Time : </span>
            <strong  class="d-block pb-1 pt-1">{!! $this->invoice->scan_time ? twelveHourClock($this->invoice->scan_time) : "Not Available" !!}</strong>
            <br/>

        </div>
        <div class="col-8">

            <div class="row mt-3">
                <div class="col-6">
                    <address class="pt-1">
                        <strong>CUSTOMER:</strong><br>
                        <span class="d-block pb-1 pt-1"> Name :  <strong>{{ $this->invoice->customer->firstname ?? "" }} {{ $this->invoice->customer->lastname ?? "" }}</strong></span>
                        <span class="d-block pb-1"> Phone Number: <strong>{{ $this->invoice->customer->phone_number ?? "" }}</strong></span>
                        <span class="d-block pb-1"> Address : <strong>{{ $this->invoice->customer->address ?? "" }}</strong></span>
                    </address>
                </div>

            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <hr/>
                    <h3>Invoice Items</h3>
                    <hr/>
                    <div class="table-responsive" wire:ignore.self>
                        <table class="table table-striped datanew table-hover table-bordered" >
                            <thead>
                            <tr>
                                <th>#</th>
                                <th class="text-left" style="width: 40%;font-size: 14px">Name</th>
                                <th class="text-center" style="width: 10%;font-size: 14px">Rate</th>
                                <th class="text-center" style="width: 15%;font-size: 14px">Discount</th>
                                <th class="text-center" style="width: 10%;font-size: 14px">Quantity</th>
                                <th class="text-end" style="width: 10%;font-size: 14px">Total</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($invoice->invoiceitems as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="text-left">{{ $item->stock->name }}</td>
                                    <td class="text-center">{{ money($item->selling_price - $item->discount_amount) }}</td>
                                    <td class="text-center">{{ money($item->discount_amount) }}</td>
                                    <td class="text-center">{{ $item->quantity }}</td>
                                    <td class="text-end">{{ money($item->quantity *  ($item->selling_price-$item->discount_amount)) }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                            <tfoot>
                            @if($this->invoice->onlineordertotals->count() > 0)
                                @foreach($this->invoice->onlineordertotals as $total)
                                    <tr>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <th class="text-right">{{ str_replace('Subtotal', 'Sub total', $total->name) }}</th>
                                        <th class="text-right">{{ money($total->value) }}</th>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <th class="text-right">Sub Total</th>
                                    <th class="text-right">{{ money($invoice->sub_total) }}</th>
                                </tr>
                            @endif
                            <tr>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <th class="text-right">Discount</th>
                                <th class="text-right">-{{ money($invoice->discount_amount,2) }}</th>
                            </tr>
                            <tr>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <th class="text-right">Total</th>
                                <th class="text-right" style="font-size: 16px;">{{ money(($invoice->sub_total -$invoice->discount_amount),2) }}</th>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            <br/>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#invoice_logs">View Invoice Log(s)</button>

            @if($invoice->status_id === status('Discount'))
                <button type="button" wire:target="sendBackToDraft"  wire:loading.attr="disabled"  class="btn btn-dark" wire:click="sendBackToDraft" >
                    Send Back To Draft    <span wire:loading wire:target="sendBackToDraft" class="spinner-border spinner-border-sm me-2" role="status"></span>
                </button>
            @endif

            @if(userCanView('invoiceandsales.requestForDiscount') && $invoice->status_id === status('Draft'))
                <button type="button" wire:target="requestForDiscount"  wire:loading.attr="disabled"  class="btn btn-success" wire:click="requestForDiscount" >
                    Request For Discount    <span wire:loading wire:target="requestForDiscount" class="spinner-border spinner-border-sm me-2" role="status"></span>
                </button>

            @endif
        </div>



    </div>

    <div wire:ignore id="invoice_logs" class="modal fade" role="dialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Invoice Activity Log(s)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-bordered datanew" id="table">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Action</th>
                                <th>By</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($invoice->invoiceactivitylogs->sortByDesc('id') as $logs)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ convert_date($logs->activity_date) }}</td>
                                    <td>{{ twelveHourClock($logs->activity_time) }}</td>
                                    <td>{{ $logs->activity }}</td>
                                    <td>{{ $logs->user->name }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore class="modal fade" id="invoiceDiscountModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Apply Invoice Discount</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="discount_type">Discount Type</label>
                        <select  required wire:model="discount_type" class="form-control" name="discount_type">
                            <option value="None" >None</option>
                            <option value="Percentage">Percentage</option>
                            <option {{  $this->invoice->discount_type == "Fixed" ? 'selected' : '' }} value="Fixed">Fixed</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="discount_type">Discount Value</label>
                        <input  type="number" wire:model="discount_value" required step="0.0000000001" name="discount_value" class="form-control input-sm"/>
                    </div>
                    <button type="button" wire:target="applyInvoiceDiscount" wire:loading.attr="disabled" wire:click="applyInvoiceDiscount" class="btn btn-success btn-sm">
                        Apply Discount
                        <span wire:loading wire:target="applyInvoiceDiscount" class="spinner-border spinner-border-sm me-2" role="status"></span>
                    </button>
                </div>
            </div>

        </div>
    </div>


    <script>

        window.onload = function (){
            let invoiceDiscountModal = "";
            let addPaymentModal = ""

            $(document).ready(function(){
                invoiceDiscountModal = new bootstrap.Modal(document.getElementById("invoiceDiscountModal"), {});
                addPaymentModal = new  new bootstrap.Modal(document.getElementById("addPaymentModal"), {});
            });

            window.addEventListener('closeAddPaymentModal', (e) => {
                addPaymentModal.hide();
            });

            window.addEventListener('closeInvoiceDiscountModal', (e) => {
                invoiceDiscountModal.show();
            });

            window.addEventListener('refreshBrowser', (e) => {
                setTimeout(()=>{
                    window.location.reload();
                }, 2000);
            });

        }
    </script>

</div>
