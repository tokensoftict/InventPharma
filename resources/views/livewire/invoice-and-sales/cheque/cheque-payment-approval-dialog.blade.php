<div>

    @if($this->mode === "requestApprovalDialog")
        <div  class="modal fade" wire:ignore.self id="creditapprovalModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" role="dialog" aria-hidden="true">

            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">

                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Cheque Payment Approval Modal</h5>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12">
                                <h5 align="center">Are you want to send this invoice out for cheque payment approval ?</h5>

                                <table class="table table-bordered">
                                    <tr>
                                        <th><h5 class="pt-2" align="center">Select Bank</h5></th>
                                        <th>
                                            <select class="form-control-lg form-control" name="bank" wire:model="bank">
                                                <option value="">Select Bank</option>
                                                @foreach(banks() as $bank)
                                                    <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('bank') <span class="text-danger d-block">{{ $message }}</span> @enderror
                                        </th>
                                    </tr>
                                    <tr>
                                        <th><h5 class="pt-2" align="center">Select Cheque Date</h5></th>
                                        <th>
                                            <input wire:model="cheque_date" placeholder="Cheque Date" class="form-control form-control-lg datepicker-basic" x-init="initDatePicker()" wire:model="cheque_date" class="form-control" name="invoice_date" id="datepicker-basic">
                                            @error('cheque_date') <span class="text-danger d-block">{{ $message }}</span> @enderror
                                        </th>
                                    </tr>
                                    <tr>
                                        <th><h5 class="pt-2" align="center">Comment</h5></th>
                                        <th>
                                            <textarea name="comment" wire:model="comment" placeholder="Comment" cols="30" rows="5"
                                                      class="form-control form-control-lg"></textarea>
                                        </th>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer ">
                        <button type="button" id="noBtn" class="btn btn-danger" wire:target="cancelChequeForApproval" wire:loading.attr="disabled" >
                            <span wire:loading wire:target="cancelChequeForApproval" class="spinner-border spinner-border-sm me-2" role="status"></span>
                            No
                        </button>
                        <button type="button" id="yesBtn" wire:target="sendChequeForApproval" wire:loading.attr="disabled" class="btn btn-primary">
                            <span wire:loading wire:target="sendChequeForApproval" class="spinner-border spinner-border-sm me-2" role="status"></span>
                            Yes
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($this->mode === "approveDecline" && $this->invoicePaymentApprovalStatus)
            <div  class="modal fade" wire:ignore.self id="creditapprovalModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" role="dialog" aria-hidden="true">

                <div class="modal-dialog modal-lg modal-dialog-centered" role="document">

                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Cheque Payment Approval Modal</h5>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-12">
                                    <h5 align="center">Are you sure want to approve this invoice for cheque payment ?</h5>

                                    <table class="table table-bordered">
                                        <tr>
                                            <th><h5 class="pt-2" align="center">Bank</h5></th>
                                            <th>
                                                <h5 class="pt-2" align="center"> {{ $this->invoicePaymentApprovalStatus->bank->name }} </h5>
                                            </th>
                                        </tr>
                                        <tr>
                                            <th><h5 class="pt-2" align="center">Amount</h5></th>
                                            <th>
                                                <h5 class="pt-2" align="center"> {{ money($this->invoicePaymentApprovalStatus->amount) }} </h5>
                                            </th>
                                        </tr>
                                        <tr>
                                            <th><h5 class="pt-2" align="center">Cheque Date</h5></th>
                                            <th>
                                                <h5 class="pt-2" align="center"> {{ human_date($this->invoicePaymentApprovalStatus->date) }} </h5>
                                            </th>
                                        </tr>
                                        <tr>
                                            <th><h5 class="pt-2" align="center">Comment</h5></th>
                                            <th>
                                                <h5 class="pt-2" align="center"> {{ $this->invoicePaymentApprovalStatus->comment }} </h5>
                                            </th>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer ">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                            <button type="button" id="noBtn" class="btn btn-warning" wire:target="cancelChequeForApproval" wire:loading.attr="disabled" >
                                <span wire:loading wire:target="cancelChequeForApproval" class="spinner-border spinner-border-sm me-2" role="status"></span>
                                Decline
                            </button>
                            <button type="button" id="approveBtn" wire:target="approvalChequePayment" wire:loading.attr="disabled" class="btn btn-primary">
                                <span wire:loading wire:target="approvalChequePayment" class="spinner-border spinner-border-sm me-2" role="status"></span>
                                Approve
                            </button>
                        </div>
                    </div>
                </div>
            </div>
    @endif

    <script>
        let myModal = "";
        window.onload = function (){
            $(document).ready(function(){
                myModal = new bootstrap.Modal(document.getElementById("creditapprovalModal"), {});
                @if($this->mode == "requestApprovalDialog")
                myModal.show();
                @endif
            });


            $('#showConfirmChequeModal').on('click', function (e){
                e.preventDefault();
                myModal.show();
            });

            $('#yesBtn').on('click', function(){
                @this.sendChequeForApproval().then(function(response){
                    setTimeout(function(){
                        window.location.href = response.href;
                    },2000)
                });
            });

            $('#noBtn').on('click', function(){
                @this.cancelChequeForApproval().then(function(response){
                    setTimeout(function(){
                        window.location.href = response.href;
                    },2000)
                });
            });

            $('#approveBtn').on('click', function(){
                @this.approvalChequePayment().then(function(response){
                    setTimeout(function(){
                        window.location.href = response.href;
                    },2000)
                });
            });
        }
    </script>
</div>
