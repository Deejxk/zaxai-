'use strict';
const express = require('express');
const db = require('../db');
const config = require('../config');
const settings = require('../settings');
const hotspot = require('../hotspot');
const payments = require('../services/payments');
const vouchers = require('../services/vouchers');
const views = require('../views/portal');

const router = express.Router();

const activePlans = () => db.get().prepare('SELECT * FROM plans WHERE active = 1 ORDER BY sort_order, price').all();
const getPlan = (id) => db.get().prepare('SELECT * FROM plans WHERE id = ? AND active = 1').get(Number(id));

function baseUrl(req) {
  return config.publicUrl || `${req.protocol}://${req.get('host')}`;
}

/** Where the router sends the user after login: our "connected" page. */
function connectedUrl(req, code) {
  return `${baseUrl(req)}/connected?code=${encodeURIComponent(code)}`;
}

const MIKROTIK_ERRORS = [
  [/expired/i, 'Your plan has expired. Please buy a new plan.'],
  [/exhausted|limit/i, 'Your data bundle is exhausted. Please buy a new plan.'],
  [/another device|in use/i, 'This voucher is already in use on another device.'],
  [/invalid|not found|password/i, 'That voucher code is not valid. Please check and try again.'],
];
function friendlyError(msg) {
  if (!msg) return '';
  for (const [re, text] of MIKROTIK_ERRORS) if (re.test(msg)) return text;
  return String(msg).slice(0, 160);
}

// Landing page. MikroTik's login.html redirects here with the device context.
router.get(['/', '/portal'], (req, res) => {
  const s = settings.all();
  let ctx = hotspot.read(req);
  if (req.query.mac || req.query['link-login-only']) {
    ctx = hotspot.fromQuery(req.query);
    hotspot.write(res, ctx);
  }
  res.send(views.home(s, {
    plans: activePlans(),
    ctx,
    error: friendlyError(req.query.error),
    tab: req.query.tab,
  }));
});

router.get('/checkout/:id', (req, res) => {
  const plan = getPlan(req.params.id);
  if (!plan) return res.redirect('/');
  const s = settings.all();
  res.send(views.checkout(s, { plan, ctx: hotspot.read(req), demo: !settings.paystackReady() }));
});

router.post('/checkout/:id', async (req, res) => {
  const plan = getPlan(req.params.id);
  if (!plan) return res.redirect('/');
  const s = settings.all();
  const ctx = hotspot.read(req);
  const phone = payments.normalizePhone(req.body.phone);
  let email = String(req.body.email || '').trim().toLowerCase();
  const demo = !settings.paystackReady();
  const fail = (error) => res.status(400).send(views.checkout(s, { plan, ctx, error, demo, values: req.body }));

  if (!payments.isValidPhone(phone)) return fail('Please enter a valid Nigerian phone number, e.g. 08031234567.');
  if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return fail('Please enter a valid email address or leave it empty.');
  // Paystack requires an email; customers without one get a placeholder tied to their phone.
  if (!email) email = `${phone}@customers.zaxconnect.ng`;

  if (demo && s.demo_payments !== '1') return fail('Online payment is not available yet. Please buy a voucher from the attendant.');

  const order = payments.createOrder({
    plan, phone, email, mac: ctx.mac, ip: ctx.ip, loginUrl: ctx.loginUrl, dst: ctx.dst, gateway: demo ? 'demo' : 'paystack',
  });

  if (demo) return res.redirect(`/pay/demo/${order.reference}`);
  try {
    const url = await payments.initialize(order, `${baseUrl(req)}/pay/callback`);
    res.redirect(303, url);
  } catch (err) {
    console.error('[paystack] initialize failed:', err.message);
    payments.markFailed(order.reference);
    fail('We could not reach the payment provider. Please try again in a moment.');
  }
});

router.get('/pay/demo/:ref', (req, res) => {
  const order = payments.getOrder(req.params.ref);
  if (!order || order.gateway !== 'demo') return res.redirect('/');
  if (order.status !== 'pending') return res.redirect(`/pay/callback?reference=${order.reference}`);
  res.send(views.demoPay(settings.all(), { order }));
});

router.post('/pay/demo/:ref', (req, res) => {
  const order = payments.getOrder(req.params.ref);
  if (!order || order.gateway !== 'demo' || settings.paystackReady()) return res.redirect('/');
  if (order.status === 'pending') {
    if (req.body.result === 'success') payments.fulfill(order.reference, { channel: 'demo' });
    else payments.markFailed(order.reference);
  }
  res.redirect(`/pay/callback?reference=${order.reference}`);
});

// Paystack redirects here after checkout (also used for our own status checks).
router.get('/pay/callback', async (req, res) => {
  const s = settings.all();
  const ref = String(req.query.reference || req.query.trxref || '');
  let order = payments.getOrder(ref);
  if (!order) return res.redirect('/');
  if (order.status === 'pending' && order.gateway === 'paystack') {
    try {
      order = await payments.verify(ref);
    } catch (err) {
      console.error('[paystack] verify failed:', err.message);
    }
  }
  if (order.status === 'success') return res.redirect(`/success/${order.reference}`);
  if (order.status === 'failed') return res.send(views.failed(s, { order }));
  res.send(views.pending(s, { order }));
});

router.get('/success/:ref', (req, res) => {
  const order = payments.getOrder(req.params.ref);
  if (!order || order.status !== 'success') return res.redirect('/');
  const ctx = hotspot.read(req);
  const loginCtx = ctx.loginUrl ? ctx : { loginUrl: hotspot.isAllowedLoginUrl(order.login_url) ? order.login_url : '' };
  const voucher = { code: order.voucher_code, display: vouchers.formatCode(order.voucher_code) };
  res.send(views.success(settings.all(), {
    order,
    voucher,
    connectUrl: hotspot.loginLink(loginCtx, order.voucher_code, connectedUrl(req, order.voucher_code)),
  }));
});

// Voucher login (no-JS fallback; the portal script checks the code first via the API).
router.post('/voucher', (req, res) => {
  const code = vouchers.normalizeCode(req.body.code);
  const v = vouchers.describe(vouchers.findByCode(code));
  if (!v) return res.redirect(`/?tab=voucher&error=${encodeURIComponent('That voucher code is not valid.')}`);
  const link = hotspot.loginLink(hotspot.read(req), code, connectedUrl(req, code));
  if (link && ['unused', 'active'].includes(v.state)) return res.redirect(link);
  res.redirect(`/status?code=${code}`);
});

router.get('/status', (req, res) => {
  const s = settings.all();
  const code = vouchers.normalizeCode(req.query.code);
  if (!code) return res.send(views.statusPage(s, {}));
  const info = vouchers.describe(vouchers.findByCode(code));
  const connectUrl = hotspot.loginLink(hotspot.read(req), code, connectedUrl(req, code));
  res.send(views.statusPage(s, { code: vouchers.formatCode(code), info, connectUrl, error: info ? '' : 'We could not find that voucher code.' }));
});

// After a successful router login.
router.get('/connected', (req, res) => {
  const s = settings.all();
  const code = vouchers.normalizeCode(req.query.code || req.query.username);
  const info = code ? vouchers.describe(vouchers.findByCode(code)) : null;
  res.send(views.statusPage(s, { code: info ? info.display : '', info, online: true }));
});

router.get('/recover', (req, res) => {
  res.send(views.recover(settings.all(), { ctx: hotspot.read(req) }));
});

router.post('/recover', (req, res) => {
  const s = settings.all();
  const ctx = hotspot.read(req);
  const phone = payments.normalizePhone(req.body.phone);
  const ref = String(req.body.reference || '').trim().toUpperCase();
  if (!payments.isValidPhone(phone)) {
    return res.status(400).send(views.recover(s, { ctx, phone: req.body.phone, ref, error: 'Please enter a valid phone number.' }));
  }
  const rows = db.get().prepare(`SELECT t.reference, t.mac, t.paid_at, v.code, p.name AS plan_name
    FROM transactions t JOIN vouchers v ON v.id = t.voucher_id JOIN plans p ON p.id = t.plan_id
    WHERE t.phone = ? AND t.status = 'success' ORDER BY t.paid_at DESC LIMIT 20`).all(phone);
  const results = rows
    .filter((r) => (ref && r.reference === ref) || (ctx.mac && r.mac && r.mac === ctx.mac))
    .map((r) => ({ ...r, display: vouchers.formatCode(r.code) }));
  res.send(views.recover(s, {
    ctx, phone, ref, results,
    error: results.length ? '' : 'No vouchers found. Check the phone number, or enter the payment reference from your receipt.',
  }));
});

// --- JSON API used by the portal script ---------------------------------------------------
router.get('/api/order/:ref', (req, res) => {
  const o = payments.getOrder(req.params.ref);
  res.json(o ? { status: o.status } : { status: 'unknown' });
});

router.post('/api/voucher/check', express.json({ limit: '10kb' }), (req, res) => {
  const code = vouchers.normalizeCode(req.body?.code);
  const info = vouchers.describe(vouchers.findByCode(code));
  if (!info) return res.status(404).json({ ok: false, message: 'That voucher code is not valid. Please check and try again.' });
  const messages = {
    expired: 'This voucher has expired. Please buy a new plan.',
    exhausted: 'This voucher has used up its data. Please buy a new plan.',
    disabled: 'This voucher has been disabled. Please contact support.',
  };
  if (messages[info.state]) return res.status(409).json({ ok: false, message: messages[info.state] });
  const link = hotspot.loginLink(hotspot.read(req), code, connectedUrl(req, code));
  res.json({
    ok: true,
    loginUrl: link || null,
    statusUrl: `/status?code=${code}`,
    message: link ? 'Connecting…' : 'Voucher is valid. Connect to the hotspot Wi-Fi to use it.',
  });
});

// Paystack webhook (configure in Paystack dashboard → Settings → API Keys & Webhooks).
router.post('/paystack/webhook', express.raw({ type: '*/*', limit: '1mb' }), (req, res) => {
  const body = Buffer.isBuffer(req.body) ? req.body : Buffer.from('');
  if (!payments.verifyWebhookSignature(body, req.get('x-paystack-signature'))) return res.sendStatus(401);
  let event;
  try {
    event = JSON.parse(body.toString('utf8'));
  } catch {
    return res.sendStatus(400);
  }
  if (event.event === 'charge.success' && event.data && event.data.reference) {
    payments.verify(event.data.reference).catch((err) => console.error('[paystack] webhook verify failed:', err.message));
  }
  res.sendStatus(200);
});

module.exports = router;
