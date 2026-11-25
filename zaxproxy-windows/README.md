# ZaxProxy for Windows

Sign in with a ZaxProxy account, pick a proxy and click **Connect**.

The program runs a small relay on this computer (`127.0.0.1:47822`) and points the Windows proxy
setting at it. The relay passes every connection to the chosen proxy over SOCKS5 with the proxy's
username and password, so browsers never ask for them. Disconnecting or quitting puts the Windows
setting back as it was; if the program ends badly, the next start puts it back.

The window is a page served from this computer only (`127.0.0.1:47821`), protected by a one-time
key; a tray icon keeps the program reachable. The sign-in token is stored protected with the
Windows account (DPAPI). The account password is never stored.

## Build (from any system with Go)

    GOOS=windows GOARCH=amd64 CGO_ENABLED=0 go build -trimpath -ldflags "-H=windowsgui" -o ZaxProxy.exe .

## Test the logic without Windows

    go build -o zaxproxy-test . && ZAX_SITE=https://zaxproxy.cloud ./zaxproxy-test

(on other systems the Windows-only parts are replaced by log lines; see `sys_other.go`).

Note: this changes the Windows proxy setting, which browsers and most programs follow. It is not a
VPN: programs that ignore the Windows proxy setting are not routed.
