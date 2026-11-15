'use strict';
// Paystack integration and order fulfillment (payment -> voucher).
const crypto = require('node:crypto');
const db = require('../db');
const settings = require('../settings');
const vouchers = require('./vouchers');

const PAYSTACK_API = 'https://api.paystack.co';

function newReference() {
  return `ZX${Date.now().toString(36).toUpperCase()}${crypto.randomBytes(3).toString('hex').toUpperCase()}`;
}

function normalizePhone(phone) {
  let p = String(phone || '').replace(/[^0-9+]/g, '');
  if (p.startsWith('+234')) p = '0' + p.slice(4);
  else if (p.startsWith('234') && p.length === 13) p = '0' + p.slice(3);
  return p;
}

function isValidPhone(phone) {
  return /^0[789][01]\d{8}$/.test(phone);
}

function createOrder({ plan, phone, email, mac, ip, loginUrl, dst, gateway }) {
  const reference = newReference();
  db.get().prepare(`INSERT INTO transactions (reference, plan_id, amount, phone, email, mac, ip, login_url, dst, gateway, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`)
    .run(reference, plan.id, plan.price, phone, email, mac || '', ip || '', loginUrl || '', dst || '', gateway, Date.now());
  return getOrder(reference);
}

function getOrder(reference) {
  return db.get().prepare(`SELECT t.*, p.name AS plan_name, v.code AS voucher_code
    FROM transactions t JOIN plans p ON p.id = t.plan_id LEFT JOIN vouchers v ON v.id = t.voucher_id
    WHERE t.reference = ?`).get(String(reference || ''));
}

/** Mark an order paid and issue its voucher exactly once. */
function fulfill(reference, { channel = '', amountKobo = null } = {}) {
  const issued = db.tx((d) => {
    const t = d.prepare('SELECT * FROM transactions WHERE reference = ?').get(reference);
    if (!t) throw new Error('Unknown order');
    if (t.status === 'success') return t;
    if (amountKobo != null && amountKobo < t.amount * 100) throw new Error('Amount paid is less than plan price');
    const v = vouchers.createVoucher({ planId: t.plan_id, source: 'online', phone: t.phone });
    d.prepare("UPDATE transactions SET status = 'success', voucher_id = ?, channel = ?, paid_at = ? WHERE id = ?")
      .run(v.id, channel, Date.now(), t.id);
    return t;
  });
  return getOrder(issued.reference);
}

function markFailed(reference) {
  db.get().prepare("UPDATE transactions SET status = 'failed' WHERE reference = ? AND status = 'pending'").run(reference);
}

async function paystack(method, path, body) {
  const res = await fetch(PAYSTACK_API + path, {
    method,
    headers: {
      Authorization: `Bearer ${settings.get('paystack_secret_key')}`,
      'Content-Type': 'application/json',
    },
    body: body ? JSON.stringify(body) : undefined,
    signal: AbortSignal.timeout(20000),
  });
  const json = await res.json().catch(() => ({}));
  if (!res.ok || !json.status) throw new Error(json.message || `Paystack error (${res.status})`);
  return json.data;
}

/** Start a Paystack checkout. Returns the URL to redirect the customer to. */
async function initialize(order, callbackUrl) {
  const data = await paystack('POST', '/transaction/initialize', {
    email: order.email,
    amount: order.amount * 100,
    reference: order.reference,
    callback_url: callbackUrl,
    channels: ['card', 'bank_transfer', 'ussd', 'bank'],
    metadata: {
      plan: order.plan_name,
      phone: order.phone,
      custom_fields: [
        { display_name: 'Plan', variable_name: 'plan', value: order.plan_name },
        { display_name: 'Phone', variable_name: 'phone', value: order.phone },
      ],
    },
  });
  return data.authorization_url;
}

/** Verify with Paystack and fulfill when successful. */
async function verify(reference) {
  const order = getOrder(reference);
  if (!order) throw new Error('Order not found');
  if (order.status === 'success') return order;
  const data = await paystack('GET', `/transaction/verify/${encodeURIComponent(reference)}`);
  if (data.status === 'success') return fulfill(reference, { channel: data.channel, amountKobo: data.amount });
  if (['failed', 'abandoned', 'reversed'].includes(data.status)) markFailed(reference);
  return getOrder(reference);
}

function verifyWebhookSignature(rawBody, signature) {
  const secret = settings.get('paystack_secret_key');
  if (!secret || !signature) return false;
  const expected = crypto.createHmac('sha512', secret).update(rawBody).digest('hex');
  const a = Buffer.from(expected);
  const b = Buffer.from(String(signature));
  return a.length === b.length && crypto.timingSafeEqual(a, b);
}

module.exports = {
  normalizePhone,
  isValidPhone,
  createOrder,
  getOrder,
  fulfill,
  markFailed,
  initialize,
  verify,
  verifyWebhookSignature,
};
