'use strict';
// Voucher lifecycle: generation, login authorization and usage accounting.
const crypto = require('node:crypto');
const db = require('../db');

const ALPHABET = '23456789ABCDEFGHJKMNPQRSTUVWXYZ'; // no 0/O, 1/I/L
const CODE_LENGTH = 8;
const INTERIM_SECONDS = 300;
const STALE_SESSION_MS = 20 * 60 * 1000;

function normalizeCode(input) {
  return String(input || '').toUpperCase().replace(/[^A-Z0-9]/g, '');
}

function formatCode(code) {
  const c = normalizeCode(code);
  return c.length === 8 ? `${c.slice(0, 4)}-${c.slice(4)}` : c;
}

function randomCode() {
  let s = '';
  for (let i = 0; i < CODE_LENGTH; i++) s += ALPHABET[crypto.randomInt(ALPHABET.length)];
  return s;
}

function createVoucher({ planId, source = 'online', batch = null, phone = null }) {
  const d = db.get();
  const insert = d.prepare('INSERT INTO vouchers (code, plan_id, source, batch, phone, created_at) VALUES (?, ?, ?, ?, ?, ?)');
  for (let attempt = 0; attempt < 10; attempt++) {
    const code = randomCode();
    try {
      const r = insert.run(code, planId, source, batch, phone, Date.now());
      return d.prepare('SELECT * FROM vouchers WHERE id = ?').get(Number(r.lastInsertRowid));
    } catch (err) {
      if (!String(err.message).includes('UNIQUE')) throw err;
    }
  }
  throw new Error('Could not generate a unique voucher code');
}

function findByCode(code) {
  return db.get().prepare(`SELECT v.*, p.name AS plan_name, p.duration_min, p.data_mb, p.download_mbps,
      p.upload_mbps, p.devices, p.price
    FROM vouchers v JOIN plans p ON p.id = v.plan_id WHERE v.code = ?`).get(normalizeCode(code));
}

/** Snapshot of a voucher's remaining entitlement (for portal status pages). */
function describe(v, now = Date.now()) {
  if (!v) return null;
  const dataCap = v.data_mb > 0 ? v.data_mb * 1024 * 1024 : 0;
  let state = v.status;
  if (state === 'active' && v.expires_at && v.expires_at <= now) state = 'expired';
  if (state === 'active' && dataCap && v.bytes_used >= dataCap) state = 'exhausted';
  return {
    code: v.code,
    display: formatCode(v.code),
    plan: v.plan_name,
    state,
    activatedAt: v.activated_at,
    expiresAt: v.expires_at,
    remainingMs: v.expires_at ? Math.max(0, v.expires_at - now) : v.duration_min * 60000,
    durationMin: v.duration_min,
    dataCapBytes: dataCap,
    bytesUsed: v.bytes_used,
    devices: v.devices,
    devicesUsed: JSON.parse(v.macs || '[]').length,
  };
}

function logAuth(username, mac, result, reason) {
  db.get().prepare('INSERT INTO auth_log (username, mac, result, reason, created_at) VALUES (?, ?, ?, ?, ?)')
    .run(username || '', mac || '', result, reason || '', Date.now());
}

/**
 * Decide whether a login is allowed. `checkPassword(code)` verifies the credential
 * the NAS sent (PAP or CHAP) against the voucher code.
 * Returns { ok, reason, voucher, sessionTimeout, rateLimit, totalLimitBytes, interim }.
 */
function authorize({ username, checkPassword, mac }) {
  const code = normalizeCode(username);
  const normMac = (mac || '').toUpperCase();
  const now = Date.now();

  return db.tx((d) => {
    const v = d.prepare(`SELECT v.*, p.duration_min, p.data_mb, p.download_mbps, p.upload_mbps, p.devices
      FROM vouchers v JOIN plans p ON p.id = v.plan_id WHERE v.code = ?`).get(code);
    const reject = (reason) => ({ ok: false, reason });

    if (!v || !checkPassword(code)) return reject('Invalid voucher code');
    if (v.status === 'disabled') return reject('This voucher has been disabled');
    if (v.status === 'expired' || (v.expires_at && v.expires_at <= now)) {
      d.prepare("UPDATE vouchers SET status = 'expired' WHERE id = ?").run(v.id);
      return reject('Your plan has expired. Please buy a new plan.');
    }
    const cap = v.data_mb > 0 ? v.data_mb * 1024 * 1024 : 0;
    if (cap && v.bytes_used >= cap) return reject('Your data bundle is exhausted. Please buy a new plan.');

    const macs = JSON.parse(v.macs || '[]');
    if (normMac && !macs.includes(normMac)) {
      if (macs.length >= Math.max(1, v.devices)) {
        return reject(v.devices > 1
          ? `This voucher is already in use on ${v.devices} devices`
          : 'This voucher is already in use on another device');
      }
      macs.push(normMac);
    }

    const activatedAt = v.activated_at || now;
    const expiresAt = v.expires_at || now + v.duration_min * 60000;
    d.prepare("UPDATE vouchers SET status = 'active', macs = ?, activated_at = ?, expires_at = ?, last_seen_at = ? WHERE id = ?")
      .run(JSON.stringify(macs), activatedAt, expiresAt, now, v.id);

    const rate = (mbps) => (mbps > 0 ? `${Math.round(mbps * 1000)}k` : '0');
    return {
      ok: true,
      voucher: { ...v, expires_at: expiresAt },
      sessionTimeout: Math.max(1, Math.ceil((expiresAt - now) / 1000)),
      // MikroTik format "rx/tx" from the router's view: upload/download of the client.
      rateLimit: v.download_mbps > 0 || v.upload_mbps > 0 ? `${rate(v.upload_mbps)}/${rate(v.download_mbps)}` : null,
      totalLimitBytes: cap ? cap - v.bytes_used : 0,
      interim: INTERIM_SECONDS,
    };
  });
}

/** Record RADIUS accounting (Start=1, Stop=2, Interim-Update=3). */
function account({ statusType, sessionId, nasId, username, mac, ip, sessionTime, inputBytes, outputBytes, terminateCause }) {
  if (!sessionId) return;
  const now = Date.now();
  const code = normalizeCode(username);
  db.tx((d) => {
    const v = d.prepare('SELECT id FROM vouchers WHERE code = ?').get(code);
    const existing = d.prepare('SELECT id FROM sessions WHERE acct_session_id = ? AND nas_id = ?').get(sessionId, nasId || '');
    const startedAt = now - (sessionTime || 0) * 1000;
    if (!existing) {
      d.prepare(`INSERT INTO sessions (acct_session_id, nas_id, voucher_id, username, mac, ip, started_at, updated_at,
          session_time, input_bytes, output_bytes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`)
        .run(sessionId, nasId || '', v ? v.id : null, code, (mac || '').toUpperCase(), ip || '', startedAt, now,
          sessionTime || 0, inputBytes || 0, outputBytes || 0);
    } else {
      d.prepare(`UPDATE sessions SET updated_at = ?, session_time = MAX(session_time, ?), input_bytes = MAX(input_bytes, ?),
          output_bytes = MAX(output_bytes, ?), ip = COALESCE(NULLIF(?, ''), ip) WHERE id = ?`)
        .run(now, sessionTime || 0, inputBytes || 0, outputBytes || 0, ip || '', existing.id);
    }
    if (statusType === 2) {
      d.prepare('UPDATE sessions SET stopped_at = ?, terminate_cause = ? WHERE acct_session_id = ? AND nas_id = ?')
        .run(now, terminateCause || '', sessionId, nasId || '');
    }
    if (v) {
      const used = d.prepare('SELECT COALESCE(SUM(input_bytes + output_bytes), 0) AS b FROM sessions WHERE voucher_id = ?').get(v.id).b;
      d.prepare('UPDATE vouchers SET bytes_used = ?, last_seen_at = ? WHERE id = ?').run(used, now, v.id);
    }
  });
}

/** Periodic housekeeping: close stale sessions and mark expired vouchers. */
function housekeeping() {
  const d = db.get();
  const now = Date.now();
  d.prepare("UPDATE sessions SET stopped_at = updated_at, terminate_cause = 'Stale' WHERE stopped_at IS NULL AND updated_at < ?")
    .run(now - STALE_SESSION_MS);
  d.prepare("UPDATE vouchers SET status = 'expired' WHERE status = 'active' AND expires_at IS NOT NULL AND expires_at <= ?").run(now);
}

module.exports = {
  normalizeCode,
  formatCode,
  createVoucher,
  findByCode,
  describe,
  authorize,
  account,
  housekeeping,
  logAuth,
  STALE_SESSION_MS,
};
