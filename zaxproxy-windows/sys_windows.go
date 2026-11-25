//go:build windows

package main

import (
	"encoding/base64"
	"log"
	"os"
	"os/exec"
	"path/filepath"
	"runtime"
	"syscall"
	"unsafe"

	"github.com/jchv/go-webview2"
	"golang.org/x/sys/windows"
	"golang.org/x/sys/windows/registry"
)

// The window must be created and run on the program's first thread.
func init() { runtime.LockOSThread() }

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
	}
	c.Relay = addr
	writeConfig(c)
	return writeProxy(1, addr, "localhost;127.*;10.*;172.16.*;192.168.*;<local>")
}

func restoreSystemProxy() {
	c := loadConfig()
	if !c.Changed {
		return
	}
	_ = writeProxy(c.PrevOn, c.PrevProxy, c.PrevSkip)
	c.Changed, c.PrevOn, c.PrevProxy, c.PrevSkip, c.Relay = false, 0, "", "", ""
	writeConfig(c)
}

// recoverSystemProxy runs at start-up: if the last run ended without cleaning up, undo it now.
func recoverSystemProxy(c config) {
	if c.Changed {
		restoreSystemProxy()
		return
	}
	// Windows still points at a relay of ours that is no longer there (also the fixed address old versions used).
	if on, server, _ := readProxy(); on == 1 && (server == "127.0.0.1:47822" || (c.Relay != "" && server == c.Relay)) {
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

/* ---------- The app window ---------- */

var (
	win      webview2.WebView
	user32   = windows.NewLazySystemDLL("user32.dll")
	setFore  = user32.NewProc("SetForegroundWindow")
	showWin  = user32.NewProc("ShowWindow")
	isIconic = user32.NewProc("IsIconic")
)

// runWindow opens the ZaxProxy Connect window and returns when the user closes it.
// The window shows the program's own screen (served from this computer only) using the
// WebView2 component that ships with Windows 10 and 11.
func runWindow(url string) {
	dir := filepath.Join(filepath.Dir(configPath()), "window")
	_ = os.MkdirAll(dir, 0o700)
	win = webview2.NewWithOptions(webview2.WebViewOptions{
		AutoFocus: true,
		DataPath:  dir,
		WindowOptions: webview2.WindowOptions{
			Title:  "ZaxProxy Connect",
			Width:  1080,
			Height: 760,
			IconId: 1, // the icon built into the program file
			Center: true,
		},
	})
	if win == nil {
		// Very old Windows without the WebView2 component: fall back to the browser so the app is still usable.
		log.Print("the app window could not be created (WebView2 missing); using the browser instead")
		alert("ZaxProxy Connect", "This computer is missing a Windows component (Microsoft Edge WebView2) that ZaxProxy Connect uses for its window.\n\nZaxProxy Connect will open in your web browser instead. Leave this message open while you use it: clicking OK closes ZaxProxy Connect and disconnects.", false)
		_ = openBrowser(url)
		alert("ZaxProxy Connect is running", "ZaxProxy Connect is open in your web browser.\n\nClick OK here when you want to close it and disconnect.", false)
		return
	}
	defer win.Destroy()
	win.SetSize(1080, 760, webview2.HintNone)
	win.SetSize(820, 600, webview2.HintMin)
	win.Navigate(url)
	win.Run()
}

// focusWindow brings the window to the front (a second launch asks for this instead of opening twice).
func focusWindow() {
	if win == nil {
		return
	}
	win.Dispatch(func() {
		h := uintptr(win.Window())
		if r, _, _ := isIconic.Call(h); r != 0 {
			showWin.Call(h, 9) // restore from the taskbar
		}
		setFore.Call(h)
	})
}

func trayStatus(on bool, label string) {
	if win == nil {
		return
	}
	title := "ZaxProxy Connect"
	if on {
		title = "ZaxProxy Connect · Connected · " + label
	}
	win.Dispatch(func() { win.SetTitle(title) })
}
