<div  class="modal fade" id="showPaymentModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">

        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Payment Information</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <livewire:payment-manager.viewpayment-component :payment="$payment"/>
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

        }
    </script>
