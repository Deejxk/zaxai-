=== ZaxConnect – Hotspot Billing SaaS ===
Contributors: zaxconnect
Tags: hotspot, wifi, billing, mikrotik, flutterwave, captive portal, saas
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later

Run a Wi-Fi hotspot billing platform on WordPress. Hotspot owners sign up, brand their own captive portal, sell plans with Flutterwave and manage MikroTik routers from their own dashboard. That dashboard is separate from wp-admin.

== Description ==

* Marketing landing page with pricing (optionally used as your homepage)
* Sign-up wizard for hotspot owners: account, brand (name, colour, logo), prices, Flutterwave, first router
* Owner dashboard at /dashboard: revenue, sales, vouchers and printable voucher cards, online users, routers, branding, payments, subscription
* Platform admin at /zx-admin (WordPress administrators): all businesses, MRR, suspend or extend, prices, platform Flutterwave keys
* Branded customer portal for each business at /h/{business}: plans, Flutterwave checkout (card, transfer, USSD), voucher login, plan status, voucher recovery
* MikroTik integration that works on shared hosting and behind CGNAT: every router checks in every 10 seconds over HTTPS, no RADIUS or VPS needed
* Light and dark mode, sticky header, mobile footer menu, animated icons

Hotspot owners never see wp-admin. They are redirected to their own dashboard.

== Installation ==

1. Upload `zaxconnect.zip` in Plugins → Add New → Upload Plugin, then activate.
2. Settings → Permalinks: choose "Post name" and save. Pretty links are required.
3. Visit /zx-admin to set your platform name, prices and Flutterwave keys.
4. Share /signup with hotspot owners.

== Frequently Asked Questions ==

= Which routers are supported? =
MikroTik RouterOS 6.45+ and 7.x with the Hotspot feature.

= Where does the money go? =
Customer payments go to each hotspot owner's own Flutterwave account. Subscription fees go to the platform's Flutterwave account set in /zx-admin.

== Changelog ==

= 1.0.0 =
* First release.
