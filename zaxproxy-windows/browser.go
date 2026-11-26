package main

import (
	"encoding/json"
	"fmt"
	"log"
	"os"
	"os/exec"
	"path/filepath"
	"strings"
	"sync"
	"sync/atomic"
	"time"

	"golang.org/x/net/websocket"
)

// The ZaxProxy browser window.
//
// So that nobody has to install or load anything, the program opens Chrome or Edge itself, in a
// profile of its own, with the GPS already working in it. Two ways are used:
//
//  1. "addon" (preferred): the browser is started with the ZaxProxy GPS add-on already loaded.
//     The browser runs exactly as normal, so websites cannot tell it apart from any other.
//  2. "direct" (fallback, for browsers that refuse to load an add-on this way): the program tells
//     the browser the location through the browser's own control channel. The switch that would
//     make websites see an "automated" browser is turned off.
//
// The way that worked is remembered, so the window opens straight away next time.

const cdpOrigin = "http://zaxproxy.local" // the only caller the browser will accept on its control address

type cdpMsg struct {
	ID        int             `json:"id,omitempty"`
	Method    string          `json:"method,omitempty"`
	Params    json.RawMessage `json:"params,omitempty"`
	SessionID string          `json:"sessionId,omitempty"`
}

type browserLink struct {
	ws    *websocket.Conn
	wmu   sync.Mutex
	next  int
	pages map[string]bool // sessions of tabs and frames that can ask for a location
}

var (
	brMu        sync.Mutex
	br          *browserLink // set while the "direct" way is in use
	brName      string
	brBusy      bool
	gpsSeenAuto atomic.Int64 // when the add-on inside the ZaxProxy browser last asked (unix seconds)
)

func browserProfile() string  { return filepath.Join(filepath.Dir(configPath()), "Browser") }
func browserAddonDir() string { return filepath.Join(filepath.Dir(configPath()), "Browser add-on") }
func addonAlive() bool        { return time.Now().Unix()-gpsSeenAuto.Load() < 45 }

func (b *browserLink) send(session, method string, params any) {
	b.wmu.Lock()
	defer b.wmu.Unlock()
	b.next++
	m := map[string]any{"id": b.next, "method": method}
	if params != nil {
		m["params"] = params
	}
	if session != "" {
		m["sessionId"] = session
	}
	_ = websocket.JSON.Send(b.ws, m)
}

// place gives one tab the current GPS choice (or hands it back its real location).
func (b *browserLink) place(session string, g gpsState) {
	if g.On {
		b.send(session, "Emulation.setGeolocationOverride", map[string]any{"latitude": g.Lat, "longitude": g.Lng, "accuracy": 12})
	} else {
		b.send(session, "Emulation.clearGeolocationOverride", nil)
	}
}

// applyAll pushes the current GPS choice to every open tab.
func (b *browserLink) applyAll(g gpsState) {
	if g.On {
		b.send("", "Browser.grantPermissions", map[string]any{"permissions": []string{"geolocation"}})
	} else {
		b.send("", "Browser.resetPermissions", nil)
	}
	brMu.Lock()
	list := make([]string, 0, len(b.pages))
	for s := range b.pages {
		list = append(list, s)
	}
	brMu.Unlock()
	for _, s := range list {
		b.place(s, g)
	}
}

var autoAttach = map[string]any{"autoAttach": true, "waitForDebuggerOnStart": true, "flatten": true}

func (b *browserLink) listen() {
	for {
		var m cdpMsg
		if err := websocket.JSON.Receive(b.ws, &m); err != nil {
			break
		}
		switch m.Method {
		case "Target.attachedToTarget":
			var p struct {
				SessionID  string `json:"sessionId"`
				TargetInfo struct {
					Type string `json:"type"`
				} `json:"targetInfo"`
			}
			if json.Unmarshal(m.Params, &p) != nil || p.SessionID == "" {
				continue
			}
			if p.TargetInfo.Type == "page" || p.TargetInfo.Type == "iframe" {
				brMu.Lock()
				b.pages[p.SessionID] = true
				brMu.Unlock()
				a.mu.Lock()
				g := a.gps
				a.mu.Unlock()
				b.place(p.SessionID, g)                                 // before the page's own scripts run
				b.send(p.SessionID, "Target.setAutoAttach", autoAttach) // frames from other sites inside this tab
			}
			b.send(p.SessionID, "Runtime.runIfWaitingForDebugger", nil)
		case "Target.detachedFromTarget":
			var p struct {
				SessionID string `json:"sessionId"`
			}
			if json.Unmarshal(m.Params, &p) == nil {
				brMu.Lock()
				delete(b.pages, p.SessionID)
				brMu.Unlock()
			}
		}
	}
	// The browser window was closed.
	brMu.Lock()
	if br == b {
		br = nil
	}
	brMu.Unlock()
	log.Print("browser: closed")
}

// browserAttach connects to a ZaxProxy browser that is already open the "direct" way.
func browserAttach() bool {
	b, err := os.ReadFile(filepath.Join(browserProfile(), "DevToolsActivePort"))
	if err != nil {
		return false
	}
	lines := strings.Split(strings.TrimSpace(string(b)), "\n")
	if len(lines) < 2 {
		return false
	}
	cfg, err := websocket.NewConfig("ws://127.0.0.1:"+strings.TrimSpace(lines[0])+strings.TrimSpace(lines[1]), cdpOrigin)
	if err != nil {
		return false
	}
	ws, err := websocket.DialConfig(cfg)
	if err != nil {
		return false
	}
	ws.MaxPayloadBytes = 64 << 20
	link := &browserLink{ws: ws, pages: map[string]bool{}}
	brMu.Lock()
	br = link
	brMu.Unlock()
	go link.listen()
	link.send("", "Target.setAutoAttach", autoAttach)
	return true
}

func launch(path string, args []string) (*exec.Cmd, error) {
	args = append(args, "--no-first-run", "--no-default-browser-check")
	if extra := os.Getenv("ZAX_BROWSER_ARGS"); extra != "" {
		args = append(args, strings.Fields(extra)...)
	}
	cmd := exec.Command(path, args...)
	if err := cmd.Start(); err != nil {
		return nil, err
	}
	go func() { _ = cmd.Wait() }()
	return cmd, nil
}

func rememberMode(mode string) {
	c := loadConfig()
	if c.BrowserMode != mode {
		c.BrowserMode = mode
		writeConfig(c)
	}
}

// browserOpen makes sure the ZaxProxy browser window is open with the GPS working in it.
func browserOpen() error {
	brMu.Lock()
	if br != nil || brBusy {
		brMu.Unlock()
		return nil
	}
	brBusy = true
	brMu.Unlock()
	defer func() { brMu.Lock(); brBusy = false; brMu.Unlock() }()

	if addonAlive() || browserAttach() { // still open from earlier
		return nil
	}
	path, name := findBrowser()
	if path == "" {
		return fmt.Errorf("Chrome or Edge was not found on this computer.")
	}
	dir := browserProfile()
	_ = os.MkdirAll(dir, 0o700)
	_ = os.Remove(filepath.Join(dir, "DevToolsActivePort"))
	mode := envOr("ZAX_BROWSER_MODE", loadConfig().BrowserMode)

	// 1. With the add-on already loaded: an entirely ordinary browser window.
	if mode != "direct" {
		writeExtension()
		cmd, err := launch(path, []string{"--user-data-dir=" + dir, "--load-extension=" + browserAddonDir(), "--disable-features=DisableLoadExtensionCommandLineSwitch"})
		if err != nil {
			return fmt.Errorf("%s could not be opened.", name)
		}
		for i := 0; i < 48; i++ { // the add-on reports in as soon as the browser is up
			time.Sleep(250 * time.Millisecond)
			if addonAlive() {
				brMu.Lock()
				brName = name
				brMu.Unlock()
				rememberMode("addon")
				log.Printf("browser: opened %s with the add-on", name)
				return nil
			}
		}
		// This browser does not load add-ons this way: close that window and use the other way.
		log.Printf("browser: %s did not load the add-on; switching to the direct way", name)
		_ = cmd.Process.Kill()
		time.Sleep(1200 * time.Millisecond)
	}

	// 2. Directly. "AutomationControlled" off keeps websites from seeing an automated browser.
	_ = os.Remove(filepath.Join(dir, "DevToolsActivePort"))
	if _, err := launch(path, []string{"--user-data-dir=" + dir, "--remote-debugging-port=0", "--remote-allow-origins=" + cdpOrigin, "--disable-blink-features=AutomationControlled"}); err != nil {
		return fmt.Errorf("%s could not be opened.", name)
	}
	for i := 0; i < 80; i++ { // the browser writes its control address once it is up
		time.Sleep(250 * time.Millisecond)
		if browserAttach() {
			brMu.Lock()
			brName = name
			brMu.Unlock()
			rememberMode("direct")
			log.Printf("browser: opened %s the direct way", name)
			return nil
		}
	}
	return fmt.Errorf("%s opened, but ZaxProxy Connect could not reach it. Close that window and try again.", name)
}

// browserSync brings the browser in line with the GPS choice. With open=true the window is
// opened when it is not there yet.
func browserSync(open bool) {
	a.mu.Lock()
	g := a.gps
	a.mu.Unlock()
	if open && g.On {
		err := browserOpen()
		a.mu.Lock()
		if err != nil {
			a.gps.BrowserError = err.Error()
			log.Printf("browser: %v", err)
		} else {
			a.gps.BrowserError = ""
		}
		a.mu.Unlock()
	}
	brMu.Lock()
	link := br
	brMu.Unlock()
	if link != nil {
		link.applyAll(g) // with the add-on, the browser asks for the place itself
	}
}

func browserStatus() (open bool, name string, tabs int) {
	brMu.Lock()
	defer brMu.Unlock()
	if br != nil {
		return true, brName, len(br.pages)
	}
	return addonAlive(), brName, 0
}
