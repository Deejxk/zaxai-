'use strict';
const express = require('express');
const crypto = require('node:crypto');
const db = require('../db');
const config = require('../config');
const settings = require('../settings');
const auth = require('../auth');
const vouchers = require('../services/vouchers');
const mikrotik = require('../services/mikrotik');
const views = require('../views/admin');

const router = express.Router();
const DAY = 86400000;

// Lagos is UTC+1 all year (no DST): compute local-day boundaries.
const LAGOS_OFFSET = 3600000;
const startOfDay = (ts = Date.now()) => Math.floor((ts + LAGOS_OFFSET) / DAY) * DAY - LAGOS_OFFSET;
function startOfMonth() {
  const d = new Date(Date.now() + LAGOS_OFFSET);
  return Date.UTC(d.getUTCFullYear(), d.getUTCMonth(), 1) - LAGOS_OFFSET;
}

function publicUrl(req) {
  return config.publicUrl || `${req.protocol}://${req.get('host')}`;
}

// Flash messages via a short cookie.
function flash(res, text, type = 'success') {
  res.cookie('zx_flash', Buffer.from(JSON.stringify({ text, type })).toString('base64url'), { maxAge: 15000, httpOnly: true, sameSite: 'lax', path: '/admin' });
}
function takeFlash(req, res) {
  const raw = req.cookies?.zx_flash;
  if (!raw) return null;
  res.clearCookie('zx_flash', { path: '/admin' });
  try { return JSON.parse(Buffer.from(raw, 'base64url').toString('utf8')); } catch { return null; }
}

// CSRF defence: admin POSTs must come from our own origin.
router.use((req, res, next) => {
  if (req.method !== 'POST') return next();
  const origin = req.get('origin') || req.get('referer');
  if (origin) {
    try {
      if (new URL(origin).host !== req.get('host')) return res.status(403).send('Cross-site request blocked');
    } catch {
      return res.status(403).send('Bad origin');
    }
  }
  next();
});

router.get('/setup', (req, res) => {
  if (auth.hasAdmin()) return res.redirect('/admin/login');
  res.send(views.setup(settings.all(), {}));
});

router.post('/setup', (req, res) => {
  if (auth.hasAdmin()) return res.redirect('/admin/login');
  const { username, password, confirm, business_name: name } = req.body;
  const err = (e) => res.status(400).send(views.setup(settings.all(), { error: e }));
  if (!username || !/^[a-zA-Z0-9_.-]{3,32}$/.test(username)) return err('Username must be 3–32 letters, numbers, dots or dashes.');
  if (!password || password.length < 8) return err('Password must be at least 8 characters.');
  if (password !== confirm) return err('Passwords do not match.');
  if (name) settings.set({ business_name: String(name).slice(0, 60) });
  auth.createAdmin(username, password);
  auth.login(req, res, username);
  res.redirect('/admin');
});

router.get('/login', (req, res) => {
  if (!auth.hasAdmin()) return res.redirect('/admin/setup');
  if (auth.current(req)) return res.redirect('/admin');
  res.send(views.login(settings.all(), {}));
});

router.post('/login', (req, res) => {
  const ip = req.ip;
  if (auth.tooManyAttempts(ip)) {
    return res.status(429).send(views.login(settings.all(), { error: 'Too many attempts. Please wait 15 minutes and try again.' }));
  }
  if (!auth.checkLogin(req.body.username, req.body.password)) {
    auth.recordFailure(ip);
    return res.status(401).send(views.login(settings.all(), { error: 'Incorrect username or password.' }));
  }
  auth.clearFailures(ip);
  auth.login(req, res, settings.get('admin_username'));
  res.redirect('/admin');
});

router.post('/logout', (req, res) => {
  auth.logout(res);
  res.redirect('/admin/login');
});

// Everything below requires a signed-in admin.
router.use((req, res, next) => {
  if (!auth.hasAdmin()) return res.redirect('/admin/setup');
  const user = auth.current(req);
  if (!user) return res.redirect('/admin/login');
  req.user = user;
  next();
});

router.get('/', (req, res) => {
  const d = db.get();
  const s = settings.all();
  const sumSince = (ts) => d.prepare("SELECT COALESCE(SUM(amount),0) AS total, COUNT(*) AS count FROM transactions WHERE status='success' AND paid_at >= ?").get(ts);
  const today0 = startOfDay();
  const days = [];
  const rows = d.prepare("SELECT paid_at, amount FROM transactions WHERE status='success' AND paid_at >= ?").all(today0 - 13 * DAY);
  for (let i = 13; i >= 0; i--) {
    const start = today0 - i * DAY;
    const inDay = rows.filter((r) => r.paid_at >= start && r.paid_at < start + DAY);
    const date = new Date(start + LAGOS_OFFSET);
    days.push({
      label: date.toLocaleDateString('en-NG', { timeZone: 'UTC', weekday: 'short', day: 'numeric', month: 'short' }),
      short: date.toLocaleDateString('en-NG', { timeZone: 'UTC', day: 'numeric', month: 'short' }),
      total: inDay.reduce((a, r) => a + r.amount, 0),
      count: inDay.length,
    });
  }
  const online = d.prepare('SELECT COUNT(*) AS n FROM sessions WHERE stopped_at IS NULL AND updated_at >= ?').get(Date.now() - vouchers.STALE_SESSION_MS).n;
  const lastAuth = d.prepare('SELECT MAX(created_at) AS t FROM auth_log').get().t;
  res.send(views.dashboard(s, {
    user: req.user,
    flash: takeFlash(req, res),
    today: sumSince(today0),
    month: sumSince(startOfMonth()),
    days,
    online,
    activeVouchers: d.prepare("SELECT COUNT(*) AS n FROM vouchers WHERE status='active'").get().n,
    unusedVouchers: d.prepare("SELECT COUNT(*) AS n FROM vouchers WHERE status='unused'").get().n,
    topPlans: d.prepare(`SELECT p.name, SUM(t.amount) AS total, COUNT(*) AS count FROM transactions t JOIN plans p ON p.id = t.plan_id
      WHERE t.status='success' AND t.paid_at >= ? GROUP BY p.id ORDER BY total DESC LIMIT 5`).all(Date.now() - 30 * DAY),
    recentSales: d.prepare(`SELECT t.*, p.name AS plan_name FROM transactions t JOIN plans p ON p.id = t.plan_id
      ORDER BY t.created_at DESC LIMIT 8`).all(),
    authLog: d.prepare('SELECT * FROM auth_log ORDER BY id DESC LIMIT 8').all(),
    checks: { paystack: settings.paystackReady(), server: Boolean(s.server_ip && s.portal_host), router: Boolean(lastAuth) },
  }));
});

// --- Plans -------------------------------------------------------------------------------
function planFromBody(b) {
  const mult = { minutes: 1, hours: 60, days: 1440 }[b.duration_unit] || 1;
  const num = (v, def = 0) => (Number.isFinite(Number(v)) && v !== '' ? Number(v) : def);
  return {
    name: String(b.name || '').trim().slice(0, 60),
    description: String(b.description || '').trim().slice(0, 120),
    price: Math.round(num(b.price, -1)),
    duration_min: Math.round(num(b.duration_value) * mult),
    data_mb: Math.max(0, Math.round(num(b.data_mb))),
    download_mbps: Math.max(0, num(b.download_mbps)),
    upload_mbps: Math.max(0, num(b.upload_mbps)),
    devices: Math.min(10, Math.max(1, Math.round(num(b.devices, 1)))),
    popular: b.popular ? 1 : 0,
    active: b.active ? 1 : 0,
    sort_order: Math.round(num(b.sort_order)),
  };
}
function planError(p) {
  if (!p.name) return 'Plan name is required.';
  if (p.price < 0) return 'Enter a valid price.';
  if (p.duration_min < 1) return 'Validity must be at least 1 minute.';
  return '';
}

router.get('/plans', (req, res) => {
  const rows = db.get().prepare(`SELECT p.*, (SELECT COUNT(*) FROM transactions t WHERE t.plan_id = p.id AND t.status='success') AS sold
    FROM plans p ORDER BY p.sort_order, p.price`).all();
  res.send(views.plans(settings.all(), { rows, user: req.user, flash: takeFlash(req, res) }));
});

router.get('/plans/new', (req, res) => res.send(views.planForm(settings.all(), { user: req.user })));

router.post('/plans', (req, res) => {
  const p = planFromBody(req.body);
  const error = planError(p);
  if (error) return res.status(400).send(views.planForm(settings.all(), { user: req.user, error }));
  db.get().prepare(`INSERT INTO plans (name, description, price, duration_min, data_mb, download_mbps, upload_mbps, devices, popular, active, sort_order, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`)
    .run(p.name, p.description, p.price, p.duration_min, p.data_mb, p.download_mbps, p.upload_mbps, p.devices, p.popular, p.active, p.sort_order, Date.now());
  flash(res, `Plan “${p.name}” created.`);
  res.redirect('/admin/plans');
});

router.get('/plans/:id', (req, res) => {
  const plan = db.get().prepare('SELECT * FROM plans WHERE id = ?').get(Number(req.params.id));
  if (!plan) return res.redirect('/admin/plans');
  res.send(views.planForm(settings.all(), { plan, user: req.user }));
});

router.post('/plans/:id', (req, res) => {
  const plan = db.get().prepare('SELECT * FROM plans WHERE id = ?').get(Number(req.params.id));
  if (!plan) return res.redirect('/admin/plans');
  const p = planFromBody(req.body);
  const error = planError(p);
  if (error) return res.status(400).send(views.planForm(settings.all(), { plan: { ...plan, ...p }, user: req.user, error }));
  db.get().prepare(`UPDATE plans SET name=?, description=?, price=?, duration_min=?, data_mb=?, download_mbps=?, upload_mbps=?, devices=?, popular=?, active=?, sort_order=? WHERE id=?`)
    .run(p.name, p.description, p.price, p.duration_min, p.data_mb, p.download_mbps, p.upload_mbps, p.devices, p.popular, p.active, p.sort_order, plan.id);
  flash(res, `Plan “${p.name}” saved. Changes apply to new logins.`);
  res.redirect('/admin/plans');
});

router.post('/plans/:id/toggle', (req, res) => {
  db.get().prepare('UPDATE plans SET active = 1 - active WHERE id = ?').run(Number(req.params.id));
  res.redirect('/admin/plans');
});

// --- Vouchers ----------------------------------------------------------------------------
router.get('/vouchers', (req, res) => {
  const status = ['unused', 'active', 'expired', 'disabled'].includes(req.query.status) ? req.query.status : 'all';
  const q = String(req.query.q || '').trim();
  const where = [];
  const params = [];
  if (status !== 'all') { where.push('v.status = ?'); params.push(status); }
  if (q) {
    where.push('(v.code LIKE ? OR v.phone LIKE ? OR v.batch LIKE ?)');
    const like = `%${vouchers.normalizeCode(q) || q}%`;
    params.push(like, `%${q}%`, `%${q}%`);
  }
  const rows = db.get().prepare(`SELECT v.*, p.name AS plan_name, p.devices FROM vouchers v JOIN plans p ON p.id = v.plan_id
    ${where.length ? `WHERE ${where.join(' AND ')}` : ''} ORDER BY v.id DESC LIMIT 200`).all(...params)
    .map((v) => ({ ...v, display: vouchers.formatCode(v.code) }));
  const batches = db.get().prepare(`SELECT v.batch, p.name AS plan_name, COUNT(*) AS count, SUM(v.status='unused') AS unused, MAX(v.created_at) AS created
    FROM vouchers v JOIN plans p ON p.id = v.plan_id WHERE v.batch IS NOT NULL GROUP BY v.batch ORDER BY created DESC LIMIT 8`).all();
  const plans = db.get().prepare('SELECT * FROM plans ORDER BY sort_order, price').all();
  res.send(views.voucherList(settings.all(), { rows, plans, batches, user: req.user, filter: { status, q }, flash: takeFlash(req, res) }));
});

router.post('/vouchers/generate', (req, res) => {
  const plan = db.get().prepare('SELECT * FROM plans WHERE id = ?').get(Number(req.body.plan_id));
  const qty = Math.min(500, Math.max(1, Math.round(Number(req.body.quantity) || 0)));
  if (!plan) return res.redirect('/admin/vouchers');
  const d = new Date(Date.now() + LAGOS_OFFSET);
  const batch = `${String(d.getUTCDate()).padStart(2, '0')}${String(d.getUTCMonth() + 1).padStart(2, '0')}-${crypto.randomBytes(2).toString('hex').toUpperCase()}`;
  db.tx(() => {
    for (let i = 0; i < qty; i++) vouchers.createVoucher({ planId: plan.id, source: 'batch', batch });
  });
  flash(res, `${qty} “${plan.name}” vouchers generated in batch ${batch}.`);
  res.redirect(`/admin/vouchers?q=${batch}`);
});

router.get('/vouchers/print/:batch', (req, res) => {
  const rows = db.get().prepare(`SELECT v.*, p.name AS plan_name, p.price, p.duration_min, p.data_mb FROM vouchers v JOIN plans p ON p.id = v.plan_id
    WHERE v.batch = ? ORDER BY v.id`).all(String(req.params.batch))
    .map((v) => ({ ...v, display: vouchers.formatCode(v.code) }));
  res.send(views.printCards(settings.all(), { batch: req.params.batch, rows }));
});

router.post('/vouchers/:id/toggle', (req, res) => {
  const v = db.get().prepare('SELECT * FROM vouchers WHERE id = ?').get(Number(req.params.id));
  if (v) {
    const next = v.status === 'disabled' ? (v.activated_at ? 'active' : 'unused') : 'disabled';
    db.get().prepare('UPDATE vouchers SET status = ? WHERE id = ?').run(next, v.id);
    flash(res, `Voucher ${vouchers.formatCode(v.code)} ${next === 'disabled' ? 'disabled' : 'enabled'}.`);
  }
  res.redirect(req.get('referer') || '/admin/vouchers');
});

router.post('/vouchers/:id/reset', (req, res) => {
  const v = db.get().prepare('SELECT * FROM vouchers WHERE id = ?').get(Number(req.params.id));
  if (v) {
    db.get().prepare("UPDATE vouchers SET macs = '[]' WHERE id = ?").run(v.id);
    flash(res, `Device lock cleared for ${vouchers.formatCode(v.code)}.`);
  }
  res.redirect(req.get('referer') || '/admin/vouchers');
});

// --- Sales -------------------------------------------------------------------------------
function salesQuery(query) {
  const status = ['success', 'pending', 'failed'].includes(query.status) ? query.status : 'all';
  const q = String(query.q || '').trim();
  const where = [];
  const params = [];
  if (status !== 'all') { where.push('t.status = ?'); params.push(status); }
  if (q) { where.push('(t.phone LIKE ? OR t.reference LIKE ?)'); params.push(`%${q}%`, `%${q.toUpperCase()}%`); }
  return { status, q, sql: where.length ? `WHERE ${where.join(' AND ')}` : '', params };
}

router.get('/sales', (req, res) => {
  const f = salesQuery(req.query);
  const rows = db.get().prepare(`SELECT t.*, p.name AS plan_name, v.code AS voucher_code FROM transactions t
    JOIN plans p ON p.id = t.plan_id LEFT JOIN vouchers v ON v.id = t.voucher_id ${f.sql} ORDER BY t.created_at DESC LIMIT 300`).all(...f.params)
    .map((t) => ({ ...t, voucher_code: t.voucher_code ? vouchers.formatCode(t.voucher_code) : null }));
  const totals = db.get().prepare("SELECT COALESCE(SUM(amount),0) AS total, COUNT(*) AS count FROM transactions WHERE status='success'").get();
  res.send(views.sales(settings.all(), { rows, totals, user: req.user, filter: { status: f.status, q: f.q } }));
});

router.get('/sales.csv', (req, res) => {
  const rows = db.get().prepare(`SELECT t.reference, p.name AS plan, t.amount, t.phone, t.email, t.status, t.channel, t.gateway,
      v.code AS voucher, t.created_at, t.paid_at FROM transactions t JOIN plans p ON p.id = t.plan_id
      LEFT JOIN vouchers v ON v.id = t.voucher_id ORDER BY t.created_at DESC`).all();
  const cell = (v) => {
    let s = v == null ? '' : String(v);
    if (/^[=+\-@]/.test(s)) s = `'${s}`;
    return /[",\n]/.test(s) ? `"${s.replace(/"/g, '""')}"` : s;
  };
  const iso = (t) => (t ? new Date(t).toISOString() : '');
  const lines = ['reference,plan,amount,phone,email,status,channel,gateway,voucher,created_at,paid_at'];
  for (const r of rows) lines.push([r.reference, r.plan, r.amount, r.phone, r.email, r.status, r.channel, r.gateway, r.voucher, iso(r.created_at), iso(r.paid_at)].map(cell).join(','));
  res.set('Content-Type', 'text/csv; charset=utf-8');
  res.set('Content-Disposition', 'attachment; filename="zaxconnect-sales.csv"');
  res.send(lines.join('\n'));
});

// --- Online users ------------------------------------------------------------------------
router.get('/online', (req, res) => {
  const rows = db.get().prepare(`SELECT s.*, v.expires_at, p.name AS plan_name FROM sessions s
    LEFT JOIN vouchers v ON v.id = s.voucher_id LEFT JOIN plans p ON p.id = v.plan_id
    WHERE s.stopped_at IS NULL AND s.updated_at >= ? ORDER BY s.started_at DESC`).all(Date.now() - vouchers.STALE_SESSION_MS)
    .map((r) => ({ ...r, display: vouchers.formatCode(r.username) }));
  res.send(views.online(settings.all(), { rows, user: req.user }));
});

// --- Router setup ------------------------------------------------------------------------
router.get('/router', (req, res) => {
  const s = settings.all();
  const lastSeen = db.get().prepare('SELECT MAX(created_at) AS t FROM auth_log').get().t;
  res.send(views.routerSetup(s, { user: req.user, script: mikrotik.script(s), portalUrl: publicUrl(req), lastSeen }));
});

router.get('/router/login.html', (req, res) => {
  res.set('Content-Disposition', 'attachment; filename="login.html"');
  res.type('html').send(mikrotik.loginHtml(settings.all(), config.publicUrl));
});

router.get('/router/alogin.html', (req, res) => {
  res.set('Content-Disposition', 'attachment; filename="alogin.html"');
  res.type('html').send(mikrotik.aloginHtml(settings.all(), config.publicUrl));
});

// --- Settings ----------------------------------------------------------------------------
router.get('/settings', (req, res) => {
  res.send(views.settingsPage(settings.all(), { user: req.user, flash: takeFlash(req, res), publicUrl: publicUrl(req) }));
});

router.post('/settings', (req, res) => {
  const b = req.body;
  const str = (v, max = 120) => String(v || '').trim().slice(0, max);
  const patch = {
    business_name: str(b.business_name, 60) || 'ZaxConnect',
    tagline: str(b.tagline, 140),
    hotspot_location: str(b.hotspot_location, 80),
    support_phone: str(b.support_phone, 20),
    support_whatsapp: str(b.support_whatsapp, 20),
    paystack_public_key: str(b.paystack_public_key, 200),
    demo_payments: b.demo_payments ? '1' : '0',
    portal_host: str(b.portal_host, 200).replace(/^https?:\/\//, '').replace(/\/.*$/, ''),
    server_ip: str(b.server_ip, 64),
    hotspot_dns: str(b.hotspot_dns, 100) || 'login.zaxconnect.wifi',
  };
  if (str(b.paystack_secret_key, 200)) patch.paystack_secret_key = str(b.paystack_secret_key, 200);
  const secret = str(b.radius_secret, 64);
  if (secret.length >= 8) patch.radius_secret = secret;
  if (patch.paystack_public_key && !/^pk_(test|live)_/.test(patch.paystack_public_key)) {
    flash(res, 'Paystack public key should start with pk_live_ or pk_test_.', 'danger');
    return res.redirect('/admin/settings');
  }
  if (patch.paystack_secret_key && !/^sk_(test|live)_/.test(patch.paystack_secret_key)) {
    flash(res, 'Paystack secret key should start with sk_live_ or sk_test_.', 'danger');
    return res.redirect('/admin/settings');
  }
  settings.set(patch);
  flash(res, 'Settings saved.');
  res.redirect('/admin/settings');
});

router.post('/password', (req, res) => {
  const { current, password, confirm } = req.body;
  if (!auth.verifyPassword(current, settings.get('admin_password_hash'))) {
    flash(res, 'Current password is incorrect.', 'danger');
  } else if (!password || password.length < 8 || password !== confirm) {
    flash(res, 'New passwords must match and be at least 8 characters.', 'danger');
  } else {
    auth.createAdmin(settings.get('admin_username'), password);
    auth.login(req, res, settings.get('admin_username'));
    flash(res, 'Password updated.');
  }
  res.redirect('/admin/settings');
});

module.exports = router;
