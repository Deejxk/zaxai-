package main

import (
	"context"
	"fmt"
	"io"
	"net"
	"net/http"
	"strconv"
	"sync"
	"time"

	"golang.org/x/net/proxy"
)

// relay is the local helper Windows is pointed at. It accepts ordinary proxy requests from this
// computer only and passes each one to the customer's proxy over SOCKS5, with their credentials.
type relay struct {
	ln     net.Listener
	srv    *http.Server
	dial   func(ctx context.Context, network, addr string) (net.Conn, error)
	client *http.Transport
	conns  sync.Map
}

func startRelay(addr string, p Proxy) (*relay, error) {
	var auth *proxy.Auth
	if p.Username != "" {
		auth = &proxy.Auth{User: p.Username, Password: p.Password}
	}
	d, err := proxy.SOCKS5("tcp", net.JoinHostPort(p.IP, strconv.Itoa(p.Port)), auth, &net.Dialer{Timeout: 12 * time.Second, KeepAlive: 30 * time.Second})
	if err != nil {
		return nil, fmt.Errorf("This proxy could not be set up.")
	}
	cd, ok := d.(proxy.ContextDialer)
	if !ok {
		return nil, fmt.Errorf("This proxy could not be set up.")
	}

	// Prove the proxy answers and accepts the username and password before touching Windows settings.
	ctx, cancel := context.WithTimeout(context.Background(), 12*time.Second)
	c, err := cd.DialContext(ctx, "tcp", envOr("ZAX_TEST_TARGET", "www.gstatic.com:443"))
	cancel()
	if err != nil {
		return nil, fmt.Errorf("Could not connect through this proxy. It may have expired, or your internet is down.")
	}
	c.Close()

	ln, err := net.Listen("tcp", addr)
	if err != nil {
		return nil, fmt.Errorf("Another program is using the port ZaxProxy needs (%s).", addr)
	}
	r := &relay{ln: ln, dial: cd.DialContext}
	r.client = &http.Transport{DialContext: cd.DialContext, MaxIdleConns: 32, IdleConnTimeout: 60 * time.Second, ResponseHeaderTimeout: 60 * time.Second}
	r.srv = &http.Server{Handler: http.HandlerFunc(r.serve), ReadHeaderTimeout: 20 * time.Second}
	go func() { _ = r.srv.Serve(ln) }()
	return r, nil
}

func (r *relay) stop() {
	_ = r.srv.Close()
	r.conns.Range(func(k, _ any) bool {
		k.(net.Conn).Close()
		return true
	})
	r.client.CloseIdleConnections()
}

func (r *relay) serve(w http.ResponseWriter, req *http.Request) {
	if req.Method == http.MethodConnect {
		r.tunnel(w, req)
		return
	}
	if !req.URL.IsAbs() {
		http.Error(w, "This is the ZaxProxy local relay.", http.StatusBadRequest)
		return
	}
	// Plain (non-HTTPS) request: fetch it through the proxy.
	out := req.Clone(req.Context())
	out.RequestURI = ""
	for _, h := range []string{"Proxy-Connection", "Proxy-Authorization", "Connection", "Keep-Alive", "Te", "Trailer", "Upgrade"} {
		out.Header.Del(h)
	}
	res, err := r.client.RoundTrip(out)
	if err != nil {
		http.Error(w, "The proxy could not reach this address.", http.StatusBadGateway)
		return
	}
	defer res.Body.Close()
	for k, vv := range res.Header {
		for _, v := range vv {
			w.Header().Add(k, v)
		}
	}
	w.WriteHeader(res.StatusCode)
	_, _ = io.Copy(w, res.Body)
}

func (r *relay) tunnel(w http.ResponseWriter, req *http.Request) {
	ctx, cancel := context.WithTimeout(req.Context(), 20*time.Second)
	up, err := r.dial(ctx, "tcp", req.Host)
	cancel()
	if err != nil {
		http.Error(w, "The proxy could not reach this address.", http.StatusBadGateway)
		return
	}
	hj, ok := w.(http.Hijacker)
	if !ok {
		up.Close()
		http.Error(w, "Not supported.", http.StatusInternalServerError)
		return
	}
	down, _, err := hj.Hijack()
	if err != nil {
		up.Close()
		return
	}
	r.conns.Store(down, true)
	r.conns.Store(up, true)
	_, _ = down.Write([]byte("HTTP/1.1 200 Connection established\r\n\r\n"))
	done := make(chan struct{}, 2)
	go func() { _, _ = io.Copy(up, down); done <- struct{}{} }()
	go func() { _, _ = io.Copy(down, up); done <- struct{}{} }()
	<-done
	up.Close()
	down.Close()
	<-done
	r.conns.Delete(down)
	r.conns.Delete(up)
}
