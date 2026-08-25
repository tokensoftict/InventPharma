package main

import (
	"encoding/json"
	"fmt"
	"io"
	"log"
	"net/http"
	"os"
	"path/filepath"
	"runtime"
	"strings"
	"sync"
)

const version = "1.0.0"

var (
	cfg            *Config
	selectedPrinter string
	printerMu      sync.Mutex
)

func main() {
	// Load config
	exePath, _ := os.Executable()
	configPath := filepath.Join(filepath.Dir(exePath), "config.yaml")

	// Also check current directory
	if _, err := os.Stat(configPath); os.IsNotExist(err) {
		configPath = "config.yaml"
	}

	var err error
	cfg, err = LoadConfig(configPath)
	if err != nil {
		log.Printf("Warning: Could not load config from %s: %v. Using defaults.", configPath, err)
		cfg = DefaultConfig()
	}

	// On Windows, try to run as a service first
	if runtime.GOOS == "windows" {
		if runAsService() {
			return // Ran as service successfully
		}
		// Not running as service, run as console app
		log.Println("Running as console application (not a Windows service)")
	}

	startHTTPServer()
}

func startHTTPServer() {
	mux := http.NewServeMux()

	// Routes
	mux.HandleFunc("/health", corsMiddleware(handleHealth))
	mux.HandleFunc("/printers", corsMiddleware(authMiddleware(handlePrinters)))
	mux.HandleFunc("/printer", corsMiddleware(authMiddleware(handleGetPrinter)))
	mux.HandleFunc("/printer/select", corsMiddleware(authMiddleware(handleSelectPrinter)))
	mux.HandleFunc("/print", corsMiddleware(authMiddleware(handlePrint)))
	mux.HandleFunc("/test-print", corsMiddleware(authMiddleware(handleTestPrint)))

	addr := fmt.Sprintf("%s:%d", cfg.Host, cfg.Port)
	log.Printf("InventoryPrintAgent v%s starting on %s", version, addr)

	server := &http.Server{
		Addr:    addr,
		Handler: mux,
	}

	if err := server.ListenAndServe(); err != nil {
		log.Fatalf("Failed to start server: %v", err)
	}
}

// --- HTTP Handlers ---

func handleHealth(w http.ResponseWriter, r *http.Request) {
	if r.Method == http.MethodOptions {
		w.WriteHeader(http.StatusOK)
		return
	}

	jsonResponse(w, http.StatusOK, map[string]interface{}{
		"status":  "ok",
		"version": version,
		"os":      runtime.GOOS,
	})
}

func handlePrinters(w http.ResponseWriter, r *http.Request) {
	if r.Method == http.MethodOptions {
		w.WriteHeader(http.StatusOK)
		return
	}
	if r.Method != http.MethodGet {
		jsonError(w, http.StatusMethodNotAllowed, "Method not allowed")
		return
	}

	printers, err := EnumPrinters()
	if err != nil {
		jsonError(w, http.StatusInternalServerError, "Failed to enumerate printers: "+err.Error())
		return
	}

	jsonResponse(w, http.StatusOK, map[string]interface{}{
		"success":  true,
		"printers": printers,
	})
}

func handleGetPrinter(w http.ResponseWriter, r *http.Request) {
	if r.Method == http.MethodOptions {
		w.WriteHeader(http.StatusOK)
		return
	}
	if r.Method != http.MethodGet {
		jsonError(w, http.StatusMethodNotAllowed, "Method not allowed")
		return
	}

	printerMu.Lock()
	name := selectedPrinter
	printerMu.Unlock()

	if name == "" {
		name = cfg.DefaultPrinter
	}

	jsonResponse(w, http.StatusOK, map[string]interface{}{
		"success": true,
		"printer": map[string]interface{}{
			"name": name,
		},
	})
}

func handleSelectPrinter(w http.ResponseWriter, r *http.Request) {
	if r.Method == http.MethodOptions {
		w.WriteHeader(http.StatusOK)
		return
	}
	if r.Method != http.MethodPost {
		jsonError(w, http.StatusMethodNotAllowed, "Method not allowed")
		return
	}

	var req struct {
		Name string `json:"name"`
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		jsonError(w, http.StatusBadRequest, "Invalid request body")
		return
	}

	if req.Name == "" {
		jsonError(w, http.StatusBadRequest, "Printer name is required")
		return
	}

	printerMu.Lock()
	selectedPrinter = req.Name
	printerMu.Unlock()

	log.Printf("Selected printer: %s", req.Name)

	jsonResponse(w, http.StatusOK, map[string]interface{}{
		"success": true,
		"message": "Printer selected: " + req.Name,
	})
}

func handlePrint(w http.ResponseWriter, r *http.Request) {
	if r.Method == http.MethodOptions {
		w.WriteHeader(http.StatusOK)
		return
	}
	if r.Method != http.MethodPost {
		jsonError(w, http.StatusMethodNotAllowed, "Method not allowed")
		return
	}

	// Read raw ESC/POS binary from body
	data, err := io.ReadAll(io.LimitReader(r.Body, 10*1024*1024)) // Max 10MB
	if err != nil {
		jsonError(w, http.StatusBadRequest, "Failed to read request body: "+err.Error())
		return
	}
	defer r.Body.Close()

	if len(data) == 0 {
		jsonError(w, http.StatusBadRequest, "Empty print data")
		return
	}

	printerMu.Lock()
	printerName := selectedPrinter
	printerMu.Unlock()

	if printerName == "" {
		printerName = cfg.DefaultPrinter
	}

	if printerName == "" {
		jsonError(w, http.StatusBadRequest, "No printer selected. Select a printer first via POST /printer/select")
		return
	}

	log.Printf("Printing %d bytes to printer: %s", len(data), printerName)

	if err := RawPrint(printerName, data); err != nil {
		log.Printf("Print failed: %v", err)
		jsonError(w, http.StatusInternalServerError, "Print failed: "+err.Error())
		return
	}

	log.Printf("Print job sent successfully to: %s", printerName)

	jsonResponse(w, http.StatusOK, map[string]interface{}{
		"success": true,
		"message": "Print job sent successfully",
		"printer": printerName,
		"bytes":   len(data),
	})
}

func handleTestPrint(w http.ResponseWriter, r *http.Request) {
	if r.Method == http.MethodOptions {
		w.WriteHeader(http.StatusOK)
		return
	}
	if r.Method != http.MethodPost {
		jsonError(w, http.StatusMethodNotAllowed, "Method not allowed")
		return
	}

	printerMu.Lock()
	printerName := selectedPrinter
	printerMu.Unlock()

	if printerName == "" {
		printerName = cfg.DefaultPrinter
	}

	if printerName == "" {
		jsonError(w, http.StatusBadRequest, "No printer selected")
		return
	}

	// Build a simple ESC/POS test page
	testData := buildTestPage()

	if err := RawPrint(printerName, testData); err != nil {
		jsonError(w, http.StatusInternalServerError, "Test print failed: "+err.Error())
		return
	}

	jsonResponse(w, http.StatusOK, map[string]interface{}{
		"success": true,
		"message": "Test page sent to printer: " + printerName,
	})
}

func buildTestPage() []byte {
	var b []byte
	esc := byte(0x1B)
	gs := byte(0x1D)

	// Initialize
	b = append(b, esc, '@')
	// Center
	b = append(b, esc, 'a', 1)
	// Double size
	b = append(b, gs, '!', 0x11)
	b = append(b, []byte("TEST PRINT\n")...)
	// Normal size
	b = append(b, gs, '!', 0x00)
	b = append(b, []byte("InventoryPrintAgent v"+version+"\n")...)
	b = append(b, []byte(strings.Repeat("-", 32)+"\n")...)
	b = append(b, []byte("If you can read this, the\n")...)
	b = append(b, []byte("print agent is working!\n")...)
	b = append(b, []byte(strings.Repeat("-", 32)+"\n")...)
	// Feed and cut
	b = append(b, esc, 'd', 3)
	b = append(b, gs, 'V', 66, 3)

	return b
}

// --- Middleware ---

func corsMiddleware(next http.HandlerFunc) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		origin := r.Header.Get("Origin")

		// Allow requests from any local origin or the configured allowed origins
		if origin != "" {
			allowed := false
			for _, ao := range cfg.AllowedOrigins {
				if ao == "*" || ao == origin {
					allowed = true
					break
				}
			}
			if !allowed && (strings.HasPrefix(origin, "http://localhost") ||
				strings.HasPrefix(origin, "http://127.0.0.1") ||
				strings.HasPrefix(origin, "https://localhost") ||
				strings.HasPrefix(origin, "https://127.0.0.1")) {
				allowed = true
			}
			if allowed {
				w.Header().Set("Access-Control-Allow-Origin", origin)
			}
		}

		w.Header().Set("Access-Control-Allow-Methods", "GET, POST, OPTIONS")
		w.Header().Set("Access-Control-Allow-Headers", "Content-Type, Authorization")
		w.Header().Set("Access-Control-Max-Age", "3600")

		if r.Method == http.MethodOptions {
			w.WriteHeader(http.StatusOK)
			return
		}

		next(w, r)
	}
}

func authMiddleware(next http.HandlerFunc) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		if r.Method == http.MethodOptions {
			next(w, r)
			return
		}

		// Skip auth if no token configured
		if cfg.AuthToken == "" {
			next(w, r)
			return
		}

		authHeader := r.Header.Get("Authorization")
		if authHeader == "" {
			jsonError(w, http.StatusUnauthorized, "Authorization header required")
			return
		}

		token := strings.TrimPrefix(authHeader, "Bearer ")
		if token != cfg.AuthToken {
			jsonError(w, http.StatusForbidden, "Invalid auth token")
			return
		}

		next(w, r)
	}
}

// --- Helpers ---

func jsonResponse(w http.ResponseWriter, status int, data interface{}) {
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(status)
	json.NewEncoder(w).Encode(data)
}

func jsonError(w http.ResponseWriter, status int, message string) {
	jsonResponse(w, status, map[string]interface{}{
		"success": false,
		"error":   message,
	})
}
