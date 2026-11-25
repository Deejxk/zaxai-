// ZaxProxy for Windows.
//
// Sign in with a ZaxProxy account, pick a proxy, click Connect.
//
// How it works: the program runs a small local relay on this computer (127.0.0.1) and points the
// Windows proxy setting at it. The relay passes every connection on to the chosen proxy over
// SOCKS5, signing in with the proxy's username and password, so browsers never ask for them.
// Disconnecting (or quitting) puts the Windows proxy setting back exactly as it was.
//
// The program has its own window. Its screen is a page served from this computer only and shown
// inside the window; closing the window disconnects and quits. The account password is used once to sign in and is never stored.
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
	version = "1.3.0"
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
	Place    string `json:"place"`
	Port     int    `json:"port"`
	Username string `json:"username"`
	Password string `json:"password"`
	Left     string `json:"left"`
	State    string `json:"state"`
}

// Expired is a proxy that has run out: shown for information, never connectable.
type Expired struct {
	ID      int64  `json:"id"`
	Type    string `json:"type"`
	Country string `json:"country"`
	Flag    string `json:"flag"`
	IP      string `json:"ip"`
	Place   string `json:"place"`
	Ended   string `json:"ended"`
}

type app struct {
	mu        sync.Mutex
	key       string // one-time secret the page must present
	token     string
	name      string
	proxies   []Proxy
	manual    []Proxy // typed in by hand; kept on this computer only
	expired   []Expired
	notice    string
	connected int64
	since     int64 // when the current connection started (unix seconds)
	relay     *relay
	site      string
}

var a = &app{}

// uiAddr is where this copy's window is served (this computer only), chosen at start-up.
var uiAddr string

func instanceFile() string { return filepath.Join(filepath.Dir(configPath()), "running.txt") }

// runningCopy returns the local address of a copy that is already running, if one left a note.
func runningCopy() string {
	b, err := os.ReadFile(instanceFile())
	if err != nil {
		return ""
	}
	addr := strings.TrimSpace(string(b))
	if !strings.HasPrefix(addr, "127.0.0.1:") {
		return ""
	}
	return addr
}

func rememberCopy(addr string) { _ = os.WriteFile(instanceFile(), []byte(addr), 0o600) }

func main() {
	setupLog()
	// Nothing fails silently: any unexpected error is written to the log and shown in a message box.
	defer func() {
		if r := recover(); r != nil {
			log.Printf("crash: %v\n%s", r, debug.Stack())
			alert("ZaxProxy Connect had a problem", fmt.Sprintf("ZaxProxy Connect could not start.\n\nDetails: %v\n\nA log was saved here:\n%s\n\nPlease send that file to support.", r, logPath()), true)
			os.Exit(1)
		}
	}()
	log.Printf("ZaxProxy Connect %s starting", version)
	a.site = strings.TrimRight(envOr("ZAX_SITE", "https://zaxproxy.cloud"), "/")

	// Already running? Ask that copy to show its window and leave.
	if p := runningCopy(); p != "" {
		c := http.Client{Timeout: 3 * time.Second}
		if r, e := c.Get("http://" + p + "/open"); e == nil {
			ok := r.StatusCode == 204 && r.Header.Get("X-Zax") == "1"
			r.Body.Close()
			if ok {
				log.Print("already running: asked the first copy to show its window")
				return
			}
		}
	}
	// Any free local address will do. A fixed one is only tried first for convenience: Windows
	// sometimes reserves ports, and an older copy may still be holding one.
	ln, err := net.Listen("tcp", "127.0.0.1:47821")
	if err != nil {
		ln, err = net.Listen("tcp", "127.0.0.1:0")
	}
	if err != nil {
		log.Printf("cannot open a local address: %v", err)
		alert("ZaxProxy Connect cannot start", "Windows did not let ZaxProxy Connect open a local address on this computer.\n\nDetails: "+err.Error()+"\n\nRestart your computer and try again. If it still fails, your security software may be blocking it.", true)
		return
	}
	uiAddr = ln.Addr().String()
	rememberCopy(uiAddr)

	buf := make([]byte, 24)
	if _, err := rand.Read(buf); err != nil {
		log.Fatal(err)
	}
	a.key = hex.EncodeToString(buf)

	cfg := loadConfig()
	a.token = cfg.Token
	a.name = cfg.Name
	a.manual = unpackManual(cfg.Manual)
	// A previous run that ended badly may have left Windows pointing at our relay: put it back.
	recoverSystemProxy(cfg)

	mux := http.NewServeMux()
	mux.HandleFunc("/", handleUI)
	mux.HandleFunc("/open", func(w http.ResponseWriter, r *http.Request) {
		w.Header().Set("X-Zax", "1")
		w.WriteHeader(204)
		go focusWindow()
	})
	mux.HandleFunc("/api/state", guard(handleState))
	mux.HandleFunc("/api/open", guard(handleOpen))
	mux.HandleFunc("/api/login", guard(handleLogin))
	mux.HandleFunc("/api/logout", guard(handleLogout))
	mux.HandleFunc("/api/refresh", guard(handleRefresh))
	mux.HandleFunc("/api/connect", guard(handleConnect))
	mux.HandleFunc("/api/disconnect", guard(handleDisconnect))
	mux.HandleFunc("/api/manual/add", guard(handleManualAdd))
	mux.HandleFunc("/api/manual/remove", guard(handleManualRemove))
	mux.HandleFunc("/api/quit", guard(func(w http.ResponseWriter, r *http.Request) {
		writeJSON(w, 200, map[string]any{"ok": true})
		go func() { time.Sleep(300 * time.Millisecond); quit() }()
	}))
	go func() { _ = http.Serve(ln, mux) }()

	go func() {
		for {
			_ = a.refresh()
			time.Sleep(5 * time.Minute)
		}
	}()
	log.Printf("window address: http://%s", uiAddr)
	runWindow(uiURL()) // returns when the window is closed
	quit()
}

func envOr(k, d string) string {
	if v := os.Getenv(k); v != "" {
		return v
	}
	return d
}

func uiURL() string { return "http://" + uiAddr + "/#k=" + a.key }

func logPath() string { return filepath.Join(filepath.Dir(configPath()), "log.txt") }

func setupLog() {
	f, err := os.OpenFile(logPath(), os.O_CREATE|os.O_WRONLY|os.O_TRUNC, 0o600)
	if err == nil {
		log.SetOutput(io.MultiWriter(f, os.Stderr))
	}
}

func quit() {
	a.disconnect()
	_ = os.Remove(instanceFile())
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

func localHost(r *http.Request) bool { return r.Host == uiAddr }

// guard: only this computer's own page, holding the secret, may give orders.
func guard(h http.HandlerFunc) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		if !localHost(r) || r.Header.Get("X-Key") != a.key {
			writeJSON(w, 403, map[string]any{"ok": false, "error": "Please reopen ZaxProxy Connect from the shield icon near the clock."})
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
	Country string `json:"country"`
	Type    string `json:"type"`
	Place   string `json:"place"`
	Flag    string `json:"flag"`
	IP      string `json:"ip"`
	Left    string `json:"left"`
	State   string `json:"state"`
}

func (x *app) snapshot() map[string]any {
	x.mu.Lock()
	defer x.mu.Unlock()
	list := make([]publicProxy, 0, len(x.proxies))
	for _, p := range x.proxies { // passwords stay inside the program; the page never receives them
		list = append(list, publicProxy{p.ID, p.Country, p.Type, p.Place, p.Flag, p.IP, p.Left, p.State})
	}
	old := x.expired
	if old == nil {
		old = []Expired{}
	}
	return map[string]any{"ok": true, "signedIn": x.token != "", "name": x.name, "proxies": list, "manual": x.manualList(), "expired": old, "notice": x.notice, "connected": x.connected, "since": x.since, "version": version}
}

func handleState(w http.ResponseWriter, r *http.Request) { writeJSON(w, 200, a.snapshot()) }

// handleOpen opens a page of the ZaxProxy website in the user's normal browser (buy, register, renew).
func handleOpen(w http.ResponseWriter, r *http.Request) {
	var in struct {
		Path string `json:"path"`
	}
	if readBody(r, &in) != nil || !strings.HasPrefix(in.Path, "/") || strings.HasPrefix(in.Path, "//") || strings.ContainsAny(in.Path, "\\\r\n ") {
		writeJSON(w, 400, map[string]any{"ok": false})
		return
	}
	_ = openBrowser("https://zaxproxy.cloud" + in.Path)
	writeJSON(w, 200, map[string]any{"ok": true})
}

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
	a.token, a.name, a.proxies, a.expired, a.notice = res.Token, res.User.Name, res.Proxies, res.Expired, ""
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
	x.proxies, x.expired, x.name = res.Proxies, res.Expired, res.User.Name
	gone := x.connected > 0 // only account proxies can run out; hand-typed ones are left alone
	for _, p := range res.Proxies {
		if p.ID == x.connected {
			gone = false
		}
	}
	x.mu.Unlock()
	if gone { // the proxy in use has run out or was removed: stop using it
		x.disconnect()
		x.mu.Lock()
		x.notice = "The proxy you were using has expired, so you were disconnected. Renew it on the website or choose another location."
		x.mu.Unlock()
	}
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
	for i := range a.manual {
		if a.manual[i].ID == in.ID {
			p := a.manual[i]
			chosen = &p
		}
	}
	a.mu.Unlock()
	if chosen == nil {
		writeJSON(w, 404, map[string]any{"ok": false, "error": "That proxy is no longer in your list. Click Refresh."})
		return
	}
	// The new proxy is proven to work first; only then is the current one let go. A failed switch
	// therefore leaves the user on the proxy they were already using.
	rl, err := startRelay("127.0.0.1:0", *chosen) // any free local port
	if err != nil {
		msg := err.Error()
		if chosen.ID < 0 {
			msg = "Could not connect through this proxy. Check the IP address, the SOCKS5 port, the username and the password, and that the proxy has not expired."
		}
		writeJSON(w, 502, map[string]any{"ok": false, "error": msg})
		return
	}
	a.disconnect()
	if err := setSystemProxy(rl.addr); err != nil {
		rl.stop()
		writeJSON(w, 500, map[string]any{"ok": false, "error": "Windows did not accept the proxy setting: " + err.Error()})
		return
	}
	a.mu.Lock()
	a.notice = ""
	a.relay, a.connected, a.since = rl, chosen.ID, time.Now().Unix()
	a.mu.Unlock()
	where := chosen.Country
	if chosen.Place != "" {
		where = chosen.Place + ", " + chosen.Country
	}
	trayStatus(true, where+" · "+chosen.IP)
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
	x.relay, x.connected, x.since = nil, 0, 0
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
	Manual    string `json:"manual,omitempty"` // proxies typed in by hand, protected the same way as the token
	Relay     string `json:"relay,omitempty"`  // the local relay address Windows was pointed at
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
	c.Manual = packManual(x.manual)
	x.mu.Unlock()
	writeConfig(c)
}
