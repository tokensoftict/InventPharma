<div>
    @if($this->mode == "requestApprovalDialog")
        <div  class="modal fade" wire:ignore.self id="creditapprovalModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" role="dialog" aria-hidden="true">

            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">

                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Credit Payment Approval Modal</h5>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12">
                                <p>Are you want to send this invoice out for credit payment approval</p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer ">
                        <button type="button" id="noBtn" class="btn btn-danger" wire:target="cancelBarcodeForApproval" wire:loading.attr="disabled" >
                            <span wire:loading wire:target="cancelBarcodeForApproval" class="spinner-border spinner-border-sm me-2" role="status"></span>
                            No
                        </button>
                        <button type="button" id="yesBtn" wire:target="sendBarcodeForApproval" wire:loading.attr="disabled" class="btn btn-primary">
                            <span wire:loading wire:target="sendBarcodeForApproval" class="spinner-border spinner-border-sm me-2" role="status"></span>
                            Yes
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($this->mode == "approveDecline" && $this->invoicePaymentApprovalStatus)
        <div  class="modal fade" wire:ignore.self id="creditapprovalModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" role="dialog" aria-hidden="true">

            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">

                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Credit Payment Approval Modal</h5>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12">
                                <h5>Are you want to approve the sum for <b>{{ money($this->invoicePaymentApprovalStatus->amount) }}</b> for Credit Payment</h5><br/>
                                <p><b>Customer</b> : {{ $this->invoice->customer->fullname }}</p>
                                <p><b>Credit Balance</b> : {{ $this->invoice->customer->credit_balance }}</p>
                                <p><b>Invoice Date</b> : {{ str_date2($this->invoice->invoice_date) }}</p>

                                <div class="row">
                                    <div class="col-12">
                                        <span class="d-block  text-center"  style="font-weight: bolder; font-size: 14px">Total Invoice Amount</span>
                                        <span class="d-block bg-success mt-2 pt-3 pb-3 rounded-1 text-white text-center" style="font-weight: bolder; font-size: 30px"> {{ money($this->invoicePaymentApprovalStatus->amount) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer ">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                        <button type="button" id="noBtn" class="btn btn-warning" wire:target="cancelBarcodeForApproval" wire:loading.attr="disabled" >
                            <span wire:loading wire:target="cancelBarcodeForApproval" class="spinner-border spinner-border-sm me-2" role="status"></span>
                            Decline
                        </button>
                        <button type="button" id="approveBtn" wire:target="approveCreditPayment" wire:loading.attr="disabled" class="btn btn-primary">
                            <span wire:loading wire:target="approveCreditPayment" class="spinner-border spinner-border-sm me-2" role="status"></span>
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


            $('#showConfirmCreditModal').on('click', function (e){
               e.preventDefault();
                myModal.show();
            });

            $('#yesBtn').on('click', function(){
                @this.sendBarcodeForApproval().then(function(response){
                    setTimeout(function(){
                        window.location.href = response.href;
                    },2000)
                });
            });

            $('#noBtn').on('click', function(){
                @this.cancelBarcodeForApproval().then(function(response){
                    setTimeout(function(){
                        window.location.href = response.href;
                    },2000)
                });
            });

            $('#approveBtn').on('click', function(){
                @this.approveCreditPayment().then(function(response){
                    setTimeout(function(){
                        window.location.href = response.href;
                    },2000)
                });
            });
        }
    </script>
</div>
