'use strict';
// Shared HTML helpers, formatters and brand marks.

const ESC = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ESC[c]);

/** Tagged template that escapes interpolations unless wrapped with raw(). */
class Raw { constructor(s) { this.s = s; } toString() { return this.s; } }
const raw = (s) => new Raw(String(s ?? ''));
function html(strings, ...values) {
  let out = '';
  strings.forEach((s, i) => {
    out += s;
    if (i < values.length) out += render(values[i]);
  });
  return raw(out);
}
function render(v) {
  if (v instanceof Raw) return v.s;
  if (Array.isArray(v)) return v.map(render).join('');
  if (v === false || v === null || v === undefined) return '';
  return esc(v);
}

function money(n, symbol = '₦') {
  return `${symbol}${Number(n || 0).toLocaleString('en-NG')}`;
}

function duration(min) {
  min = Number(min);
  const plural = (n, w) => `${n} ${w}${n === 1 ? '' : 's'}`;
  if (min < 60) return plural(min, 'minute');
  if (min < 1440 && min % 60 === 0) return plural(min / 60, 'hour');
  if (min < 1440) return `${Math.floor(min / 60)}h ${min % 60}m`;
  if (min % 1440 === 0) return plural(min / 1440, 'day');
  return `${Math.floor(min / 1440)}d ${Math.round((min % 1440) / 60)}h`;
}

function dataLabel(mb) {
  mb = Number(mb);
  if (!mb) return 'Unlimited data';
  if (mb >= 1024) return `${+(mb / 1024).toFixed(1)}GB data`;
  return `${mb}MB data`;
}

function speedLabel(down) {
  return Number(down) > 0 ? `Up to ${+Number(down).toFixed(1)} Mbps` : 'Full speed';
}

function bytes(n) {
  n = Number(n || 0);
  const units = ['B', 'KB', 'MB', 'GB', 'TB'];
  let i = 0;
  while (n >= 1024 && i < units.length - 1) { n /= 1024; i++; }
  return `${i === 0 ? n : n.toFixed(n >= 100 ? 0 : 1)} ${units[i]}`;
}

function remaining(ms) {
  const s = Math.max(0, Math.floor(ms / 1000));
  const d = Math.floor(s / 86400);
  const h = Math.floor((s % 86400) / 3600);
  const m = Math.floor((s % 3600) / 60);
  if (d) return `${d}d ${h}h ${m}m`;
  if (h) return `${h}h ${m}m`;
  return `${m}m ${s % 60}s`;
}

function dateTime(ts) {
  if (!ts) return '—';
  return new Date(ts).toLocaleString('en-NG', {
    timeZone: 'Africa/Lagos', day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
  });
}

const LOGO_MARK = `<svg class="logo-mark" viewBox="0 0 48 48" aria-hidden="true">
  <defs><linearGradient id="zxg" x1="0" y1="0" x2="1" y2="1">
    <stop offset="0" stop-color="#6D4BFF"/><stop offset="1" stop-color="#14C8E8"/></linearGradient></defs>
  <rect width="48" height="48" rx="14" fill="url(#zxg)"/>
  <path d="M14 15.5h17.5L15.5 33H34" fill="none" stroke="#fff" stroke-width="4.2" stroke-linecap="round" stroke-linejoin="round"/>
  <circle cx="35" cy="14.5" r="3.2" fill="#fff"/>
</svg>`;

function logo(name = 'ZaxConnect') {
  const m = /^(.*?)(Connect)$/i.exec(name);
  const word = m ? html`${m[1]}<span>${m[2]}</span>` : esc(name);
  return raw(`<span class="logo">${LOGO_MARK}<span class="logo-word">${word}</span></span>`);
}

const ICONS = {
  clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
  bolt: '<path d="M13 3 5 13.5h6L10 21l8-10.5h-6z"/>',
  data: '<ellipse cx="12" cy="6" rx="7" ry="3"/><path d="M5 6v6c0 1.7 3.1 3 7 3s7-1.3 7-3V6"/><path d="M5 12v6c0 1.7 3.1 3 7 3s7-1.3 7-3v-6"/>',
  devices: '<rect x="3" y="5" width="13" height="10" rx="1.5"/><path d="M2 18h15"/><rect x="18" y="8" width="4" height="10" rx="1"/>',
  wifi: '<path d="M2.5 9a14 14 0 0 1 19 0"/><path d="M5.5 12.5a9.5 9.5 0 0 1 13 0"/><path d="M8.7 16a5 5 0 0 1 6.6 0"/><circle cx="12" cy="19.3" r="1.1" fill="currentColor"/>',
  ticket: '<path d="M3 8a2 2 0 0 0 2-2h14a2 2 0 0 0 2 2v2a2 2 0 0 0 0 4v2a2 2 0 0 0-2 2H5a2 2 0 0 0-2-2v-2a2 2 0 0 0 0-4z"/><path d="M14 6v12" stroke-dasharray="2 2"/>',
  shield: '<path d="M12 3 4.5 6v5.5c0 4.6 3.2 8.3 7.5 9.5 4.3-1.2 7.5-4.9 7.5-9.5V6z"/><path d="m9 12 2 2 4-4"/>',
  check: '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
  copy: '<rect x="8" y="8" width="12" height="12" rx="2"/><path d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2"/>',
  phone: '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/>',
  whatsapp: '<path d="M4 20l1.3-4A8 8 0 1 1 8.4 19z"/><path d="M9 9.5c0 3 2.5 5.5 5.5 5.5l1-1.5-2-1-1 1a4 4 0 0 1-2-2l1-1-1-2z"/>',
  search: '<circle cx="11" cy="11" r="6.5"/><path d="m16 16 4.5 4.5"/>',
  arrow: '<path d="M5 12h14M13 6l6 6-6 6"/>',
  alert: '<circle cx="12" cy="12" r="9"/><path d="M12 7.5v5.5M12 16.5v.01"/>',
  grid: '<rect x="4" y="4" width="7" height="7" rx="1.5"/><rect x="13" y="4" width="7" height="7" rx="1.5"/><rect x="4" y="13" width="7" height="7" rx="1.5"/><rect x="13" y="13" width="7" height="7" rx="1.5"/>',
  layers: '<path d="m12 3 9 5-9 5-9-5z"/><path d="m3 13 9 5 9-5"/>',
  card: '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h3"/>',
  users: '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18 14a6.5 6.5 0 0 1 3.5 6"/>',
  router: '<rect x="3" y="13" width="18" height="7" rx="2"/><path d="M7 16.5h.01M11 16.5h.01M15 13V9M9.5 7a4 4 0 0 1 5.5 0M7.5 4.5a7 7 0 0 1 9.5 0"/>',
  gear: '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.6 1.7 1.7 0 0 0-1.8.3l-.1.1A2 2 0 1 1 4.3 17l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.6-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1A2 2 0 1 1 7 4.3l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1A2 2 0 1 1 19.7 7l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>',
  logout: '<path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3M10 17l5-5-5-5M15 12H4"/>',
  plus: '<path d="M12 5v14M5 12h14"/>',
  printer: '<path d="M7 9V3h10v6"/><rect x="3" y="9" width="18" height="8" rx="2"/><rect x="7" y="14" width="10" height="7"/>',
  download: '<path d="M12 4v11M7 10l5 5 5-5M5 20h14"/>',
  trash: '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>',
  menu: '<path d="M4 7h16M4 12h16M4 17h16"/>',
  trend: '<path d="m3 17 6-6 4 4 8-8"/><path d="M15 7h6v6"/>',
};
function icon(name, cls = '') {
  return raw(`<svg class="i ${cls}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${ICONS[name] || ''}</svg>`);
}

function page({ title, body, bodyClass = '', head = '' }) {
  return `<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0B0F2A">
<title>${esc(title)}</title>
<link rel="icon" href="/static/img/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="/static/css/app.css">
${head}
</head>
<body class="${esc(bodyClass)}">
${render(body)}
</body>
</html>`;
}

module.exports = { esc, raw, html, money, duration, dataLabel, speedLabel, bytes, remaining, dateTime, logo, icon, page, LOGO_MARK };
