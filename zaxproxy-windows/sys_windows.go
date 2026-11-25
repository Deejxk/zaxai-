//go:build windows

package main

import (
	_ "embed"
	"encoding/base64"
	"os/exec"
	"syscall"
	"unsafe"

	"fyne.io/systray"
	"golang.org/x/sys/windows"
	"golang.org/x/sys/windows/registry"
)

//go:embed icon.ico
var iconICO []byte

const inetKey = `Software\Microsoft\Windows\CurrentVersion\Internet Settings`

var (
	wininet           = windows.NewLazySystemDLL("wininet.dll")
	internetSetOption = wininet.NewProc("InternetSetOptionW")
)

// Tell running programs that the proxy setting changed, so they pick it up without a restart.
func notifyProxyChange() {
	const settingsChanged, refresh = 39, 37
	internetSetOption.Call(0, settingsChanged, 0, 0)
	internetSetOption.Call(0, refresh, 0, 0)
}

func readProxy() (on uint32, server, skip string) {
	k, err := registry.OpenKey(registry.CURRENT_USER, inetKey, registry.QUERY_VALUE)
	if err != nil {
		return
	}
	defer k.Close()
	if v, _, e := k.GetIntegerValue("ProxyEnable"); e == nil {
		on = uint32(v)
	}
	server, _, _ = k.GetStringValue("ProxyServer")
	skip, _, _ = k.GetStringValue("ProxyOverride")
	return
}

func writeProxy(on uint32, server, skip string) error {
	k, _, err := registry.CreateKey(registry.CURRENT_USER, inetKey, registry.SET_VALUE)
	if err != nil {
		return err
	}
	defer k.Close()
	if err := k.SetDWordValue("ProxyEnable", on); err != nil {
		return err
	}
	if server == "" {
		_ = k.DeleteValue("ProxyServer")
	} else if err := k.SetStringValue("ProxyServer", server); err != nil {
		return err
	}
	if skip == "" {
		_ = k.DeleteValue("ProxyOverride")
	} else {
		_ = k.SetStringValue("ProxyOverride", skip)
	}
	notifyProxyChange()
	return nil
}

// setSystemProxy remembers the current Windows setting (on disk, so it survives a crash) and
// then points Windows at the local relay.
func setSystemProxy(addr string) error {
	c := loadConfig()
	if !c.Changed {
		c.PrevOn, c.PrevProxy, c.PrevSkip = readProxy()
		if c.PrevProxy == addr { // never remember our own relay as "the previous setting"
			c.PrevOn, c.PrevProxy = 0, ""
		}
		c.Changed = true
		writeConfig(c)
	}
	return writeProxy(1, addr, "localhost;127.*;10.*;172.16.*;192.168.*;<local>")
}

func restoreSystemProxy() {
	c := loadConfig()
	if !c.Changed {
		return
	}
	_ = writeProxy(c.PrevOn, c.PrevProxy, c.PrevSkip)
	c.Changed, c.PrevOn, c.PrevProxy, c.PrevSkip = false, 0, "", ""
	writeConfig(c)
}

// recoverSystemProxy runs at start-up: if the last run ended without cleaning up, undo it now.
func recoverSystemProxy(c config) {
	if c.Changed {
		restoreSystemProxy()
		return
	}
	if on, server, _ := readProxy(); on == 1 && server == relayAddr {
		_ = writeProxy(0, "", "")
	}
}

// openBrowser asks Windows to open the address with the default browser, trying three ways.
func openBrowser(url string) error {
	verb, _ := windows.UTF16PtrFromString("open")
	target, _ := windows.UTF16PtrFromString(url)
	err := windows.ShellExecute(0, verb, target, nil, nil, windows.SW_SHOWNORMAL)
	if err == nil {
		return nil
	}
	cmd := exec.Command("rundll32", "url.dll,FileProtocolHandler", url)
	cmd.SysProcAttr = &syscall.SysProcAttr{HideWindow: true}
	if e := cmd.Start(); e == nil {
		return nil
	}
	cmd = exec.Command("cmd", "/c", "start", "", url)
	cmd.SysProcAttr = &syscall.SysProcAttr{HideWindow: true}
	if e := cmd.Start(); e == nil {
		return nil
	}
	return err
}

// alert shows an ordinary Windows message box, always on top so it cannot hide behind other windows.
func alert(title, text string, isError bool) {
	t, _ := windows.UTF16PtrFromString(title)
	m, _ := windows.UTF16PtrFromString(text)
	flags := uint32(windows.MB_OK | windows.MB_SETFOREGROUND | windows.MB_TOPMOST)
	if isError {
		flags |= windows.MB_ICONERROR
	} else {
		flags |= windows.MB_ICONINFORMATION
	}
	_, _ = windows.MessageBox(0, m, t, flags)
}

/* ---------- Keep the sign-in token readable by this Windows account only (DPAPI) ---------- */

func protect(plain string) (string, error) {
	in := windows.DataBlob{Size: uint32(len(plain)), Data: &[]byte(plain)[0]}
	var out windows.DataBlob
	if err := windows.CryptProtectData(&in, nil, nil, 0, nil, 0, &out); err != nil {
		return "", err
	}
	defer windows.LocalFree(windows.Handle(unsafe.Pointer(out.Data)))
	return base64.StdEncoding.EncodeToString(unsafe.Slice(out.Data, out.Size)), nil
}

func unprotect(text string) (string, error) {
	raw, err := base64.StdEncoding.DecodeString(text)
	if err != nil || len(raw) == 0 {
		return "", err
	}
	in := windows.DataBlob{Size: uint32(len(raw)), Data: &raw[0]}
	var out windows.DataBlob
	if err := windows.CryptUnprotectData(&in, nil, nil, 0, nil, 0, &out); err != nil {
		return "", err
	}
	defer windows.LocalFree(windows.Handle(unsafe.Pointer(out.Data)))
	return string(unsafe.Slice(out.Data, out.Size)), nil
}

/* ---------- Tray icon ---------- */

var (
	trayState *systray.MenuItem
	trayStop  *systray.MenuItem
)

func runTray() {
	systray.Run(func() {
		systray.SetIcon(iconICO)
		systray.SetTitle("ZaxProxy")
		systray.SetTooltip("ZaxProxy: not connected")
		open := systray.AddMenuItem("Open ZaxProxy", "Show the ZaxProxy window")
		trayState = systray.AddMenuItem("Not connected", "")
		trayState.Disable()
		trayStop = systray.AddMenuItem("Disconnect", "Stop using the proxy")
		trayStop.Disable()
		systray.AddSeparator()
		exit := systray.AddMenuItem("Quit", "Disconnect and close ZaxProxy")
		go func() {
			for {
				select {
				case <-open.ClickedCh:
					openUI()
				case <-trayStop.ClickedCh:
					a.disconnect()
				case <-exit.ClickedCh:
					systray.Quit()
					return
				}
			}
		}()
	}, func() {})
}

func trayStatus(on bool, label string) {
	if trayState == nil {
		return
	}
	if on {
		trayState.SetTitle("Connected: " + label)
		trayStop.Enable()
		systray.SetTooltip("ZaxProxy: connected, " + label)
	} else {
		trayState.SetTitle("Not connected")
		trayStop.Disable()
		systray.SetTooltip("ZaxProxy: not connected")
	}
}
