# ZaxConnect setup guide

This guide takes you from nothing to a working paid hotspot.

## What you need

| Item | Notes |
|---|---|
| MTN ODU with an active data plan | This is your internet source |
| **MikroTik router** running RouterOS v6.45+ or v7 | Required. It runs the hotspot. Any hAP model works |
| Wi-Fi access point(s) | Optional if the MikroTik has good built-in Wi-Fi |
| A small **VPS** (1 GB RAM is enough) | Must allow **UDP** traffic (DigitalOcean, Hetzner, Contabo, Vultr and similar). Platforms like Render, Railway and Vercel don't support UDP, so RADIUS won't work there |
| A domain name | For example `wifi.yourbusiness.com` |
| A Paystack business account | For real payments |

---

## 1. Deploy the server

Point your domain's **A record** at the VPS IP address. Then, on the VPS (Ubuntu):

### Option A: Docker (easiest)

```bash
sudo apt update && sudo apt install -y docker.io docker-compose-v2 git
git clone <this repo> /opt/zaxconnect && cd /opt/zaxconnect
cp .env.example .env
nano .env                      # set PUBLIC_URL=https://wifi.yourbusiness.com
nano deploy/Caddyfile          # replace wifi.yourdomain.com with your domain
sudo docker compose up -d --build
```

### Option B: Node.js + systemd

```bash
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash - && sudo apt install -y nodejs caddy
sudo useradd -r -s /bin/false zaxconnect
sudo git clone <this repo> /opt/zaxconnect && cd /opt/zaxconnect
sudo npm ci --omit=dev && sudo cp .env.example .env && sudo nano .env
sudo chown -R zaxconnect /opt/zaxconnect
sudo cp deploy/zaxconnect.service /etc/systemd/system/ && sudo systemctl enable --now zaxconnect
sudo cp deploy/Caddyfile /etc/caddy/Caddyfile && sudo nano /etc/caddy/Caddyfile && sudo systemctl reload caddy
```

### Open the firewall

```bash
sudo ufw allow 80,443/tcp
sudo ufw allow 1812,1813/udp
```

Also open these ports in your VPS provider's cloud firewall if it has one.

### Create your admin account

Visit `https://wifi.yourbusiness.com/admin` **straight away** and create your admin account.

---

## 2. Configure ZaxConnect

In **Admin → Settings**:

1. **Business:** name, support phone and WhatsApp number (shown to customers).
2. **Server & router:** your portal domain (`wifi.yourbusiness.com`) and the VPS public IP.
3. **Paystack:** paste your public key (`pk_live_…`) and secret key (`sk_live_…`). You'll find them at dashboard.paystack.com → Settings → API Keys & Webhooks. In the same Paystack page, set the **Webhook URL** to
   `https://wifi.yourbusiness.com/paystack/webhook`
   Tip: test first with your `pk_test_`/`sk_test_` keys.

In **Admin → Plans**, adjust the prices and limits to suit your market.

---

## 3. Set up the MikroTik

Wiring: **MTN ODU (LAN/PoE) → MikroTik ether1**. Access points go into the other MikroTik ports.

Then follow **Admin → Router setup**. It shows your personal script. In short:

1. **Internet:** WinBox → IP → DHCP Client → add one on `ether1`.
2. **Hotspot:** WinBox → IP → Hotspot → **Hotspot Setup** → choose your LAN bridge → accept the defaults → DNS name: `login.zaxconnect.wifi` → skip the local user.
3. **Script:** copy the script from the Router setup page → WinBox → New Terminal → paste. This sets up:
   - RADIUS pointing at your server, using your secret
   - Hotspot login over RADIUS with usage reporting every 5 minutes
   - A walled garden so customers can open the portal and Paystack **before** paying
4. **Redirect page:** download `login.html` and `alogin.html` from the Router setup page and drag them into the `hotspot` folder in WinBox → Files (replace the existing files).

When a phone joins the Wi-Fi it should now show the ZaxConnect portal. The Router setup page changes to **"Router connected"** after the first login attempt reaches the server.

---

## 4. Test before you open

1. Join the Wi-Fi with your phone. The portal appears (or open any `http://` site).
2. Buy the cheapest plan (with test keys, use a Paystack test card).
3. You're connected automatically and land on the "You're online" page.
4. In Admin: the sale shows on the Dashboard, the login appears under *Login activity*, and the device appears under *Online users* within 5 minutes.
5. Generate a few vouchers (Admin → Vouchers), print them, and try one from the **"I have a voucher"** tab.

## How the plan rules work

- **Validity starts at first login.** Unused vouchers never expire.
- **Time:** the router disconnects the user when the plan runs out (RADIUS Session-Timeout).
- **Speed:** enforced by the MikroTik through Mikrotik-Rate-Limit.
- **Data cap:** enforced per session through Mikrotik-Total-Limit. Usage is tracked across sessions.
- **Devices:** a voucher is locked to the first *N* devices (by MAC address). To move a customer to a new phone, use **Reset** in Admin → Vouchers.
- **Reconnecting:** the router remembers devices (cookie / MAC-cookie), so customers usually reconnect automatically while their plan is active.

## Troubleshooting

| Problem | Fix |
|---|---|
| Portal doesn't appear | Make sure `login.html` was uploaded to the `hotspot` folder. Open `http://neverssl.com` to trigger the redirect |
| Portal shows "site can't be reached" | Your domain is missing from the walled garden: re-run the script after setting **Portal domain** |
| Paystack page won't load before payment | Re-run the script (walled garden). Some banks' 3-D Secure pages need extra domains. Bank transfer and USSD always work |
| Router setup still says "Waiting for router" | Check that UDP 1812/1813 is open on the VPS, the server IP in Settings is correct, and the RADIUS secret matches. On the MikroTik, `/radius monitor 0` shows the request count |
| "Invalid voucher code" for a valid code | The RADIUS secret on the router doesn't match Settings → RADIUS secret. Re-run the script |
| Customer paid but saw no voucher | The webhook fulfils it automatically. They can use **Recover voucher** with their phone number, or find it in Admin → Sales |

## Backups

All data lives in `data/zaxconnect.db`. Back it up regularly, for example:

```bash
sqlite3 /opt/zaxconnect/data/zaxconnect.db ".backup '/root/zaxconnect-$(date +%F).db'"
```
