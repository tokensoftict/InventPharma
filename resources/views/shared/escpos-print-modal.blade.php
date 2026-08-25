{{-- ESC/POS Thermal Print Modal --}}
<div class="modal fade" id="escposPrintModal" tabindex="-1" aria-labelledby="escposPrintModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="escposPrintModalLabel">
                    <i class="mdi mdi-printer me-2"></i>Thermal Printer (ESC/POS)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{-- Status Message --}}
                <div id="escposStatusMessage" class="alert alert-info mb-3" style="display:none;"></div>

                {{-- Loading --}}
                <div id="escpos-section-loading" style="display:block;">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary mb-3" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="text-muted">Connecting to print agent...</p>
                    </div>
                </div>

                {{-- Agent Offline --}}
                <div id="escpos-section-agent-offline" style="display:none;">
                    <div class="text-center py-3">
                        <i class="mdi mdi-lan-disconnect text-danger" style="font-size:48px;"></i>
                        <h6 class="mt-3">Print Agent Not Available</h6>
                        <p class="text-muted small">
                            The ESC/POS Print Agent is not running on this computer.<br>
                            Please ensure <strong>InventoryPrintAgent</strong> is installed and running.
                        </p>
                    </div>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-primary" onclick="escposRetry()">
                            <i class="mdi mdi-refresh me-1"></i>Retry
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Cancel
                        </button>
                    </div>
                </div>

                {{-- No Printers Found --}}
                <div id="escpos-section-no-printers" style="display:none;">
                    <div class="text-center py-3">
                        <i class="mdi mdi-printer-off text-warning" style="font-size:48px;"></i>
                        <h6 class="mt-3">No Printers Found</h6>
                        <p class="text-muted small">No thermal printers are installed on this computer.</p>
                    </div>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-primary" onclick="escposRetry()">
                            <i class="mdi mdi-refresh me-1"></i>Retry
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </div>

                {{-- Printer Ready --}}
                <div id="escpos-section-printer-ready" style="display:none;">
                    <div class="mb-3">
                        <label for="escposPrinterSelect" class="form-label fw-bold">Select Printer</label>
                        <select id="escposPrinterSelect" class="form-select">
                            {{-- Populated by JS --}}
                        </select>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" id="escposPrintBtn" class="btn btn-primary flex-fill" onclick="escposDoPrint()">
                            <i class="fa fa-print me-2"></i>Print
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="escposTestPrint()">
                            <i class="mdi mdi-printer-check me-1"></i>Test
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Cancel
                        </button>
                    </div>
                </div>

                {{-- Print Success --}}
                <div id="escpos-section-print-success" style="display:none;">
                    <div class="text-center py-3">
                        <i class="mdi mdi-check-circle text-success" style="font-size:48px;"></i>
                        <h6 class="mt-3 text-success">Receipt Printed Successfully!</h6>
                    </div>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-success" data-bs-dismiss="modal">
                            <i class="mdi mdi-check me-1"></i>Done
                        </button>
                        <button type="button" class="btn btn-outline-primary" onclick="escposDoPrint()">
                            <i class="mdi mdi-printer me-1"></i>Print Again
                        </button>
                    </div>
                </div>

                {{-- Print Error --}}
                <div id="escpos-section-print-error" style="display:none;">
                    <div class="text-center py-3">
                        <i class="mdi mdi-alert-circle text-danger" style="font-size:48px;"></i>
                        <h6 class="mt-3 text-danger">Printing Failed</h6>
                        <p class="text-muted small">Please check the printer connection and try again.</p>
                    </div>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-primary" onclick="escposRetry()">
                            <i class="mdi mdi-refresh me-1"></i>Retry
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Cancel
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

{{-- ESC/POS Print Agent Scripts --}}
<script src="{{ asset('js/escpos-print-agent.js') }}"></script>
<script src="{{ asset('js/thermal-print-modal.js') }}"></script>
