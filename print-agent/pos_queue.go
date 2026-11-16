package main

import (
	"encoding/json"
	"net/http"
	"sync"
	"time"
)

// The OPay terminal can only process one transaction at a time. Every request
// that talks to the terminal (purchase / inquiry / echo) takes a numbered
// ticket and waits its turn in a FIFO queue. When the active transaction
// finishes, the next waiting ticket is started automatically.

type posTicket struct {
	Number     int
	Kind       string // purchase | inquiry | echo
	OrderNo    string
	Label      string // free text shown to cashiers, e.g. invoice number
	AmountKobo int64
	EnqueuedAt time.Time
	StartedAt  time.Time
	ready      chan struct{}
	cancelled  chan struct{}
}

type posQueue struct {
	mu      sync.Mutex
	counter int
	active  *posTicket
	waiting []*posTicket
}

var posQ = &posQueue{}

// enqueue registers a ticket and starts it immediately if the terminal is free.
func (q *posQueue) enqueue(kind, orderNo, label string, amount int64) *posTicket {
	q.mu.Lock()
	defer q.mu.Unlock()
	q.counter++
	t := &posTicket{
		Number:     q.counter,
		Kind:       kind,
		OrderNo:    orderNo,
		Label:      label,
		AmountKobo: amount,
		EnqueuedAt: time.Now(),
		ready:      make(chan struct{}),
		cancelled:  make(chan struct{}),
	}
	q.waiting = append(q.waiting, t)
	q.dispatchLocked()
	return t
}

func (q *posQueue) dispatchLocked() {
	if q.active == nil && len(q.waiting) > 0 {
		t := q.waiting[0]
		q.waiting = q.waiting[1:]
		t.StartedAt = time.Now()
		q.active = t
		close(t.ready)
	}
}

// wait blocks until the ticket becomes active. Returns false (and removes the
// ticket) if done is closed first, e.g. the browser gave up waiting.
func (q *posQueue) wait(t *posTicket, done <-chan struct{}) bool {
	select {
	case <-t.ready:
		return true
	case <-t.cancelled:
		return false
	case <-done:
		q.mu.Lock()
		defer q.mu.Unlock()
		select {
		case <-t.ready: // became active in the meantime; hand the terminal on
			if q.active == t {
				q.active = nil
				q.dispatchLocked()
			}
		default:
			q.removeWaitingLocked(t)
		}
		return false
	}
}

func (q *posQueue) removeWaitingLocked(t *posTicket) bool {
	for i, w := range q.waiting {
		if w == t {
			q.waiting = append(q.waiting[:i], q.waiting[i+1:]...)
			return true
		}
	}
	return false
}

// release must be called once the active ticket has finished.
func (q *posQueue) release(t *posTicket) {
	q.mu.Lock()
	defer q.mu.Unlock()
	if q.active == t {
		q.active = nil
		q.dispatchLocked()
	}
}

// cancelWaiting removes a waiting (not yet started) purchase by order number.
func (q *posQueue) cancelWaiting(orderNo string) (found bool, isActive bool) {
	q.mu.Lock()
	defer q.mu.Unlock()
	if q.active != nil && q.active.OrderNo == orderNo {
		return true, true
	}
	for i, w := range q.waiting {
		if w.OrderNo == orderNo {
			q.waiting = append(q.waiting[:i], q.waiting[i+1:]...)
			close(w.cancelled)
			return true, false
		}
	}
	return false, false
}

func (q *posQueue) hasOrder(orderNo string) bool {
	q.mu.Lock()
	defer q.mu.Unlock()
	if q.active != nil && q.active.OrderNo == orderNo && q.active.Kind == "purchase" {
		return true
	}
	for _, w := range q.waiting {
		if w.OrderNo == orderNo && w.Kind == "purchase" {
			return true
		}
	}
	return false
}

func ticketView(t *posTicket, position int) map[string]interface{} {
	v := map[string]interface{}{
		"queue_number":    t.Number,
		"kind":            t.Kind,
		"order_no":        t.OrderNo,
		"label":           t.Label,
		"amount_kobo":     t.AmountKobo,
		"waiting_seconds": int(time.Since(t.EnqueuedAt).Seconds()),
	}
	if position > 0 {
		v["position"] = position // 1 = next up
	}
	if !t.StartedAt.IsZero() {
		v["started_at"] = t.StartedAt.Format(time.RFC3339)
		v["elapsed_seconds"] = int(time.Since(t.StartedAt).Seconds())
	}
	return v
}

func (q *posQueue) snapshot() map[string]interface{} {
	q.mu.Lock()
	defer q.mu.Unlock()
	waiting := make([]map[string]interface{}, 0, len(q.waiting))
	for i, w := range q.waiting {
		waiting = append(waiting, ticketView(w, i+1))
	}
	var active interface{}
	if q.active != nil {
		active = ticketView(q.active, 0)
	}
	return map[string]interface{}{
		"success": true,
		"busy":    q.active != nil,
		"active":  active,
		"waiting": waiting,
		"total":   len(waiting) + btoi(q.active != nil),
	}
}

func btoi(b bool) int {
	if b {
		return 1
	}
	return 0
}

// --- HTTP handlers ---

// GET /pos/queue - current transaction and everyone waiting.
func handlePOSQueue(w http.ResponseWriter, r *http.Request) {
	if r.Method == http.MethodOptions {
		w.WriteHeader(http.StatusOK)
		return
	}
	jsonResponse(w, http.StatusOK, posQ.snapshot())
}

// POST /pos/queue/cancel {order_no} - leave the queue before being started.
func handlePOSQueueCancel(w http.ResponseWriter, r *http.Request) {
	if r.Method == http.MethodOptions {
		w.WriteHeader(http.StatusOK)
		return
	}
	if r.Method != http.MethodPost {
		jsonError(w, http.StatusMethodNotAllowed, "Method not allowed")
		return
	}
	var body struct {
		OrderNo string `json:"order_no"`
	}
	if err := json.NewDecoder(r.Body).Decode(&body); err != nil || body.OrderNo == "" {
		jsonError(w, http.StatusBadRequest, "order_no is required")
		return
	}
	found, isActive := posQ.cancelWaiting(body.OrderNo)
	switch {
	case !found:
		jsonError(w, http.StatusNotFound, "Order is not in the queue")
	case isActive:
		jsonError(w, http.StatusConflict, "Transaction is already active on the terminal; cancel it on the POS device")
	default:
		jsonResponse(w, http.StatusOK, map[string]interface{}{"success": true, "message": "Removed from queue"})
	}
}
