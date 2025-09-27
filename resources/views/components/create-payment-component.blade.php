<div  class="modal fade" id="addPaymentModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">

        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Invoice Payment</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <livewire:payment-manager.addpayment-component :invoice="$invoice" :customer="$invoice->customer"/>
            </div>
        </div>
    </div>
</div>
    <script>
        window.onload = function (){
            window.addEventListener('refreshBrowser', (e) => {
                setTimeout(()=>{
                    window.location.reload();
                }, 1700);
            });
            let invoiceDiscountModal = "";
            let addPaymentModal = ""

            $(document).ready(function(){
                addPaymentModal = new  bootstrap.Modal(document.getElementById("addPaymentModal"), {});
            });

            window.addEventListener('closeAddPaymentModal', (e) => {
                addPaymentModal.hide();
            });
        }
    </script>
