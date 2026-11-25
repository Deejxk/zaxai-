package main

import (
	"bytes"
	"encoding/json"
	"io"
	"net/http"
	"time"
)

type apiResult struct {
	OK    bool   `json:"ok"`
	Error string `json:"error"`
	Token string `json:"token"`
	User  struct {
		Name  string `json:"name"`
		Email string `json:"email"`
	} `json:"user"`
	Proxies []Proxy   `json:"proxies"`
	Expired []Expired `json:"expired"`
}

// The website is always called directly, never through the Windows proxy setting, so signing in
// and refreshing keep working whether or not a proxy is connected.
var apiClient = &http.Client{
	Timeout:       30 * time.Second,
	Transport:     &http.Transport{Proxy: nil, TLSHandshakeTimeout: 15 * time.Second, ResponseHeaderTimeout: 25 * time.Second},
	CheckRedirect: func(*http.Request, []*http.Request) error { return http.ErrUseLastResponse },
}

func apiCall(method, url, token string, body any) (apiResult, int, error) {
	var out apiResult
	var rd io.Reader
	if body != nil {
		b, _ := json.Marshal(body)
		rd = bytes.NewReader(b)
	}
	req, err := http.NewRequest(method, url, rd)
	if err != nil {
		return out, 0, err
	}
	req.Header.Set("Accept", "application/json")
	req.Header.Set("User-Agent", "ZaxProxyConnect-Windows/"+version)
	if body != nil {
		req.Header.Set("Content-Type", "application/json; charset=utf-8")
	}
	if token != "" {
		req.Header.Set("X-ZP-Token", token)
	}
	res, err := apiClient.Do(req)
	if err != nil {
		return out, 0, err
	}
	defer res.Body.Close()
	b, _ := io.ReadAll(io.LimitReader(res.Body, 4<<20))
	_ = json.Unmarshal(b, &out)
	return out, res.StatusCode, nil
}

func apiLogin(site, login, password, device string) (apiResult, error) {
	r, _, err := apiCall("POST", site+"/zp-app/login/", "", map[string]string{"login": login, "password": password, "device": device})
	return r, err
}

func apiProxies(site, token string) (apiResult, int, error) {
	return apiCall("GET", site+"/zp-app/proxies/", token, nil)
}

func apiLogout(site, token string) {
	_, _, _ = apiCall("POST", site+"/zp-app/logout/", token, map[string]string{})
}
