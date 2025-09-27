<div  class="modal fade" id="dispatchInvoice" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog  modal-dialog-centered" role="document">

        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Dispatch Invoice</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <livewire:invoice-and-sales.dispatch.invoice-dispatcher-component :invoice="$invoice"/>
            </div>
        </div>
    </div>
</div>
