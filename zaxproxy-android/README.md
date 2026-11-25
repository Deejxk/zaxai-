# ZaxProxy Connect for Android

Sign in with a ZaxProxy account, pick one of your proxies and tap **Connect**. The whole phone then
uses that proxy (a local VPN that forwards traffic to the proxy over SOCKS5) until you tap Disconnect.

## Build

1. Install the Android SDK (platform 34, build-tools 34) and NDK 26.3.
2. Fetch the tunnel engine (open source, MIT): `./fetch-engine.sh`
3. Build: `gradle :app:assembleRelease` (or `assembleDebug`).

Release builds are signed with the key described in `keystore.properties` (not kept in this repository):

    storeFile=/path/to/zaxproxy.jks
    storePassword=...
    keyAlias=zaxproxy
    keyPassword=...

Keep that key file safe: every future update must be signed with the same key.

## How it talks to the site

`https://zaxproxy.cloud/zp-app/` (see `zaxproxy/includes/class-zp-app.php`): `login`, `proxies`, `logout`.
The app stores a device token, never the account password.

## Third-party code

The tunnel engine is [hev-socks5-tunnel](https://github.com/heiher/hev-socks5-tunnel) by hev (MIT licence),
which bundles lwIP (BSD), libyaml (MIT) and hev-task-system (MIT).
