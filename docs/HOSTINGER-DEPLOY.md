# Deploy ZaxConnect on Hostinger (zaxconnect.cloud)

About 15 minutes. You need your Hostinger login and the file `dist/zaxconnect.zip` from this repository.

## 1. Point zaxconnect.cloud at your Business hosting

**If you bought the domain at Hostinger**
1. hPanel → **Websites** → **Add website** (or **Create or migrate a website**).
2. Choose **WordPress** and pick **zaxconnect.cloud** as the domain.
3. Finish the wizard. Hostinger installs WordPress and creates the admin login. Write it down.

**If the domain is registered elsewhere**
1. At your registrar, set the nameservers to `ns1.dns-parking.com` and `ns2.dns-parking.com`.
2. Then follow the steps above. DNS can take a few hours to update.

## 2. Turn on HTTPS

hPanel → **Websites** → zaxconnect.cloud → **Security** → **SSL** → install the free SSL. Wait until it shows **Active**.

## 3. Install the plugin

1. Open **https://zaxconnect.cloud/wp-admin** and log in.
2. **Plugins → Add New → Upload Plugin** → choose `zaxconnect.zip` → **Install Now** → **Activate**.
3. **Settings → Permalinks** → select **Post name** → **Save Changes**.

## 4. Set up the platform

1. Open **https://zaxconnect.cloud/zx-admin/login** and sign in with your WordPress administrator account. This is your admin console; it is separate from wp-admin. Add staff under **Team**.
2. **Platform settings**:
   - Set your **subscription prices** (what hotspot owners pay you monthly) and the free-trial length.
   - Add **your Flutterwave keys** (dashboard.flutterwave.com → Settings → API Keys). These collect subscription fees.
   - In Flutterwave → Settings → **Webhooks**, set the URL shown on the page (`https://zaxconnect.cloud/wp-json/zaxconnect/v1/flutterwave/platform`) and a secret hash. Paste the same hash in Platform settings.
3. Visit **https://zaxconnect.cloud**. The ZaxConnect landing page is now your homepage.

## 5. Test it end to end

1. Open **https://zaxconnect.cloud/signup** in a private window and create a test hotspot business.
2. In its dashboard (**/dashboard**), leave Flutterwave empty. Payments are simulated until keys are added.
3. Open the business's portal link (shown under **Portal & branding**) on your phone, buy a plan, and check that the voucher appears under **Sales** and **Vouchers**.

## 6. Connect a MikroTik router (each hotspot owner does this)

Wiring: **MTN ODU → MikroTik ether1**, access points → other MikroTik ports. Then:

1. WinBox → **IP → DHCP Client** → add on `ether1`.
2. **IP → Hotspot → Hotspot Setup** on the LAN bridge, DNS name `login.wifi`, skip the local user.
3. Dashboard → **Locations & routers** → copy the script → WinBox → **New Terminal** → paste.
4. Download **login.html** and **alogin.html** from the same page and drag both into the router's **Files → hotspot** folder.
5. Within about 10 seconds the location shows **Connected**.

## Hostinger-specific notes

- **Caching:** the plugin tells LiteSpeed Cache not to cache dashboards, portals or router check-ins. If you use another cache plugin, exclude `/dashboard`, `/zx-admin`, `/h/` and `/wp-json/zaxconnect/`.
- **Security / firewall:** if routers show "Not connected" while they have internet, check hPanel → **Security** → that requests to `/wp-json/zaxconnect/v1/sync` are not blocked. Their user agent starts with `Mikrotik`.
- **Login protection:** consider a login-limit plugin for `/wp-login.php`. Owner logins go through `/login`, which uses WordPress's normal authentication.
- **Backups:** turn on Hostinger's daily backups. All data lives in the WordPress database (tables starting `wp_zx_`).
