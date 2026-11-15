'use strict';
// Generates MikroTik RouterOS configuration and hotspot HTML pages for this installation.

const PAYSTACK_HOSTS = [
  'paystack.com', 'www.paystack.com', 'checkout.paystack.com', 'standard.paystack.co', 'js.paystack.co',
  'api.paystack.co', 'paystack.co', 'assets.paystack.com', 'fonts.googleapis.com', 'fonts.gstatic.com',
];

const q = (v) => `"${String(v).replace(/["\\$]/g, '\\$&')}"`;

function script(s) {
  const ip = s.server_ip || 'YOUR.SERVER.IP';
  const host = s.portal_host || 'wifi.yourdomain.com';
  const lines = [
    `# ${s.business_name} hotspot billing — MikroTik RouterOS v6.45+ / v7`,
    '# Run AFTER "IP > Hotspot > Hotspot Setup". Safe to run again.',
    '',
    '# 1. RADIUS: the router asks the billing server whether a voucher may log in',
    '/radius remove [find comment="ZaxConnect"]',
    `/radius add service=hotspot address=${ip} secret=${q(s.radius_secret)} authentication-port=1812 accounting-port=1813 timeout=3s comment="ZaxConnect"`,
    '/radius incoming set accept=yes',
    '',
    '# 2. Hotspot profile: use RADIUS, send usage every 5 minutes, remember devices',
    `/ip hotspot profile set [find] use-radius=yes radius-accounting=yes radius-interim-update=5m login-by=http-pap,cookie,mac-cookie dns-name=${q(s.hotspot_dns)} http-cookie-lifetime=1d`,
    '# Allow multi-device plans (the billing server enforces each plan\'s device limit)',
    '/ip hotspot user profile set [find default=yes] shared-users=10 keepalive-timeout=5m',
    '',
    '# 3. Walled garden: pages customers can open BEFORE paying',
    '/ip hotspot walled-garden remove [find comment~"ZaxConnect"]',
    `/ip hotspot walled-garden add dst-host=${q(host)} comment="ZaxConnect portal"`,
    '/ip hotspot walled-garden add dst-host="*paystack*" comment="ZaxConnect payments"',
    '/ip hotspot walled-garden ip remove [find comment~"ZaxConnect"]',
    `/ip hotspot walled-garden ip add action=accept dst-address=${ip} comment="ZaxConnect portal"`,
    `/ip hotspot walled-garden ip add action=accept dst-host=${q(host)} comment="ZaxConnect portal"`,
    ...PAYSTACK_HOSTS.map((h) => `/ip hotspot walled-garden ip add action=accept dst-host=${q(h)} comment="ZaxConnect payments"`),
    '',
    '# 4. Done. Now upload login.html and alogin.html to the "hotspot" folder (Files).',
  ];
  return lines.join('\n');
}

function portalBase(s, publicUrl) {
  if (publicUrl) return publicUrl;
  return s.portal_host ? `https://${s.portal_host}` : 'https://wifi.yourdomain.com';
}

function loginHtml(s, publicUrl) {
  const base = portalBase(s, publicUrl);
  return `<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="pragma" content="no-cache">
<meta http-equiv="expires" content="-1">
<title>${s.business_name}</title>
<noscript><meta http-equiv="refresh" content="0; url=${base}/portal?mac=$(mac-esc)&amp;link-login-only=$(link-login-only-esc)&amp;dst=$(link-orig-esc)"></noscript>
<style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#0B0F2A;color:#fff;font-family:system-ui,sans-serif}
.s{width:36px;height:36px;border-radius:50%;border:3px solid rgba(255,255,255,.2);border-top-color:#14C8E8;animation:r 1s linear infinite;margin:0 auto 14px}
@keyframes r{to{transform:rotate(360deg)}}a{color:#9FE8F5}</style>
</head>
<body>
<div style="text-align:center"><div class="s"></div>Opening ${s.business_name}…<br><small><a id="go" href="${base}/portal">Tap here if nothing happens</a></small></div>
<script>
(function () {
  var p = {
    mac: "$(mac)",
    ip: "$(ip)",
    "link-login-only": "$(link-login-only)",
    dst: "$(link-orig)",
    error: "$(error)"
  };
  var qs = [];
  for (var k in p) if (p[k]) qs.push(encodeURIComponent(k) + "=" + encodeURIComponent(p[k]));
  var url = "${base}/portal?" + qs.join("&");
  document.getElementById("go").href = url;
  window.location.replace(url);
})();
</script>
</body>
</html>
`;
}

function aloginHtml(s, publicUrl) {
  const base = portalBase(s, publicUrl);
  return `<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Connected</title>
<style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#0B0F2A;color:#fff;font-family:system-ui,sans-serif}a{color:#9FE8F5}</style>
</head>
<body>
<div style="text-align:center">You're online!<br><small><a id="go" href="${base}/connected">Continue</a></small></div>
<script>
(function () {
  var url = "${base}/connected?code=" + encodeURIComponent("$(username)");
  document.getElementById("go").href = url;
  setTimeout(function () { window.location.replace(url); }, 800);
})();
</script>
</body>
</html>
`;
}

module.exports = { script, loginHtml, aloginHtml, PAYSTACK_HOSTS };
