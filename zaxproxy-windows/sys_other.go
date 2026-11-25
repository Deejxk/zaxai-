//go:build !windows

package main

// Stand-ins so the program can be built and tested on other systems. The real behaviour
// (Windows proxy setting, tray icon, protected storage) lives in sys_windows.go.

import (
	"log"
	"os"
	"os/signal"
)

func setSystemProxy(addr string) error       { log.Println("[test] system proxy ->", addr); return nil }
func restoreSystemProxy()                    { log.Println("[test] system proxy restored") }
func recoverSystemProxy(c config)            {}
func openBrowser(url string) error           { log.Println("[test] open", url); return nil }
func alert(title, text string, isError bool) { log.Println("[test] message:", title) }
func protect(s string) (string, error)       { return s, nil }
func unprotect(s string) (string, error) {
	return s, nil
}
func trayStatus(on bool, label string) {}
func focusWindow()                     { log.Println("[test] window to front") }
func runWindow(url string) {
	log.Println("[test] open", url)
	ch := make(chan os.Signal, 1)
	signal.Notify(ch, os.Interrupt)
	<-ch
}
