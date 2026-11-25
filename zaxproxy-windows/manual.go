package main

import (
	"encoding/json"
	"net/http"
	"strconv"
	"strings"
)

// Proxies the user typed in by hand. They live on this computer only (protected with the Windows
// account, like the sign-in token), are never sent to the website, and have negative IDs so they
// can never be mistaken for a proxy from the account.

const maxManual = 30

func (x *app) manualList() []publicProxy {
	list := make([]publicProxy, 0, len(x.manual))
	for _, p := range x.manual {
		list = append(list, publicProxy{ID: p.ID, Country: p.Country, Type: "Manual", Flag: "✏️", IP: p.IP + ":" + strconv.Itoa(p.Port)})
	}
	return list
}

func badText(s string, max int) bool {
	return len(s) > max || strings.ContainsAny(s, "\r\n\t\x00")
}

func handleManualAdd(w http.ResponseWriter, r *http.Request) {
	var in struct {
		Name, Host, Username, Password string
		Port                           int
	}
	if readBody(r, &in) != nil {
		writeJSON(w, 400, map[string]any{"ok": false, "error": "Fill in the proxy details."})
		return
	}
	in.Name, in.Host, in.Username = strings.TrimSpace(in.Name), strings.TrimSpace(in.Host), strings.TrimSpace(in.Username)
	switch {
	case in.Host == "" || badText(in.Host, 253) || strings.ContainsAny(in.Host, " /@"):
		writeJSON(w, 400, map[string]any{"ok": false, "error": "Enter the proxy IP address or host, for example 185.12.34.56."})
		return
	case in.Port < 1 || in.Port > 65535:
		writeJSON(w, 400, map[string]any{"ok": false, "error": "Enter the SOCKS5 port: a number between 1 and 65535."})
		return
	case badText(in.Name, 40) || badText(in.Username, 255) || badText(in.Password, 255):
		writeJSON(w, 400, map[string]any{"ok": false, "error": "One of the fields is too long."})
		return
	case (in.Username == "") != (in.Password == ""):
		writeJSON(w, 400, map[string]any{"ok": false, "error": "Enter both the username and the password, or leave both empty."})
		return
	}
	if in.Name == "" {
		in.Name = "Manual proxy"
	}
	a.mu.Lock()
	if len(a.manual) >= maxManual {
		a.mu.Unlock()
		writeJSON(w, 400, map[string]any{"ok": false, "error": "You have added the most proxies this app can keep. Remove one first."})
		return
	}
	id := int64(-1)
	for _, p := range a.manual {
		if p.ID <= id {
			id = p.ID - 1
		}
	}
	a.manual = append(a.manual, Proxy{ID: id, Type: "Manual", Country: in.Name, IP: in.Host, Port: in.Port, Username: in.Username, Password: in.Password})
	a.mu.Unlock()
	a.save()
	out := a.snapshot()
	out["added"] = id
	writeJSON(w, 200, out)
}

func handleManualRemove(w http.ResponseWriter, r *http.Request) {
	var in struct {
		ID int64 `json:"id"`
	}
	if readBody(r, &in) != nil || in.ID >= 0 {
		writeJSON(w, 400, map[string]any{"ok": false, "error": "Choose a proxy."})
		return
	}
	a.mu.Lock()
	inUse := a.connected == in.ID
	a.mu.Unlock()
	if inUse {
		a.disconnect()
	}
	a.mu.Lock()
	kept := a.manual[:0]
	for _, p := range a.manual {
		if p.ID != in.ID {
			kept = append(kept, p)
		}
	}
	a.manual = kept
	a.mu.Unlock()
	a.save()
	writeJSON(w, 200, a.snapshot())
}

func packManual(list []Proxy) string {
	if len(list) == 0 {
		return ""
	}
	b, _ := json.Marshal(list)
	s, err := protect(string(b))
	if err != nil {
		return ""
	}
	return s
}

func unpackManual(s string) []Proxy {
	if s == "" {
		return nil
	}
	plain, err := unprotect(s)
	if err != nil {
		return nil
	}
	var list []Proxy
	if json.Unmarshal([]byte(plain), &list) != nil {
		return nil
	}
	out := list[:0]
	for _, p := range list {
		if p.ID < 0 && p.IP != "" && p.Port > 0 && len(out) < maxManual {
			out = append(out, p)
		}
	}
	return out
}
