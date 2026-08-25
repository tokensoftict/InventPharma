//go:build windows
// +build windows

package main

import (
	"fmt"
	"syscall"
	"unsafe"
)

var (
	winspool          = syscall.NewLazyDLL("winspool.drv")
	procEnumPrintersW = winspool.NewProc("EnumPrintersW")
	procOpenPrinterW  = winspool.NewProc("OpenPrinterW")
	procClosePrinter  = winspool.NewProc("ClosePrinter")
	procStartDocPrinterW = winspool.NewProc("StartDocPrinterW")
	procEndDocPrinter = winspool.NewProc("EndDocPrinter")
	procStartPagePrinter = winspool.NewProc("StartPagePrinter")
	procEndPagePrinter = winspool.NewProc("EndPagePrinter")
	procWritePrinter  = winspool.NewProc("WritePrinter")
	procGetDefaultPrinterW = syscall.NewLazyDLL("kernel32.dll").NewProc("GetDefaultPrinterW")
)

const (
	PRINTER_ENUM_LOCAL      = 0x00000002
	PRINTER_ENUM_CONNECTIONS = 0x00000004
)

// PrinterInfo represents a discovered printer.
type PrinterInfo struct {
	Name    string `json:"name"`
	Default bool   `json:"default"`
}

// PRINTER_INFO_2W is the Windows PRINTER_INFO_2 struct (simplified).
type printerInfo2 struct {
	pServerName     *uint16
	pPrinterName    *uint16
	pShareName      *uint16
	pPortName       *uint16
	pDriverName     *uint16
	pComment        *uint16
	pLocation       *uint16
	pDevMode        uintptr
	pSepFile        *uint16
	pPrintProcessor *uint16
	pDatatype       *uint16
	pParameters     *uint16
	pSecurityDescriptor uintptr
	attributes      uint32
	priority        uint32
	defaultPriority uint32
	startTime       uint32
	untilTime       uint32
	status          uint32
	cJobs           uint32
	averagePPM      uint32
}

// EnumPrinters returns a list of installed printers.
func EnumPrinters() ([]PrinterInfo, error) {
	var needed, returned uint32
	flags := uint32(PRINTER_ENUM_LOCAL | PRINTER_ENUM_CONNECTIONS)

	// First call to get required buffer size
	procEnumPrintersW.Call(
		uintptr(flags),
		0, // pName - NULL for local
		2, // Level 2
		0, // pPrinterEnum - NULL
		0, // cbBuf
		uintptr(unsafe.Pointer(&needed)),
		uintptr(unsafe.Pointer(&returned)),
	)

	if needed == 0 {
		return []PrinterInfo{}, nil
	}

	buf := make([]byte, needed)

	ret, _, err := procEnumPrintersW.Call(
		uintptr(flags),
		0,
		2,
		uintptr(unsafe.Pointer(&buf[0])),
		uintptr(needed),
		uintptr(unsafe.Pointer(&needed)),
		uintptr(unsafe.Pointer(&returned)),
	)

	if ret == 0 {
		return nil, fmt.Errorf("EnumPrintersW failed: %v", err)
	}

	defaultPrinter := getDefaultPrinter()

	printers := make([]PrinterInfo, 0, returned)
	size := unsafe.Sizeof(printerInfo2{})

	for i := uint32(0); i < returned; i++ {
		info := (*printerInfo2)(unsafe.Pointer(&buf[uintptr(i)*size]))
		name := syscall.UTF16ToString((*[256]uint16)(unsafe.Pointer(info.pPrinterName))[:])

		printers = append(printers, PrinterInfo{
			Name:    name,
			Default: name == defaultPrinter,
		})
	}

	return printers, nil
}

// getDefaultPrinter returns the system default printer name.
func getDefaultPrinter() string {
	kernel32 := syscall.NewLazyDLL("kernel32.dll")
	getDefault := kernel32.NewProc("GetDefaultPrinterW")

	var size uint32 = 256
	buf := make([]uint16, size)

	ret, _, _ := getDefault.Call(
		uintptr(unsafe.Pointer(&buf[0])),
		uintptr(unsafe.Pointer(&size)),
	)

	if ret == 0 {
		return ""
	}

	return syscall.UTF16ToString(buf)
}

// DOC_INFO_1W for StartDocPrinter
type docInfo1 struct {
	pDocName    *uint16
	pOutputFile *uint16
	pDatatype   *uint16
}

// RawPrint sends raw bytes directly to a printer.
func RawPrint(printerName string, data []byte) error {
	pName, err := syscall.UTF16PtrFromString(printerName)
	if err != nil {
		return fmt.Errorf("invalid printer name: %v", err)
	}

	var handle uintptr
	ret, _, callErr := procOpenPrinterW.Call(
		uintptr(unsafe.Pointer(pName)),
		uintptr(unsafe.Pointer(&handle)),
		0, // pDefault - NULL
	)
	if ret == 0 {
		return fmt.Errorf("OpenPrinter failed for '%s': %v", printerName, callErr)
	}
	defer procClosePrinter.Call(handle)

	docName, _ := syscall.UTF16PtrFromString("ESC/POS Receipt")
	datatype, _ := syscall.UTF16PtrFromString("RAW")

	di := docInfo1{
		pDocName:    docName,
		pOutputFile: nil,
		pDatatype:   datatype,
	}

	ret, _, callErr = procStartDocPrinterW.Call(
		handle,
		1, // Level
		uintptr(unsafe.Pointer(&di)),
	)
	if ret == 0 {
		return fmt.Errorf("StartDocPrinter failed: %v", callErr)
	}
	defer procEndDocPrinter.Call(handle)

	ret, _, callErr = procStartPagePrinter.Call(handle)
	if ret == 0 {
		return fmt.Errorf("StartPagePrinter failed: %v", callErr)
	}
	defer procEndPagePrinter.Call(handle)

	var written uint32
	ret, _, callErr = procWritePrinter.Call(
		handle,
		uintptr(unsafe.Pointer(&data[0])),
		uintptr(len(data)),
		uintptr(unsafe.Pointer(&written)),
	)
	if ret == 0 {
		return fmt.Errorf("WritePrinter failed: %v", callErr)
	}

	if int(written) != len(data) {
		return fmt.Errorf("WritePrinter: wrote %d of %d bytes", written, len(data))
	}

	return nil
}
