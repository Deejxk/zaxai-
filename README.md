# ZaxConnect — Hotspot Billing System

ZaxConnect is a Wi-Fi hotspot billing system with a captive portal. When a customer joins your Wi-Fi, they're sent to a ZaxConnect page where they buy a plan with Paystack (card, bank transfer or USSD) and get connected automatically.

## How it works

```
 Internet                                         Customers' phones
    │                                                    │
 [MTN ODU] ──ethernet──▶ [MikroTik router: hotspot] ──▶ [Wi-Fi access point]
                                │      ▲
            redirect to portal  │      │  RADIUS: "is this voucher valid?"
                                ▼      │
                      [ZaxConnect server on a VPS]
                       portal · Paystack · vouchers · admin
```

**Important:** a consumer 4G/5G router like the MTN ODU can't redirect users to a payment page on its own. You need a cheap **MikroTik** router (for example hAP lite, hAP ac², hAP ax lite) between the ODU and your access points. The MikroTik does the hotspot work. ZaxConnect handles the billing.

1. A customer connects to your Wi-Fi. The MikroTik catches them and sends them to the ZaxConnect portal.
2. They pick a plan and pay with Paystack.
3. ZaxConnect creates a voucher code and logs the device in through the MikroTik.
4. The MikroTik asks ZaxConnect over **RADIUS** whether the code is valid. ZaxConnect replies with the plan's time limit, speed limit and data cap.
5. The router reports usage every 5 minutes, so you can see who's online and how much data they've used.

RADIUS traffic starts at the router, so this works behind MTN's CGNAT. You don't need a public IP at the hotspot.

## Features

**Customer portal (mobile first, light and dark mode)**
- Plan cards showing price, validity, speed, data and device count
- Paystack checkout (card, transfer, USSD, bank)
- Instant voucher on payment, with automatic connect
- A voucher login tab for printed cards
- A "Check my plan" page with a live countdown and data meter
- Lost-voucher recovery by phone number (only on the same device, or with the payment reference)

**Admin dashboard**
- Revenue today and this month, people online now, a 14-day revenue chart and top plans
- Plans: create or edit price, validity (minutes, hours or days), data cap, download/upload speed and devices per voucher
- Vouchers: search, disable, reset a device lock, and **generate printable voucher cards** for offline sales
- Sales history with CSV export
- Online users with live usage
- **Router setup page** that writes your MikroTik script and `login.html` for you
- Login activity from the router (useful for troubleshooting)

**Built in:** a RADIUS server (PAP and CHAP, Message-Authenticator, MikroTik rate and data limits), a SQLite database (no separate database server), and demo payment mode so you can test before adding Paystack keys.

## Quick start (local test)

Requires **Node.js 22.13+**.

```bash
npm install
npm start
```

1. Open http://localhost:3000/admin and create your admin account.
2. Open http://localhost:3000 to see the customer portal. Until Paystack keys are added, payments are simulated.

Run the tests with `npm test`. They simulate a MikroTik talking RADIUS to the server.

## Going live

See **[docs/SETUP.md](docs/SETUP.md)** for the full step-by-step guide: VPS, domain and HTTPS, Paystack, MikroTik, and testing.

## Project layout

```
server.js                 HTTP server + RADIUS startup
src/radius/               RADIUS codec and server (talks to MikroTik)
src/services/vouchers.js  voucher rules: activation, expiry, device and data limits
src/services/payments.js  Paystack checkout, verification, webhook
src/services/mikrotik.js  generated RouterOS script and hotspot pages
src/routes/               portal and admin routes
src/views/                page templates
public/                   CSS, JS, icons
deploy/                   Caddyfile, systemd unit
```
