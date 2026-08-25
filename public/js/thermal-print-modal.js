/**
 * Thermal Print Modal - Manages the ESC/POS printing workflow.
 * Shows printer selection, handles printing, and provides error recovery options.
 */
(function() {
    'use strict';

    let currentEscPosUrl = '';
    let isProcessing = false;

    /**
     * Main entry point — called when user clicks "Print Thermal (ESC/POS)".
     * @param {string} escPosUrl - Laravel route URL that returns ESC/POS binary
     */
    window.escposPrint = async function(escPosUrl) {
        currentEscPosUrl = escPosUrl;

        const modal = document.getElementById('escposPrintModal');
        if (!modal) {
            alert('ESC/POS print modal not found. Please refresh the page.');
            return;
        }

        // Reset modal state
        showModalSection('loading');
        setModalStatus('Checking print agent...', 'info');

        // Show the modal
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();

        // Check agent health
        try {
            await window.printAgent.checkHealth();
        } catch (e) {
            showModalSection('agent-offline');
            setModalStatus('Print agent is not running on this computer.', 'danger');
            return;
        }

        // Agent is online — load printers
        await loadPrinters();
    };

    /**
     * Load printer list from the agent.
     */
    async function loadPrinters() {
        showModalSection('loading');
        setModalStatus('Loading printers...', 'info');

        try {
            const result = await window.printAgent.getPrinters();
            const printers = result.printers || [];

            if (printers.length === 0) {
                showModalSection('no-printers');
                setModalStatus('No printers found on this computer.', 'warning');
                return;
            }

            // Populate printer dropdown
            const select = document.getElementById('escposPrinterSelect');
            select.innerHTML = '';
            printers.forEach(function(p) {
                const option = document.createElement('option');
                option.value = p.name;
                option.textContent = p.name + (p.default ? ' (Default)' : '');
                select.appendChild(option);
            });

            // Auto-select remembered printer or default
            const remembered = window.printAgent.getRememberedPrinter();
            if (remembered) {
                const exists = printers.find(p => p.name === remembered);
                if (exists) {
                    select.value = remembered;
                }
            } else {
                const defaultPrinter = printers.find(p => p.default);
                if (defaultPrinter) {
                    select.value = defaultPrinter.name;
                }
            }

            showModalSection('printer-ready');
            setModalStatus('Ready to print.', 'success');

        } catch (e) {
            showModalSection('agent-offline');
            setModalStatus('Error loading printers: ' + e.message, 'danger');
        }
    }

    /**
     * Execute the ESC/POS print.
     */
    window.escposDoPrint = async function() {
        if (isProcessing) return;
        isProcessing = true;

        const select = document.getElementById('escposPrinterSelect');
        const printerName = select ? select.value : '';

        if (!printerName) {
            setModalStatus('Please select a printer.', 'warning');
            isProcessing = false;
            return;
        }

        const printBtn = document.getElementById('escposPrintBtn');
        if (printBtn) {
            printBtn.disabled = true;
            printBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Printing...';
        }

        try {
            // Select printer on agent
            setModalStatus('Selecting printer...', 'info');
            await window.printAgent.selectPrinter(printerName);

            // Fetch ESC/POS bytes from Laravel
            setModalStatus('Generating receipt...', 'info');
            const response = await fetch(currentEscPosUrl, {
                method: 'GET',
                credentials: 'same-origin',
            });

            if (!response.ok) {
                const text = await response.text();
                throw new Error('Failed to generate receipt: ' + text);
            }

            const escPosBytes = await response.arrayBuffer();

            // Send to print agent
            setModalStatus('Sending to printer...', 'info');
            await window.printAgent.print(escPosBytes);

            // Success
            setModalStatus('Receipt printed successfully!', 'success');
            showModalSection('print-success');

        } catch (e) {
            setModalStatus('Print failed: ' + e.message, 'danger');
            showModalSection('print-error');
        } finally {
            isProcessing = false;
            if (printBtn) {
                printBtn.disabled = false;
                printBtn.innerHTML = '<i class="fa fa-print me-2"></i>Print';
            }
        }
    };

    /**
     * Retry printing.
     */
    window.escposRetry = function() {
        if (currentEscPosUrl) {
            window.escposPrint(currentEscPosUrl);
        }
    };

    /**
     * Send a test print.
     */
    window.escposTestPrint = async function() {
        setModalStatus('Sending test print...', 'info');
        try {
            const select = document.getElementById('escposPrinterSelect');
            if (select && select.value) {
                await window.printAgent.selectPrinter(select.value);
            }
            await window.printAgent.testPrint();
            setModalStatus('Test page sent to printer!', 'success');
        } catch (e) {
            setModalStatus('Test print failed: ' + e.message, 'danger');
        }
    };

    /**
     * Close the modal.
     */
    window.escposCloseModal = function() {
        const modal = document.getElementById('escposPrintModal');
        if (modal) {
            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) bsModal.hide();
        }
    };

    // ---- Helper Functions ----

    function showModalSection(section) {
        const sections = ['loading', 'agent-offline', 'no-printers', 'printer-ready', 'print-success', 'print-error'];
        sections.forEach(function(s) {
            const el = document.getElementById('escpos-section-' + s);
            if (el) {
                el.style.display = (s === section) ? 'block' : 'none';
            }
        });
    }

    function setModalStatus(message, type) {
        const el = document.getElementById('escposStatusMessage');
        if (el) {
            el.className = 'alert alert-' + type + ' mb-3';
            el.textContent = message;
            el.style.display = 'block';
        }
    }

})();
