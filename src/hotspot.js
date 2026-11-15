'use strict';
// Carries the MikroTik hotspot context (device MAC/IP and router login URL) through the
// purchase flow, using a short-lived cookie set when the router redirects to the portal.
const settings = require('./settings');

const COOKIE = 'zx_hs';

function isAllowedLoginUrl(value) {
  let u;
  try {
    u = new URL(value);
  } catch {
    return false;
  }
  if (!['http:', 'https:'].includes(u.protocol)) return false;
  const h = u.hostname.toLowerCase();
  const dns = String(settings.get('hotspot_dns') || '').toLowerCase();
  if (dns && h === dns) return true;
  const m = /^(\d+)\.(\d+)\.(\d+)\.(\d+)$/.exec(h);
  if (m) {
    const [a, b] = [Number(m[1]), Number(m[2])];
    return a === 10 || (a === 172 && b >= 16 && b <= 31) || (a === 192 && b === 168) || (a === 100 && b >= 64 && b <= 127);
  }
  return !h.includes('.') || h.endsWith('.wifi') || h.endsWith('.lan') || h.endsWith('.local') || h.endsWith('.hotspot');
}

const clean = (v, max = 200) => String(v || '').slice(0, max);

function fromQuery(q) {
  const ctx = {
    mac: clean(q.mac, 32).toUpperCase(),
    ip: clean(q.ip, 64),
    loginUrl: clean(q['link-login-only'] || q.login, 300),
    dst: clean(q.dst || q['link-orig'], 500),
  };
  if (ctx.loginUrl && !isAllowedLoginUrl(ctx.loginUrl)) ctx.loginUrl = '';
  if (ctx.dst && !/^https?:\/\//i.test(ctx.dst)) ctx.dst = '';
  return ctx;
}

function read(req) {
  try {
    const raw = req.cookies?.[COOKIE];
    if (!raw) return {};
    const ctx = JSON.parse(Buffer.from(raw, 'base64url').toString('utf8'));
    if (ctx.loginUrl && !isAllowedLoginUrl(ctx.loginUrl)) ctx.loginUrl = '';
    return ctx;
  } catch {
    return {};
  }
}

function write(res, ctx) {
  res.cookie(COOKIE, Buffer.from(JSON.stringify(ctx)).toString('base64url'), {
    maxAge: 6 * 3600 * 1000, httpOnly: true, sameSite: 'lax', path: '/',
  });
}

/** Build the router login URL that signs the device in with a voucher code. */
function loginLink(ctx, code, dst) {
  if (!ctx || !ctx.loginUrl) return '';
  const u = new URL(ctx.loginUrl);
  u.searchParams.set('username', code);
  u.searchParams.set('password', code);
  const target = dst || ctx.dst;
  if (target) u.searchParams.set('dst', target);
  return u.toString();
}

module.exports = { fromQuery, read, write, loginLink, isAllowedLoginUrl };
