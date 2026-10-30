package main

import (
	"encoding/json"
	"fmt"
	"io"
	"log"
	"net/http"
	"strconv"
	"strings"
	"sync"
	"time"
)

// OPay POS (USB / 232 serial) integration.
// Protocol: JSON request objects written to the serial port, JSON response
// object read back. See https://documentation.opayweb.com/doc/offline/pos-usb.html

// POSConfig holds serial settings for the OPay POS terminal.
type POSConfig struct {
	Enabled        bool   `yaml:"enabled"`
	Port           string `yaml:"port"`            // e.g. COM3
	BaudRate       int    `yaml:"baud_rate"`       // default 115200 (confirm with OPay / emulator)
	DataBits       int    `yaml:"data_bits"`       // default 8
	Parity         string `yaml:"parity"`          // none | even | odd
	StopBits       int    `yaml:"stop_bits"`       // 1 or 2
	TimeoutSeconds int    `yaml:"timeout_seconds"` // max wait for customer payment
	AppendNewline  bool   `yaml:"append_newline"`  // append "\r\n" after request JSON
}

func defaultPOSConfig() POSConfig {
	return POSConfig{
		Enabled:        true,
		BaudRate:       115200,
		DataBits:       8,
		Parity:         "none",
		StopBits:       1,
		TimeoutSeconds: 120,
	}
}

// serialPort is a minimal serial connection (Read returns 0 bytes on a short timeout).
type serialPort interface {
	io.ReadWriteCloser
}

var posMu sync.Mutex // only one terminal conversation at a time

// POS response codes
const (
	posCancel  = "201"
	posSuccess = "202"
	posPending = "203"
	posFail    = "205"
)

type posResult struct {
	Success       bool                   `json:"success"`
	Status        string                 `json:"status"` // success|cancelled|pending|failed|timeout
	ResponseCode  string                 `json:"response_code"`
	Message       string                 `json:"message"`
	AmountMatches *bool                  `json:"amount_matches,omitempty"`
	Data          map[string]interface{} `json:"data,omitempty"`
}

func statusFromCode(code string) string {
	switch code {
	case posSuccess:
		return "success"
	case posCancel:
		return "cancelled"
	case posPending:
		return "pending"
	case posFail:
		return "failed"
	}
	return "failed"
}

// extractJSON returns the first complete top-level JSON object in buf and the
// remaining bytes, or ok=false if no complete object is present yet.
func extractJSON(buf []byte) (obj []byte, rest []byte, ok bool) {
	start := -1
	depth := 0
	inStr, esc := false, false
	for i, c := range buf {
		if start == -1 {
			if c == '{' {
				start, depth = i, 1
			}
			continue
		}
		if inStr {
			switch {
			case esc:
				esc = false
			case c == '\\':
				esc = true
			case c == '"':
				inStr = false
			}
			continue
		}
		switch c {
		case '"':
			inStr = true
		case '{':
			depth++
		case '}':
			depth--
			if depth == 0 {
				return buf[start : i+1], buf[i+1:], true
			}
		}
	}
	return nil, buf, false
}

// posExchange sends a request and waits for a response object.
// If waitPastPending is true, PENDING (203) responses are skipped until a final
// result arrives or the timeout expires.
func posExchange(req map[string]interface{}, timeout time.Duration, waitPastPending bool) (map[string]interface{}, error) {
	pc := cfg.POS
	if !pc.Enabled {
		return nil, fmt.Errorf("POS integration is disabled in config.yaml")
	}
	if pc.Port == "" {
		return nil, fmt.Errorf("POS serial port is not configured (set pos.port in config.yaml, e.g. COM3)")
	}

	port, err := openSerial(pc)
	if err != nil {
		return nil, fmt.Errorf("cannot open %s: %v", pc.Port, err)
	}
	defer port.Close()

	payload, _ := json.Marshal(req)
	if pc.AppendNewline {
		payload = append(payload, '\r', '\n')
	}
	log.Printf("POS -> %s", strings.TrimSpace(string(payload)))
	if _, err := port.Write(payload); err != nil {
		return nil, fmt.Errorf("write to POS failed: %v", err)
	}

	deadline := time.Now().Add(timeout)
	var buf []byte
	var last map[string]interface{}
	chunk := make([]byte, 512)

	for time.Now().Before(deadline) {
		n, err := port.Read(chunk)
		if n > 0 {
			buf = append(buf, chunk[:n]...)
		}
		if err != nil && err != io.EOF {
			return last, fmt.Errorf("read from POS failed: %v", err)
		}
		for {
			obj, rest, ok := extractJSON(buf)
			if !ok {
				break
			}
			buf = rest
			log.Printf("POS <- %s", string(obj))
			var m map[string]interface{}
			if json.Unmarshal(obj, &m) != nil {
				continue
			}
			last = m
			code, _ := m["responseCode"].(string)
			if waitPastPending && code == posPending {
				continue
			}
			return m, nil
		}
	}
	return last, errPOSTimeout
}

var errPOSTimeout = fmt.Errorf("timed out waiting for POS response")

func buildResult(m map[string]interface{}, err error, expectedAmount string) posResult {
	if err != nil && m == nil {
		st := "failed"
		if err == errPOSTimeout {
			st = "timeout"
		}
		return posResult{Status: st, Message: err.Error()}
	}
	code, _ := m["responseCode"].(string)
	msg, _ := m["response"].(string)
	res := posResult{
		Success:      code == posSuccess,
		Status:       statusFromCode(code),
		ResponseCode: code,
		Message:      msg,
		Data:         m,
	}
	if err == errPOSTimeout {
		res.Success = false
		res.Status = "timeout"
		res.Message = "Timed out; last status: " + msg + ". Use inquiry to re-check the order."
	}
	if expectedAmount != "" {
		if got, ok := m["amount"].(string); ok {
			match := got == expectedAmount
			res.AmountMatches = &match
			if res.Success && !match {
				res.Success = false
				res.Status = "failed"
				res.Message = fmt.Sprintf("Amount mismatch: requested %s kobo, terminal reported %s kobo", expectedAmount, got)
			}
		}
	}
	return res
}

// --- HTTP handlers ---

func handlePOSPorts(w http.ResponseWriter, r *http.Request) {
	if r.Method == http.MethodOptions {
		w.WriteHeader(http.StatusOK)
		return
	}
	jsonResponse(w, http.StatusOK, map[string]interface{}{
		"success":         true,
		"ports":           listSerialPorts(),
		"configured_port": cfg.POS.Port,
	})
}

func handlePOSEcho(w http.ResponseWriter, r *http.Request) {
	if r.Method == http.MethodOptions {
		w.WriteHeader(http.StatusOK)
		return
	}
	if !posMu.TryLock() {
		jsonError(w, http.StatusConflict, "POS terminal is busy with another request")
		return
	}
	defer posMu.Unlock()

	m, err := posExchange(map[string]interface{}{"type": "echo"}, 5*time.Second, false)
	res := buildResult(m, err, "")
	// Echo replies may not carry a responseCode; any reply means the link works.
	if err == nil && m != nil {
		res.Success = true
		res.Status = "success"
		res.Message = "POS terminal responded"
	}
	jsonResponse(w, http.StatusOK, res)
}

func handlePOSPurchase(w http.ResponseWriter, r *http.Request) {
	if r.Method == http.MethodOptions {
		w.WriteHeader(http.StatusOK)
		return
	}
	if r.Method != http.MethodPost {
		jsonError(w, http.StatusMethodNotAllowed, "Method not allowed")
		return
	}
	var body struct {
		Amount  json.Number `json:"amount"`   // in kobo
		OrderNo string      `json:"order_no"` // business order number, max 32 chars
	}
	if err := json.NewDecoder(r.Body).Decode(&body); err != nil {
		jsonError(w, http.StatusBadRequest, "Invalid JSON body")
		return
	}
	kobo, err := strconv.ParseInt(body.Amount.String(), 10, 64)
	if err != nil || kobo <= 0 {
		jsonError(w, http.StatusBadRequest, "amount must be a positive integer in kobo")
		return
	}
	if body.OrderNo == "" || len(body.OrderNo) > 32 {
		jsonError(w, http.StatusBadRequest, "order_no is required and must be at most 32 characters")
		return
	}
	if !posMu.TryLock() {
		jsonError(w, http.StatusConflict, "POS terminal is busy with another request")
		return
	}
	defer posMu.Unlock()

	amount := strconv.FormatInt(kobo, 10)
	timeout := time.Duration(cfg.POS.TimeoutSeconds) * time.Second
	m, err := posExchange(map[string]interface{}{
		"amount":  amount,
		"type":    "purchase",
		"orderNo": body.OrderNo,
	}, timeout, true)
	jsonResponse(w, http.StatusOK, buildResult(m, err, amount))
}

func handlePOSInquiry(w http.ResponseWriter, r *http.Request) {
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
	if err := json.NewDecoder(r.Body).Decode(&body); err != nil || body.OrderNo == "" || len(body.OrderNo) > 32 {
		jsonError(w, http.StatusBadRequest, "order_no is required and must be at most 32 characters")
		return
	}
	if !posMu.TryLock() {
		jsonError(w, http.StatusConflict, "POS terminal is busy with another request")
		return
	}
	defer posMu.Unlock()

	m, err := posExchange(map[string]interface{}{
		"type":    "Inquiry",
		"orderNo": body.OrderNo,
	}, 15*time.Second, false)
	jsonResponse(w, http.StatusOK, buildResult(m, err, ""))
}
