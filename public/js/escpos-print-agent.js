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

    // ---------------- OPay POS terminal (USB serial, via the agent) ----------------

    async _posRequest(path, body, timeoutMs) {
        const response = await fetch(this.agentUrl + path, {
            method: 'POST',
            headers: this._headers(),
            body: body ? JSON.stringify(body) : undefined,
            signal: AbortSignal.timeout(timeoutMs),
        });
        const result = await response.json().catch(() => ({}));
        if (!response.ok) {
            throw new Error(result.error || result.message || ('POS agent returned ' + response.status));
        }
        return result;
    }

    /** Test the link between the agent and the OPay terminal. */
    posEcho() {
        return this._posRequest('/pos/echo', null, 10000);
    }

    /**
     * Start a purchase on the terminal and wait for the customer to pay.
     * @param {number} amountKobo integer amount in kobo
     * @param {string} orderNo    business order number (max 32 chars)
     */
    // The terminal handles one transaction at a time; the agent queues the rest,
    // so the timeout must cover waiting in the queue + the customer's payment time.
    posPurchase(amountKobo, orderNo, label = '', timeoutMs = 10800000) {
        return this._posRequest('/pos/purchase', { amount: amountKobo, order_no: orderNo, label: label }, timeoutMs);
    }

    /** Re-check the status of an earlier order (waits its turn behind active transactions). */
    posInquiry(orderNo) {
        return this._posRequest('/pos/inquiry', { order_no: orderNo }, 600000);
    }

    /** Current POS transaction + everyone waiting in the queue. */
    async posQueue() {
        const response = await fetch(this.agentUrl + '/pos/queue', {
            headers: this._headers(),
            signal: AbortSignal.timeout(5000),
        });
        return await response.json();
    }

    /** Leave the queue before the transaction reaches the terminal. */
    posQueueCancel(orderNo) {
        return this._posRequest('/pos/queue/cancel', { order_no: orderNo }, 5000);
    }

    /**
     * Render a live POS queue into `container` (polls every 2s until stopped).
     * `myOrderNo()` returns the order number of this screen so it is highlighted.
     * Returns a stop() function.
     */
    startPosQueueWidget(container, myOrderNo, onMine) {
        const agent = this;
        const fmt = (k) => '₦' + (k / 100).toLocaleString(undefined, { minimumFractionDigits: 2 });
        const row = (cls, badge, t, note) => {
            const li = document.createElement('div');
            li.className = 'd-flex justify-content-between align-items-center border rounded px-2 py-1 mb-1 ' + cls;
            const left = document.createElement('span');
            const b = document.createElement('span');
            b.className = 'badge bg-dark me-2';
            b.textContent = '#' + t.queue_number;
            left.appendChild(b);
            left.appendChild(document.createTextNode((t.label || t.order_no || t.kind) + (t.amount_kobo ? ' · ' + fmt(t.amount_kobo) : '')));
            const right = document.createElement('small');
            right.className = 'text-muted';
            if (typeof note === 'string') right.textContent = note; else right.appendChild(note);
            li.appendChild(left);
            li.appendChild(right);
            return li;
        };
        const mmss = (sec) => {
            sec = Math.max(0, Math.round(sec));
            return Math.floor(sec / 60) + ':' + String(sec % 60).padStart(2, '0');
        };
        // Smooth 1s countdown between 2s polls: each timer span carries a deadline.
        if (container._cdTimer) clearInterval(container._cdTimer);
        container._cdTimer = setInterval(() => {
            container.querySelectorAll('[data-deadline]').forEach((el) => {
                const left = (parseInt(el.dataset.deadline, 10) - Date.now()) / 1000;
                el.textContent = el.dataset.prefix + mmss(left) + (el.dataset.suffix || '');
            });
        }, 1000);
        const timerNote = (prefix, seconds, suffix) => {
            const span = document.createElement('span');
            span.dataset.deadline = String(Date.now() + seconds * 1000);
            span.dataset.prefix = prefix;
            span.dataset.suffix = suffix || '';
            span.textContent = prefix + mmss(seconds) + (suffix || '');
            return span;
        };
        const render = (q) => {
            container.replaceChildren();
            if (!q || !q.total) {
                if (onMine) onMine(null, q);
                container.classList.add('d-none');
                return;
            }
            container.classList.remove('d-none');
            const mine = myOrderNo ? myOrderNo() : '';
            if (onMine) {
                const mineTicket = (q.active && q.active.order_no === mine) ? q.active
                    : (q.waiting || []).find((t) => t.order_no === mine);
                onMine(mineTicket || null, q);
            }
            const head = document.createElement('div');
            head.className = 'fw-bold small mb-1';
            head.textContent = 'OPay POS queue (' + q.total + ')';
            container.appendChild(head);
            if (q.active) {
                const me = mine && q.active.order_no === mine;
                const note = document.createElement('span');
                note.appendChild(document.createTextNode((me ? 'YOU · ' : '') + 'ACTIVE on terminal · '));
                note.appendChild(timerNote('', q.active.remaining_seconds || 0, ' left'));
                container.appendChild(row('border-success bg-success-subtle' + (me ? ' fw-bold' : ''), 'active', q.active, note));
            }
            (q.waiting || []).forEach((t) => {
                const me = mine && t.order_no === mine;
                const ahead = t.position - 1 + (q.active ? 1 : 0);
                const wnote = document.createElement('span');
                wnote.appendChild(document.createTextNode((me ? 'YOU · ' : '') + 'waiting · ' + ahead + ' ahead · up to '));
                wnote.appendChild(timerNote('', t.max_wait_seconds || 0));
                const r = row('border-warning' + (me ? ' bg-warning-subtle fw-bold' : ''), 'wait', t, wnote);
                if (me) {
                    const leave = document.createElement('button');
                    leave.type = 'button';
                    leave.className = 'btn btn-sm btn-outline-danger ms-2 py-0';
                    leave.textContent = 'Leave queue';
                    leave.onclick = () => agent.posQueueCancel(t.order_no).catch(() => {});
                    r.lastChild.appendChild(leave);
                }
                container.appendChild(r);
            });
        };
        let stopped = false;
        const tick = async () => {
            if (stopped) return;
            if (!container.isConnected) { stopped = true; if (container._cdTimer) clearInterval(container._cdTimer); if (window.opayQueueUI && window.opayQueueUI._stop) window.opayQueueUI._stop = null; return; }
            try { render(await agent.posQueue()); } catch (e) { /* agent unreachable */ }
            if (!stopped) setTimeout(tick, 2000);
        };
        tick();
        return () => { stopped = true; if (container._cdTimer) clearInterval(container._cdTimer); };
    }

    /**
     * Render a live POS queue into `container` (polls every 2s until stopped).
     * `myOrderNo()` returns the order number of this screen so it is highlighted.
     * Returns a stop() function.
     */
    startPosQueueWidget(container, myOrderNo) {
        const agent = this;
        const fmt = (k) => '₦' + (k / 100).toLocaleString(undefined, { minimumFractionDigits: 2 });
        const row = (cls, badge, t, note) => {
            const li = document.createElement('div');
            li.className = 'd-flex justify-content-between align-items-center border rounded px-2 py-1 mb-1 ' + cls;
            const left = document.createElement('span');
            const b = document.createElement('span');
            b.className = 'badge bg-dark me-2';
            b.textContent = '#' + t.queue_number;
            left.appendChild(b);
            left.appendChild(document.createTextNode((t.label || t.order_no || t.kind) + (t.amount_kobo ? ' · ' + fmt(t.amount_kobo) : '')));
            const right = document.createElement('small');
            right.className = 'text-muted';
            if (typeof note === 'string') right.textContent = note; else right.appendChild(note);
            li.appendChild(left);
            li.appendChild(right);
            return li;
        };
        const mmss = (sec) => {
            sec = Math.max(0, Math.round(sec));
            return Math.floor(sec / 60) + ':' + String(sec % 60).padStart(2, '0');
        };
        // Smooth 1s countdown between 2s polls: each timer span carries a deadline.
        if (container._cdTimer) clearInterval(container._cdTimer);
        container._cdTimer = setInterval(() => {
            container.querySelectorAll('[data-deadline]').forEach((el) => {
                const left = (parseInt(el.dataset.deadline, 10) - Date.now()) / 1000;
                el.textContent = el.dataset.prefix + mmss(left) + (el.dataset.suffix || '');
            });
        }, 1000);
        const timerNote = (prefix, seconds, suffix) => {
            const span = document.createElement('span');
            span.dataset.deadline = String(Date.now() + seconds * 1000);
            span.dataset.prefix = prefix;
            span.dataset.suffix = suffix || '';
            span.textContent = prefix + mmss(seconds) + (suffix || '');
            return span;
        };
        const render = (q) => {
            container.replaceChildren();
            if (!q || !q.total) { container.classList.add('d-none'); return; }
            container.classList.remove('d-none');
            const mine = myOrderNo ? myOrderNo() : '';
            const head = document.createElement('div');
            head.className = 'fw-bold small mb-1';
            head.textContent = 'OPay POS queue (' + q.total + ')';
            container.appendChild(head);
            if (q.active) {
                const me = mine && q.active.order_no === mine;
                const note = document.createElement('span');
                note.appendChild(document.createTextNode((me ? 'YOU · ' : '') + 'ACTIVE on terminal · '));
                note.appendChild(timerNote('', q.active.remaining_seconds || 0, ' left'));
                container.appendChild(row('border-success bg-success-subtle' + (me ? ' fw-bold' : ''), 'active', q.active, note));
            }
            (q.waiting || []).forEach((t) => {
                const me = mine && t.order_no === mine;
                const ahead = t.position - 1 + (q.active ? 1 : 0);
                container.appendChild(row('border-warning' + (me ? ' bg-warning-subtle fw-bold' : ''), 'wait', t,
                    (me ? 'YOU · ' : '') + 'waiting · ' + ahead + ' ahead'));
            });
        };
        let stopped = false;
        const tick = async () => {
            if (stopped) return;
            try { render(await agent.posQueue()); } catch (e) { /* agent unreachable */ }
            if (!stopped) setTimeout(tick, 2000);
        };
        tick();
        return () => { stopped = true; if (container._cdTimer) clearInterval(container._cdTimer); };
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

// Shared helper for the payment screens: shows the live queue and this
// cashier's place in it while a purchase request is pending on the agent.
window.opayQueueUI = {
    _stop: null,
    start(agent, orderNo, statusFn) {
        this.stop();
        let el = document.getElementById('opayQueue');
        if (!el) return;
        let seenWaiting = false;
        this._stop = agent.startPosQueueWidget(el, () => orderNo, (mine) => {
            if (!mine || !statusFn) return;
            if (mine.position) {
                seenWaiting = true;
                const ahead = mine.position - 1 + 1; // active transaction + those before us
                statusFn('Queue #' + mine.queue_number + ' - waiting for the terminal (' + ahead + ' transaction(s) ahead). It will start automatically.', 'secondary', true);
            } else if (seenWaiting) {
                statusFn('Your turn (Queue #' + mine.queue_number + ')! Waiting for customer to pay on the OPay terminal...', 'info', true);
            }
        });
    },
    stop() { if (this._stop) { this._stop(); this._stop = null; } },
};

// Passive monitor: whenever an OPay panel with a queue box is on screen, keep
// the live queue visible even if this cashier hasn't sent anything yet.
setInterval(() => {
    const el = document.getElementById('opayQueue');
    const panel = document.getElementById('opayPanel') || document.querySelector('[data-opay-url]');
    if (!el || !panel || window.opayQueueUI._stop) return;
    const agent = new EscPosPrintAgent(panel.dataset.opayUrl || 'http://127.0.0.1:9100');
    if (panel.dataset.opayToken) agent.setToken(panel.dataset.opayToken);
    window.opayQueueUI._stop = agent.startPosQueueWidget(el, () => panel.dataset.opayLastOrder || '');
}, 2500);

// Global singleton
window.EscPosPrintAgent = EscPosPrintAgent;
window.printAgent = new EscPosPrintAgent();
