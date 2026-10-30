//go:build linux

package main

import (
	"fmt"

	"golang.org/x/sys/unix"
)

const (
	ioctlGetTermios = unix.TCGETS
	ioctlSetTermios = unix.TCSETS
)

func setBaud(t *unix.Termios, baud int) error {
	speeds := map[int]uint32{9600: unix.B9600, 19200: unix.B19200, 38400: unix.B38400, 57600: unix.B57600, 115200: unix.B115200, 230400: unix.B230400}
	b, ok := speeds[baud]
	if !ok {
		return fmt.Errorf("unsupported baud rate %d", baud)
	}
	t.Cflag &^= unix.CBAUD
	t.Cflag |= b
	t.Ispeed, t.Ospeed = b, b
	return nil
}
