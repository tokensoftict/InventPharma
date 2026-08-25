//go:build windows
// +build windows

package main

import (
	"log"

	"golang.org/x/sys/windows/svc"
)

type inventoryService struct{}

func (s *inventoryService) Execute(args []string, r <-chan svc.ChangeRequest, changes chan<- svc.Status) (ssec bool, errno uint32) {
	const cmdsAccepted = svc.AcceptStop | svc.AcceptShutdown

	changes <- svc.Status{State: svc.StartPending}

	// Start HTTP server in a goroutine
	go startHTTPServer()

	changes <- svc.Status{State: svc.Running, Accepts: cmdsAccepted}

	for {
		c := <-r
		switch c.Cmd {
		case svc.Interrogate:
			changes <- c.CurrentStatus
		case svc.Stop, svc.Shutdown:
			changes <- svc.Status{State: svc.StopPending}
			return
		default:
			log.Printf("Unexpected service control request: %d", c.Cmd)
		}
	}
}

// runAsService attempts to run as a Windows service.
// Returns true if it ran as a service, false if not (e.g., running from console).
func runAsService() bool {
	isService, err := svc.IsWindowsService()
	if err != nil {
		log.Printf("Failed to check if running as service: %v", err)
		return false
	}

	if !isService {
		return false
	}

	err = svc.Run(cfg.ServiceName, &inventoryService{})
	if err != nil {
		log.Printf("Service failed: %v", err)
		return false
	}

	return true
}
