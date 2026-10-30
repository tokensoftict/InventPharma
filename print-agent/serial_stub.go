//go:build !windows && !darwin && !linux

package main

import "fmt"

// Serial access is implemented for Windows, macOS and Linux only.
func openSerial(pc POSConfig) (serialPort, error) {
	return nil, fmt.Errorf("serial ports are not supported on this OS")
}

func listSerialPorts() []string { return []string{} }
