//go:build darwin

package main

import "golang.org/x/sys/unix"

const (
	ioctlGetTermios = unix.TIOCGETA
	ioctlSetTermios = unix.TIOCSETA
)

// macOS accepts arbitrary numeric baud rates.
func setBaud(t *unix.Termios, baud int) error {
	t.Ispeed = uint64(baud)
	t.Ospeed = uint64(baud)
	return nil
}
