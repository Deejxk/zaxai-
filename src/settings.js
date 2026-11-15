'use strict';
// Business settings stored in the database and editable from the admin panel.
const crypto = require('node:crypto');
const db = require('./db');

const DEFAULTS = {
  business_name: 'ZaxConnect',
  tagline: 'Fast, reliable Wi-Fi. Pay small, browse big.',
  support_phone: '',
  support_whatsapp: '',
  support_email: '',
  hotspot_location: '',
  currency_symbol: '₦',
  paystack_public_key: '',
  paystack_secret_key: '',
  radius_secret: '',
  portal_host: '',   // public domain of this portal (for MikroTik walled garden)
  server_ip: '',     // public IP of this server (for MikroTik RADIUS client)
  hotspot_dns: 'login.zaxconnect.wifi',
  demo_payments: '1', // allow simulated payments while Paystack keys are not set
};

let cache = null;

function all() {
  if (cache) return cache;
  const rows = db.get().prepare('SELECT key, value FROM settings').all();
  const out = { ...DEFAULTS };
  for (const r of rows) out[r.key] = r.value;
  cache = out;
  return out;
}

function getSetting(key) {
  return all()[key];
}

function set(values) {
  const stmt = db.get().prepare('INSERT INTO settings (key, value) VALUES (?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value');
  for (const [k, v] of Object.entries(values)) stmt.run(k, v == null ? '' : String(v));
  cache = null;
}

/** Ensure secrets exist on first boot. */
function init() {
  const s = all();
  const patch = {};
  if (!s.radius_secret) patch.radius_secret = crypto.randomBytes(12).toString('base64url');
  if (!s.session_secret) patch.session_secret = crypto.randomBytes(32).toString('hex');
  if (Object.keys(patch).length) set(patch);
}

function paystackReady() {
  const s = all();
  return Boolean(s.paystack_secret_key && s.paystack_public_key);
}

module.exports = { all, get: getSetting, set, init, paystackReady, DEFAULTS };
