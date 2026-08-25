/**
 * EscPosPrintAgent - Browser client for the Go Print Agent.
 * Communicates with the local print agent running on the client's Windows machine.
 */
class EscPosPrintAgent {
    constructor(agentUrl = 'http://127.0.0.1:9100') {
        this.agentUrl = agentUrl.replace(/\/$/, '');
        this.token = '';
    }

    /**
     * Set the authentication token.
     */
    setToken(token) {
        this.token = token;
    }

    /**
     * Get default headers with auth.
     */
    _headers(contentType = 'application/json') {
        const headers = {};
        if (contentType) {
            headers['Content-Type'] = contentType;
        }
        if (this.token) {
            headers['Authorization'] = 'Bearer ' + this.token;
        }
        return headers;
    }

    /**
     * Check if the print agent is running.
     * @returns {Promise<{status: string, version: string}>}
     */
    async checkHealth() {
        try {
            const response = await fetch(this.agentUrl + '/health', {
                method: 'GET',
                headers: this._headers(),
                signal: AbortSignal.timeout(3000),
            });
            if (!response.ok) throw new Error('Agent returned ' + response.status);
            return await response.json();
        } catch (e) {
            throw new Error('Print agent is not available: ' + e.message);
        }
    }

    /**
     * Get list of installed printers.
     * @returns {Promise<{success: boolean, printers: Array}>}
     */
    async getPrinters() {
        const response = await fetch(this.agentUrl + '/printers', {
            method: 'GET',
            headers: this._headers(),
            signal: AbortSignal.timeout(5000),
        });
        if (!response.ok) throw new Error('Failed to get printers');
        return await response.json();
    }

    /**
     * Get the currently selected printer.
     * @returns {Promise<{success: boolean, printer: object}>}
     */
    async getSelectedPrinter() {
        const response = await fetch(this.agentUrl + '/printer', {
            method: 'GET',
            headers: this._headers(),
            signal: AbortSignal.timeout(3000),
        });
        if (!response.ok) throw new Error('Failed to get selected printer');
        return await response.json();
    }

    /**
     * Select a printer by name.
     * @param {string} printerName
     * @returns {Promise<{success: boolean}>}
     */
    async selectPrinter(printerName) {
        const response = await fetch(this.agentUrl + '/printer/select', {
            method: 'POST',
            headers: this._headers(),
            body: JSON.stringify({ name: printerName }),
            signal: AbortSignal.timeout(3000),
        });
        if (!response.ok) throw new Error('Failed to select printer');
        const result = await response.json();
        this.rememberPrinter(printerName);
        return result;
    }

    /**
     * Send raw ESC/POS bytes to the printer.
     * @param {ArrayBuffer} escPosBytes - Raw binary data
     * @returns {Promise<{success: boolean}>}
     */
    async print(escPosBytes) {
        const headers = {};
        headers['Content-Type'] = 'application/octet-stream';
        if (this.token) {
            headers['Authorization'] = 'Bearer ' + this.token;
        }

        const response = await fetch(this.agentUrl + '/print', {
            method: 'POST',
            headers: headers,
            body: escPosBytes,
            signal: AbortSignal.timeout(15000),
        });

        if (!response.ok) {
            const text = await response.text();
            throw new Error('Print failed: ' + text);
        }
        return await response.json();
    }

    /**
     * Send a test print to the selected printer.
     * @returns {Promise<{success: boolean}>}
     */
    async testPrint() {
        const response = await fetch(this.agentUrl + '/test-print', {
            method: 'POST',
            headers: this._headers(),
            signal: AbortSignal.timeout(10000),
        });
        if (!response.ok) throw new Error('Test print failed');
        return await response.json();
    }

    /**
     * Remember the selected printer in localStorage.
     */
    rememberPrinter(name) {
        try {
            localStorage.setItem('escpos_printer', name);
        } catch (e) {
            // localStorage not available
        }
    }

    /**
     * Get the remembered printer from localStorage.
     */
    getRememberedPrinter() {
        try {
            return localStorage.getItem('escpos_printer');
        } catch (e) {
            return null;
        }
    }
}

// Global singleton
window.EscPosPrintAgent = EscPosPrintAgent;
window.printAgent = new EscPosPrintAgent();
