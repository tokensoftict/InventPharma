//go:build windows

package main

import (
	"fmt"
	"sort"
	"strings"
	"syscall"
	"unsafe"

	"golang.org/x/sys/windows/registry"
)

var (
	kernel32            = syscall.NewLazyDLL("kernel32.dll")
	procGetCommState    = kernel32.NewProc("GetCommState")
	procSetCommState    = kernel32.NewProc("SetCommState")
	procSetCommTimeouts = kernel32.NewProc("SetCommTimeouts")
	procPurgeComm       = kernel32.NewProc("PurgeComm")
)

type dcb struct {
	DCBlength  uint32
	BaudRate   uint32
	Flags      uint32
	wReserved  uint16
	XonLim     uint16
	XoffLim    uint16
	ByteSize   uint8
	Parity     uint8
	StopBits   uint8
	XonChar    int8
	XoffChar   int8
	ErrorChar  int8
	EofChar    int8
	EvtChar    int8
	wReserved1 uint16
}

type commTimeouts struct {
	ReadIntervalTimeout         uint32
	ReadTotalTimeoutMultiplier  uint32
	ReadTotalTimeoutConstant    uint32
	WriteTotalTimeoutMultiplier uint32
	WriteTotalTimeoutConstant   uint32
}

type winSerial struct{ h syscall.Handle }

func (s *winSerial) Read(p []byte) (int, error) {
	var n uint32
	if err := syscall.ReadFile(s.h, p, &n, nil); err != nil {
		return 0, err
	}
	return int(n), nil // 0 bytes = read timeout (100ms), caller loops
}

func (s *winSerial) Write(p []byte) (int, error) {
	var n uint32
	if err := syscall.WriteFile(s.h, p, &n, nil); err != nil {
		return 0, err
	}
	return int(n), nil
}

func (s *winSerial) Close() error { return syscall.CloseHandle(s.h) }

func openSerial(pc POSConfig) (serialPort, error) {
	name := pc.Port
	if !strings.HasPrefix(name, `\\.\`) {
		name = `\\.\` + name
	}
	p, err := syscall.UTF16PtrFromString(name)
	if err != nil {
		return nil, err
	}
	h, err := syscall.CreateFile(p, syscall.GENERIC_READ|syscall.GENERIC_WRITE, 0, nil, syscall.OPEN_EXISTING, syscall.FILE_ATTRIBUTE_NORMAL, 0)
	if err != nil {
		return nil, err
	}

	var d dcb
	d.DCBlength = uint32(unsafe.Sizeof(d))
	if r, _, e := procGetCommState.Call(uintptr(h), uintptr(unsafe.Pointer(&d))); r == 0 {
		syscall.CloseHandle(h)
		return nil, fmt.Errorf("GetCommState: %v", e)
	}
	d.BaudRate = uint32(pc.BaudRate)
	d.ByteSize = uint8(pc.DataBits)
	switch strings.ToLower(pc.Parity) {
	case "odd":
		d.Parity = 1
	case "even":
		d.Parity = 2
	default:
		d.Parity = 0
	}
	if pc.StopBits == 2 {
		d.StopBits = 2
	} else {
		d.StopBits = 0 // ONESTOPBIT
	}
	// fBinary | fDtrControl=ENABLE | fRtsControl=ENABLE, no software/hardware flow control
	d.Flags = 0x0001 | 0x0010 | 0x1000
	if r, _, e := procSetCommState.Call(uintptr(h), uintptr(unsafe.Pointer(&d))); r == 0 {
		syscall.CloseHandle(h)
		return nil, fmt.Errorf("SetCommState: %v", e)
	}

	// Reads return after 100ms if no data is available.
	t := commTimeouts{ReadIntervalTimeout: 0xFFFFFFFF, ReadTotalTimeoutMultiplier: 0xFFFFFFFF, ReadTotalTimeoutConstant: 100, WriteTotalTimeoutConstant: 2000}
	if r, _, e := procSetCommTimeouts.Call(uintptr(h), uintptr(unsafe.Pointer(&t))); r == 0 {
		syscall.CloseHandle(h)
		return nil, fmt.Errorf("SetCommTimeouts: %v", e)
	}
	procPurgeComm.Call(uintptr(h), 0x0004|0x0008) // purge RX and TX
	return &winSerial{h: h}, nil
}

func listSerialPorts() []string {
	ports := []string{}
	k, err := registry.OpenKey(registry.LOCAL_MACHINE, `HARDWARE\DEVICEMAP\SERIALCOMM`, registry.QUERY_VALUE)
	if err != nil {
		return ports
	}
	defer k.Close()
	names, _ := k.ReadValueNames(0)
	for _, n := range names {
		if v, _, err := k.GetStringValue(n); err == nil {
			ports = append(ports, v)
		}
	}
	sort.Strings(ports)
	return ports
}

