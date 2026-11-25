// ZaxProxy for Windows.
//
// Sign in with a ZaxProxy account, pick a proxy, click Connect.
//
// How it works: the program runs a small local relay on this computer (127.0.0.1) and points the
// Windows proxy setting at it. The relay passes every connection on to the chosen proxy over
// SOCKS5, signing in with the proxy's username and password, so browsers never ask for them.
// Disconnecting (or quitting) puts the Windows proxy setting back exactly as it was.
//
// The window is a page in your browser served from this computer only; a tray icon keeps the
// program reachable. The account password is used once to sign in and is never stored.
package main

import (
	"crypto/rand"
	"embed"
	"encoding/hex"
	"encoding/json"
	"fmt"
	"io"
	"log"
	"net"
	"net/http"
	"os"
	"path/filepath"
	"runtime/debug"
	"strings"
	"sync"
	"time"
)

const (
	uiAddr    = "127.0.0.1:47821"
	relayAddr = "127.0.0.1:47822"
	version   = "1.0.1"
)

//go:embed ui.html
var uiFS embed.FS

// Proxy is one of the customer's proxies, as sent by the website.
type Proxy struct {
	ID       int64  `json:"id"`
	Type     string `json:"type"`
	Country  string `json:"country"`
	Flag     string `json:"flag"`
	IP       string `json:"ip"`
	Port     int    `json:"port"`
	Username string `json:"username"`
	Password string `json:"password"`
	Left     string `json:"left"`
	State    string `json:"state"`
}

type app struct {
	mu        sync.Mutex
	key       string // one-time secret the page must present
	token     string
	name      string
	proxies   []Proxy
	connected int64
	relay     *relay
	site      string
}

var a = &app{}

func main() {
	setupLog()
	// Nothing fails silently: any unexpected error is written to the log and shown in a message box.
	defer func() {
		if r := recover(); r != nil {
			log.Printf("crash: %v\n%s", r, debug.Stack())
			alert("ZaxProxy had a problem", fmt.Sprintf("ZaxProxy could not start.\n\nDetails: %v\n\nA log was saved here:\n%s\n\nPlease send that file to support.", r, logPath()), true)
			os.Exit(1)
		}
	}()
	log.Printf("ZaxProxy %s starting", version)
	a.site = strings.TrimRight(envOr("ZAX_SITE", "https://zaxproxy.cloud"), "/")

	// Already running? Ask that copy to show its window and leave.
	ln, err := net.Listen("tcp", uiAddr)
	if err != nil {
		c := http.Client{Timeout: 3 * time.Second}
		r, e := c.Get("http://" + uiAddr + "/open")
		if e == nil && r.StatusCode == 204 {
			r.Body.Close()
			log.Print("already running: asked the first copy to show its window")
			return
		}
		log.Printf("cannot listen on %s: %v", uiAddr, err)
		alert("ZaxProxy cannot start", "Another program on this computer is using the address ZaxProxy needs ("+uiAddr+").\n\nClose that program, or restart your computer, and open ZaxProxy again.", true)
		return
	}

	buf := make([]byte, 24)
	if _, err := rand.Read(buf); err != nil {
		log.Fatal(err)
	}
	a.key = hex.EncodeToString(buf)

	cfg := loadConfig()
	a.token = cfg.Token
	a.name = cfg.Name
	// A previous run that ended badly may have left Windows pointing at our relay: put it back.
	recoverSystemProxy(cfg)

	mux := http.NewServeMux()
	mux.HandleFunc("/", handleUI)
	mux.HandleFunc("/open", func(w http.ResponseWriter, r *http.Request) { openUI(); w.WriteHeader(204) })
	mux.HandleFunc("/api/state", guard(handleState))
	mux.HandleFunc("/api/login", guard(handleLogin))
	mux.HandleFunc("/api/logout", guard(handleLogout))
	mux.HandleFunc("/api/refresh", guard(handleRefresh))
	mux.HandleFunc("/api/connect", guard(handleConnect))
	mux.HandleFunc("/api/disconnect", guard(handleDisconnect))
	mux.HandleFunc("/api/quit", guard(func(w http.ResponseWriter, r *http.Request) {
		writeJSON(w, 200, map[string]any{"ok": true})
		go func() { time.Sleep(300 * time.Millisecond); quit() }()
	}))
	go func() { _ = http.Serve(ln, mux) }()

	if a.token != "" {
		go func() { _ = a.refresh() }()
	}
	log.Printf("window address: http://%s", uiAddr)
	if os.Getenv("ZAX_NO_BROWSER") == "" {
		go func() {
			// A plain message first, so it is obvious the program is running and where to find it.
			alert("ZaxProxy is running", "ZaxProxy is now running on this computer.\n\nWhen you click OK, the ZaxProxy window opens in your web browser. Sign in there and click Connect on a proxy.\n\nYou can reopen it any time from the shield icon near the clock (click the small ^ arrow if you do not see it).", false)
			openUI()
		}()
	}
	runTray() // blocks until Quit
	quit()
}

func envOr(k, d string) string {
	if v := os.Getenv(k); v != "" {
		return v
	}
	return d
}

func uiURL() string { return "http://" + uiAddr + "/#k=" + a.key }

func openUI() {
	if err := openBrowser(uiURL()); err != nil {
		log.Printf("could not open the browser: %v", err)
		alert("Open ZaxProxy in your browser", "ZaxProxy could not open your web browser by itself.\n\nOpen your browser and type this address:\n\n"+uiURL(), false)
	}
}

func logPath() string { return filepath.Join(filepath.Dir(configPath()), "log.txt") }

func setupLog() {
	f, err := os.OpenFile(logPath(), os.O_CREATE|os.O_WRONLY|os.O_TRUNC, 0o600)
	if err == nil {
		log.SetOutput(io.MultiWriter(f, os.Stderr))
	}
}

func quit() {
	a.disconnect()
	os.Exit(0)
}

/* ---------- Local page ---------- */

func handleUI(w http.ResponseWriter, r *http.Request) {
	if r.URL.Path != "/" || !localHost(r) {
		http.NotFound(w, r)
		return
	}
	b, _ := uiFS.ReadFile("ui.html")
	w.Header().Set("Content-Type", "text/html; charset=utf-8")
	w.Header().Set("Cache-Control", "no-store")
	w.Header().Set("Content-Security-Policy", "default-src 'self' 'unsafe-inline'; img-src 'self' data:; connect-src 'self'; frame-ancestors 'none'")
	w.Header().Set("X-Frame-Options", "DENY")
	w.Write(b)
}

func localHost(r *http.Request) bool { return r.Host == uiAddr || r.Host == "localhost:47821" }

// guard: only this computer's own page, holding the secret, may give orders.
func guard(h http.HandlerFunc) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		if !localHost(r) || r.Header.Get("X-Key") != a.key {
			writeJSON(w, 403, map[string]any{"ok": false, "error": "Please reopen ZaxProxy from the tray icon."})
			return
		}
		if o := r.Header.Get("Origin"); o != "" && o != "http://"+uiAddr {
			writeJSON(w, 403, map[string]any{"ok": false, "error": "Not allowed."})
			return
		}
		h(w, r)
	}
}

func writeJSON(w http.ResponseWriter, status int, v any) {
	w.Header().Set("Content-Type", "application/json; charset=utf-8")
	w.Header().Set("Cache-Control", "no-store")
	w.WriteHeader(status)
	_ = json.NewEncoder(w).Encode(v)
}

func readBody(r *http.Request, v any) error {
	b, err := io.ReadAll(io.LimitReader(r.Body, 1<<16))
	if err != nil {
		return err
	}
	return json.Unmarshal(b, v)
}

type publicProxy struct {
	ID      int64  `json:"id"`
	Title   string `json:"title"`
	Flag    string `json:"flag"`
	Address string `json:"address"`
	Left    string `json:"left"`
	State   string `json:"state"`
}

func (x *app) snapshot() map[string]any {
	x.mu.Lock()
	defer x.mu.Unlock()
	list := make([]publicProxy, 0, len(x.proxies))
	for _, p := range x.proxies { // passwords stay inside the program; the page never receives them
		list = append(list, publicProxy{p.ID, p.Country + " · " + p.Type, p.Flag, fmt.Sprintf("%s:%d", p.IP, p.Port), p.Left, p.State})
	}
	return map[string]any{"ok": true, "signedIn": x.token != "", "name": x.name, "proxies": list, "connected": x.connected, "version": version}
}

func handleState(w http.ResponseWriter, r *http.Request) { writeJSON(w, 200, a.snapshot()) }

func handleLogin(w http.ResponseWriter, r *http.Request) {
	var in struct{ Login, Password string }
	if readBody(r, &in) != nil || in.Login == "" || in.Password == "" {
		writeJSON(w, 400, map[string]any{"ok": false, "error": "Enter your email and password."})
		return
	}
	host, _ := os.Hostname()
	res, err := apiLogin(a.site, in.Login, in.Password, "Windows · "+host)
	if err != nil {
		writeJSON(w, 502, map[string]any{"ok": false, "error": "Could not reach ZaxProxy. Check your internet connection."})
		return
	}
	if !res.OK {
		writeJSON(w, 401, map[string]any{"ok": false, "error": orText(res.Error, "Incorrect email or password.")})
		return
	}
	a.mu.Lock()
	a.token, a.name, a.proxies = res.Token, res.User.Name, res.Proxies
	a.mu.Unlock()
	a.save()
	writeJSON(w, 200, a.snapshot())
}

func handleLogout(w http.ResponseWriter, r *http.Request) {
	a.disconnect()
	a.mu.Lock()
	t := a.token
	a.token, a.name, a.proxies = "", "", nil
	a.mu.Unlock()
	a.save()
	if t != "" {
		go apiLogout(a.site, t)
	}
	writeJSON(w, 200, a.snapshot())
}

func handleRefresh(w http.ResponseWriter, r *http.Request) {
	if err := a.refresh(); err != nil {
		writeJSON(w, 502, map[string]any{"ok": false, "error": err.Error()})
		return
	}
	writeJSON(w, 200, a.snapshot())
}

func (x *app) refresh() error {
	x.mu.Lock()
	t := x.token
	x.mu.Unlock()
	if t == "" {
		return nil
	}
	res, status, err := apiProxies(x.site, t)
	if err != nil {
		return fmt.Errorf("Could not reach ZaxProxy. Check your internet connection.")
	}
	if status == 401 {
		x.disconnect()
		x.mu.Lock()
		x.token, x.name, x.proxies = "", "", nil
		x.mu.Unlock()
		x.save()
		return nil
	}
	if !res.OK {
		return fmt.Errorf("%s", orText(res.Error, "Could not load your proxies."))
	}
	x.mu.Lock()
	x.proxies, x.name = res.Proxies, res.User.Name
	x.mu.Unlock()
	x.save()
	return nil
}

func handleConnect(w http.ResponseWriter, r *http.Request) {
	var in struct {
		ID int64 `json:"id"`
	}
	if readBody(r, &in) != nil {
		writeJSON(w, 400, map[string]any{"ok": false, "error": "Choose a proxy."})
		return
	}
	a.mu.Lock()
	var chosen *Proxy
	for i := range a.proxies {
		if a.proxies[i].ID == in.ID {
			p := a.proxies[i]
			chosen = &p
		}
	}
	a.mu.Unlock()
	if chosen == nil {
		writeJSON(w, 404, map[string]any{"ok": false, "error": "That proxy is no longer in your list. Click Refresh."})
		return
	}
	a.disconnect()
	rl, err := startRelay(relayAddr, *chosen)
	if err != nil {
		writeJSON(w, 502, map[string]any{"ok": false, "error": err.Error()})
		return
	}
	if err := setSystemProxy(relayAddr); err != nil {
		rl.stop()
		writeJSON(w, 500, map[string]any{"ok": false, "error": "Windows did not accept the proxy setting: " + err.Error()})
		return
	}
	a.mu.Lock()
	a.relay, a.connected = rl, chosen.ID
	a.mu.Unlock()
	trayStatus(true, chosen.Country+" · "+chosen.IP)
	writeJSON(w, 200, a.snapshot())
}

func handleDisconnect(w http.ResponseWriter, r *http.Request) {
	a.disconnect()
	writeJSON(w, 200, a.snapshot())
}

func (x *app) disconnect() {
	x.mu.Lock()
	rl := x.relay
	was := x.connected
	x.relay, x.connected = nil, 0
	x.mu.Unlock()
	if was != 0 || rl != nil {
		restoreSystemProxy()
	}
	if rl != nil {
		rl.stop()
	}
	trayStatus(false, "")
}

func orText(s, d string) string {
	if s == "" {
		return d
	}
	return s
}

/* ---------- Saved settings ---------- */

type config struct {
	Token string `json:"token,omitempty"` // protected with the Windows account (DPAPI) before it is written
	Name  string `json:"name,omitempty"`
	// What the Windows proxy setting was before we changed it, so it can be put back even after a crash.
	Changed   bool   `json:"changed,omitempty"`
	PrevOn    uint32 `json:"prev_on,omitempty"`
	PrevProxy string `json:"prev_proxy,omitempty"`
	PrevSkip  string `json:"prev_skip,omitempty"`
}

func configPath() string {
	dir, err := os.UserConfigDir()
	if err != nil {
		dir = "."
	}
	dir = filepath.Join(dir, "ZaxProxy")
	_ = os.MkdirAll(dir, 0o700)
	return filepath.Join(dir, "config.json")
}

func loadConfig() config {
	var c config
	b, err := os.ReadFile(configPath())
	if err != nil {
		return c
	}
	_ = json.Unmarshal(b, &c)
	if c.Token != "" {
		if t, err := unprotect(c.Token); err == nil {
			c.Token = t
		} else {
			c.Token = ""
		}
	}
	return c
}

func writeConfig(c config) {
	if c.Token != "" {
		if t, err := protect(c.Token); err == nil {
			c.Token = t
		} else {
			c.Token = ""
		}
	}
	b, _ := json.MarshalIndent(c, "", "  ")
	_ = os.WriteFile(configPath(), b, 0o600)
}

func (x *app) save() {
	c := loadConfig()
	x.mu.Lock()
	c.Token, c.Name = x.token, x.name
	x.mu.Unlock()
	writeConfig(c)
}
