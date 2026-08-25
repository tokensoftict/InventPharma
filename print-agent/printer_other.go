//go:build !windows
// +build !windows

package main

import "fmt"

// PrinterInfo represents a discovered printer.
type PrinterInfo struct {
	Name    string `json:"name"`
	Default bool   `json:"default"`
}

// EnumPrinters is a stub for non-Windows platforms.
// The agent is designed to run on Windows, but this allows building/testing on other OS.
func EnumPrinters() ([]PrinterInfo, error) {
	return []PrinterInfo{
		{Name: "Simulated-Thermal-Printer", Default: true},
	}, nil
}

// RawPrint is a stub for non-Windows platforms.
// On non-Windows, it logs the data that would have been sent.
func RawPrint(printerName string, data []byte) error {
	fmt.Printf("[STUB] Would print %d bytes to printer: %s\n", len(data), printerName)
	return nil
}
