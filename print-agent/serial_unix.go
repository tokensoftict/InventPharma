//go:build darwin || linux

package main

import (
	"fmt"
	"path/filepath"
	"sort"
	"strings"

	"golang.org/x/sys/unix"
)

type unixSerial struct{ fd int }

// Read returns 0 bytes (nil error) when the 100ms VTIME read timeout expires,
// matching the Windows implementation.
func (s *unixSerial) Read(p []byte) (int, error) {
	for {
		n, err := unix.Read(s.fd, p)
		if err == unix.EINTR {
			continue
		}
		if err != nil {
			return 0, err
		}
		return n, nil
	}
}

func (s *unixSerial) Write(p []byte) (int, error) {
	total := 0
	for total < len(p) {
		n, err := unix.Write(s.fd, p[total:])
		if err == unix.EINTR || err == unix.EAGAIN {
			continue
		}
		if err != nil {
			return total, err
		}
		total += n
	}
	return total, nil
}

func (s *unixSerial) Close() error { return unix.Close(s.fd) }

func openSerial(pc POSConfig) (serialPort, error) {
	fd, err := unix.Open(pc.Port, unix.O_RDWR|unix.O_NOCTTY|unix.O_NONBLOCK, 0)
	if err != nil {
		return nil, err
	}

	t, err := unix.IoctlGetTermios(fd, ioctlGetTermios)
	if err != nil {
		unix.Close(fd)
		return nil, fmt.Errorf("get termios: %v", err)
	}

	// Raw mode
	t.Iflag &^= unix.IGNBRK | unix.BRKINT | unix.PARMRK | unix.ISTRIP | unix.INLCR | unix.IGNCR | unix.ICRNL | unix.IXON | unix.IXOFF | unix.IXANY
	t.Oflag &^= unix.OPOST
	t.Lflag &^= unix.ECHO | unix.ECHONL | unix.ICANON | unix.ISIG | unix.IEXTEN
	t.Cflag &^= unix.CSIZE | unix.PARENB | unix.PARODD | unix.CSTOPB | unix.CRTSCTS
	t.Cflag |= unix.CREAD | unix.CLOCAL

	switch pc.DataBits {
	case 5:
		t.Cflag |= unix.CS5
	case 6:
		t.Cflag |= unix.CS6
	case 7:
		t.Cflag |= unix.CS7
	default:
		t.Cflag |= unix.CS8
	}
	switch strings.ToLower(pc.Parity) {
	case "odd":
		t.Cflag |= unix.PARENB | unix.PARODD
	case "even":
		t.Cflag |= unix.PARENB
	}
	if pc.StopBits == 2 {
		t.Cflag |= unix.CSTOPB
	}
	if err := setBaud(t, pc.BaudRate); err != nil {
		unix.Close(fd)
		return nil, err
	}

	// Blocking reads that time out after 100ms with 0 bytes.
	t.Cc[unix.VMIN] = 0
	t.Cc[unix.VTIME] = 1

	if err := unix.IoctlSetTermios(fd, ioctlSetTermios, t); err != nil {
		unix.Close(fd)
		return nil, fmt.Errorf("set termios: %v", err)
	}
	if fl, err := unix.FcntlInt(uintptr(fd), unix.F_GETFL, 0); err == nil {
		unix.FcntlInt(uintptr(fd), unix.F_SETFL, fl&^unix.O_NONBLOCK)
	}
	// Discard any stale bytes: read until a 100ms read times out empty.
	s := &unixSerial{fd: fd}
	junk := make([]byte, 256)
	for i := 0; i < 20; i++ {
		if n, err := s.Read(junk); err != nil || n == 0 {
			break
		}
	}
	return s, nil
}

func listSerialPorts() []string {
	ports := []string{}
	for _, pat := range []string{"/dev/cu.*", "/dev/ttyUSB*", "/dev/ttyACM*", "/dev/pts/*"} {
		m, _ := filepath.Glob(pat)
		for _, p := range m {
			if strings.Contains(p, "Bluetooth") {
				continue
			}
			ports = append(ports, p)
		}
	}
	sort.Strings(ports)
	return ports
}
