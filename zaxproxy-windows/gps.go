package main

import (
	"embed"
	"encoding/json"
	"fmt"
	"io"
	"io/fs"
	"math"
	"net"
	"net/http"
	"net/url"
	"os"
	"path/filepath"
	"strings"
	"sync/atomic"
	"time"
)

// GPS map.
//
// Windows has no setting that lets an ordinary program change the location of the whole PC, so
// this works where location is actually asked for on a computer: in the browser. The map in the
// app chooses a place; a small browser add-on ("ZaxProxy GPS", written to the user's ZaxProxy
// folder by this program) asks this program for that place and gives it to websites instead of
// the real location. When the GPS is off, or this program is closed, websites get the real
// location again.

//go:embed extension leaflet
var assetsFS embed.FS

// The add-on looks for the program on these local ports, in order.
var gpsPorts = []int{47831, 47832, 47833, 47834, 47835}

type gpsState struct {
	On    bool    `json:"on"`
	Lat   float64 `json:"lat"`
	Lng   float64 `json:"lng"`
	Label string  `json:"label"`
	Since int64   `json:"since"`
	Auto  bool    `json:"auto"`  // follow the connected proxy
	Ext   bool    `json:"ext"`   // the browser add-on has asked recently
	Ready bool    `json:"ready"` // the local address the add-on uses is open
	// The ZaxProxy browser window (opened by this program, GPS applied automatically).
	Browser      bool   `json:"browser"`
	BrowserName  string `json:"browserName"`
	BrowserTabs  int    `json:"browserTabs"`
	BrowserError string `json:"browserError"`
}

var (
	gpsSeen  atomic.Int64 // when the add-on last asked (unix seconds)
	gpsReady atomic.Bool
)

func (x *app) gpsView() gpsState {
	g := x.gps
	g.Ext = time.Now().Unix()-gpsSeen.Load() < 100
	g.Ready = gpsReady.Load()
	g.Browser, g.BrowserName, g.BrowserTabs = browserStatus()
	return g
}

func validSpot(lat, lng float64) bool {
	return !math.IsNaN(lat) && !math.IsNaN(lng) && math.Abs(lat) <= 90 && math.Abs(lng) <= 180
}

func (x *app) gpsSet(lat, lng float64, label string) {
	x.mu.Lock()
	if !x.gps.On {
		x.gps.Since = time.Now().Unix()
	}
	x.gps.On, x.gps.Lat, x.gps.Lng, x.gps.Label = true, lat, lng, label
	x.mu.Unlock()
	go browserSync(true) // opens the ZaxProxy browser window if it is not open yet
}

func (x *app) gpsOff() {
	x.mu.Lock()
	x.gps.On, x.gps.Since = false, 0
	x.mu.Unlock()
	go browserSync(false)
}

// gpsFollow moves the GPS to where a proxy is, when "match my GPS to the connected proxy" is on.
func (x *app) gpsFollow(p *Proxy) {
	x.mu.Lock()
	auto := x.gps.Auto
	x.mu.Unlock()
	if !auto || p == nil || p.Lat == nil || p.Lng == nil || !validSpot(*p.Lat, *p.Lng) {
		return
	}
	where := p.Country
	if p.Place != "" {
		where = p.Place + ", " + p.Country
	}
	x.gpsSet(*p.Lat, *p.Lng, where)
}

// startGPSListener opens the local address the browser add-on asks. Only this computer can reach it.
func startGPSListener() {
	mux := http.NewServeMux()
	mux.HandleFunc("/gps", func(w http.ResponseWriter, r *http.Request) {
		// Only the add-on may read this. Web pages cannot: no cross-site permission is given,
		// and requests that carry a website's address are refused outright.
		if o := r.Header.Get("Origin"); o != "" && !strings.HasPrefix(o, "chrome-extension://") && !strings.HasPrefix(o, "moz-extension://") {
			http.Error(w, "Not allowed.", http.StatusForbidden)
			return
		}
		if !strings.HasPrefix(r.Host, "127.0.0.1:") {
			http.NotFound(w, r)
			return
		}
		if r.URL.Query().Get("src") == "auto" {
			gpsSeenAuto.Store(time.Now().Unix()) // the add-on inside the ZaxProxy browser window
		} else {
			gpsSeen.Store(time.Now().Unix())
		}
		a.mu.Lock()
		g := a.gps
		a.mu.Unlock()
		writeJSON(w, 200, map[string]any{"app": "zaxproxy", "on": g.On, "lat": g.Lat, "lng": g.Lng, "label": g.Label})
	})
	for _, p := range gpsPorts {
		ln, err := net.Listen("tcp", fmt.Sprintf("127.0.0.1:%d", p))
		if err != nil {
			continue
		}
		gpsReady.Store(true)
		go func() { _ = http.Serve(ln, mux) }()
		return
	}
}

// extensionDir is where the add-on's files are kept for the browser to load.
func extensionDir() string { return filepath.Join(filepath.Dir(configPath()), "ZaxProxy GPS add-on") }

// writeExtension puts the current add-on files on disk (they travel inside this program).
func writeExtension() {
	dir := extensionDir()
	_ = os.MkdirAll(dir, 0o755)
	_ = fs.WalkDir(assetsFS, "extension", func(path string, d fs.DirEntry, err error) error {
		if err != nil || d.IsDir() {
			return nil
		}
		b, e := assetsFS.ReadFile(path)
		if e == nil {
			_ = os.WriteFile(filepath.Join(dir, filepath.Base(path)), b, 0o644)
			// The ZaxProxy browser window loads its own copy, which says who it is.
			if filepath.Base(path) == "mode.js" {
				b = []byte("self.ZAX_SRC = 'auto';\n")
			}
			_ = os.MkdirAll(browserAddonDir(), 0o755)
			_ = os.WriteFile(filepath.Join(browserAddonDir(), filepath.Base(path)), b, 0o644)
		}
		return nil
	})
}

// handleAsset serves the map library to the program's own window.
func handleAsset(w http.ResponseWriter, r *http.Request) {
	name := strings.TrimPrefix(r.URL.Path, "/leaflet/")
	if !localHost(r) || (name != "leaflet.js" && name != "leaflet.css") {
		http.NotFound(w, r)
		return
	}
	b, _ := assetsFS.ReadFile("leaflet/" + name)
	if strings.HasSuffix(name, ".css") {
		w.Header().Set("Content-Type", "text/css; charset=utf-8")
	} else {
		w.Header().Set("Content-Type", "application/javascript; charset=utf-8")
	}
	w.Header().Set("Cache-Control", "max-age=86400")
	w.Write(b)
}

func handleGPSSet(w http.ResponseWriter, r *http.Request) {
	var in struct {
		Lat, Lng float64
		Label    string
	}
	if readBody(r, &in) != nil || !validSpot(in.Lat, in.Lng) || badText(in.Label, 160) {
		writeJSON(w, 400, map[string]any{"ok": false, "error": "Choose a place on the map first."})
		return
	}
	a.gpsSet(in.Lat, in.Lng, strings.TrimSpace(in.Label))
	writeJSON(w, 200, a.snapshot())
}

func handleGPSOff(w http.ResponseWriter, r *http.Request) {
	a.gpsOff()
	writeJSON(w, 200, a.snapshot())
}

func handleGPSAuto(w http.ResponseWriter, r *http.Request) {
	var in struct {
		On bool `json:"on"`
	}
	if readBody(r, &in) != nil {
		writeJSON(w, 400, map[string]any{"ok": false})
		return
	}
	a.mu.Lock()
	a.gps.Auto = in.On
	id := a.connected
	var now *Proxy
	for i := range a.proxies {
		if a.proxies[i].ID == id {
			p := a.proxies[i]
			now = &p
		}
	}
	a.mu.Unlock()
	a.save()
	if in.On {
		a.gpsFollow(now) // already connected: match straight away
	}
	writeJSON(w, 200, a.snapshot())
}

// handleGPSBrowser opens the ZaxProxy browser window (or brings the GPS choice to it again).
func handleGPSBrowser(w http.ResponseWriter, r *http.Request) {
	if err := browserOpen(); err != nil {
		writeJSON(w, 500, map[string]any{"ok": false, "error": err.Error()})
		return
	}
	browserSync(false)
	writeJSON(w, 200, a.snapshot())
}

// handleGPSFolder shows the add-on's folder so it can be chosen in the browser.
func handleGPSFolder(w http.ResponseWriter, r *http.Request) {
	var in struct {
		Show bool `json:"show"`
	}
	_ = readBody(r, &in)
	writeExtension()
	if in.Show {
		_ = openBrowser(extensionDir())
	}
	writeJSON(w, 200, map[string]any{"ok": true, "path": extensionDir()})
}

/* ---------- Place names ---------- */

var geoClient = &http.Client{
	Timeout:   15 * time.Second,
	Transport: &http.Transport{Proxy: http.ProxyFromEnvironment, TLSHandshakeTimeout: 10 * time.Second, ResponseHeaderTimeout: 12 * time.Second},
}

func geoGet(u string) ([]byte, error) {
	req, err := http.NewRequest("GET", u, nil)
	if err != nil {
		return nil, err
	}
	req.Header.Set("User-Agent", "ZaxProxyConnect/"+version+" (Windows; zaxproxy.cloud)")
	req.Header.Set("Accept", "application/json")
	req.Header.Set("Accept-Language", "en")
	res, err := geoClient.Do(req)
	if err != nil {
		return nil, err
	}
	defer res.Body.Close()
	if res.StatusCode != 200 {
		return nil, fmt.Errorf("status %d", res.StatusCode)
	}
	return io.ReadAll(io.LimitReader(res.Body, 600000))
}

func handleGeoSearch(w http.ResponseWriter, r *http.Request) {
	var in struct {
		Q string `json:"q"`
	}
	q := ""
	if readBody(r, &in) == nil {
		q = strings.TrimSpace(in.Q)
	}
	if q == "" || len(q) > 120 {
		writeJSON(w, 200, map[string]any{"ok": true, "results": []any{}})
		return
	}
	b, err := geoGet("https://nominatim.openstreetmap.org/search?format=jsonv2&addressdetails=1&limit=8&accept-language=en&q=" + url.QueryEscape(q))
	var list []json.RawMessage
	if err != nil || json.Unmarshal(b, &list) != nil {
		writeJSON(w, 502, map[string]any{"ok": false, "error": "Could not search. Check your internet connection."})
		return
	}
	writeJSON(w, 200, map[string]any{"ok": true, "results": list})
}

func handleGeoReverse(w http.ResponseWriter, r *http.Request) {
	var in struct{ Lat, Lng float64 }
	if readBody(r, &in) != nil || !validSpot(in.Lat, in.Lng) {
		writeJSON(w, 400, map[string]any{"ok": false})
		return
	}
	b, err := geoGet(fmt.Sprintf("https://nominatim.openstreetmap.org/reverse?format=jsonv2&zoom=12&accept-language=en&lat=%.5f&lon=%.5f", in.Lat, in.Lng))
	var place json.RawMessage
	if err != nil || json.Unmarshal(b, &place) != nil {
		writeJSON(w, 200, map[string]any{"ok": true, "place": nil})
		return
	}
	writeJSON(w, 200, map[string]any{"ok": true, "place": place})
}
