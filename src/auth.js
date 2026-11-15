'use strict';
// Admin authentication: scrypt password hashes + HMAC-signed session cookie.
const crypto = require('node:crypto');
const config = require('./config');
const settings = require('./settings');

const COOKIE = 'zx_admin';
const SESSION_HOURS = 12;

function hashPassword(password) {
  const salt = crypto.randomBytes(16);
  const hash = crypto.scryptSync(String(password), salt, 64);
  return `scrypt$${salt.toString('hex')}$${hash.toString('hex')}`;
}

function verifyPassword(password, stored) {
  if (!stored || !stored.startsWith('scrypt$')) return false;
  const [, saltHex, hashHex] = stored.split('$');
  const hash = crypto.scryptSync(String(password), Buffer.from(saltHex, 'hex'), 64);
  const expected = Buffer.from(hashHex, 'hex');
  return expected.length === hash.length && crypto.timingSafeEqual(hash, expected);
}

const secret = () => config.sessionSecret || settings.get('session_secret');
const sign = (data) => crypto.createHmac('sha256', secret()).update(data).digest('base64url');

function hasAdmin() {
  return Boolean(settings.get('admin_password_hash'));
}

/** Seed the admin account from ADMIN_PASSWORD if provided and none exists yet. */
function init() {
  if (!hasAdmin() && config.adminPassword) {
    settings.set({ admin_username: config.adminUser, admin_password_hash: hashPassword(config.adminPassword) });
  }
}

function createAdmin(username, password) {
  settings.set({ admin_username: username, admin_password_hash: hashPassword(password) });
}

function checkLogin(username, password) {
  const s = settings.all();
  const userOk = String(username || '').toLowerCase() === String(s.admin_username || '').toLowerCase();
  const passOk = verifyPassword(password, s.admin_password_hash);
  return userOk && passOk;
}

function login(req, res, username) {
  // Version ties sessions to the current password hash, so changing it logs everyone out.
  const payload = Buffer.from(JSON.stringify({
    u: username, exp: Date.now() + SESSION_HOURS * 3600e3, v: settings.get('admin_password_hash').slice(-12),
  })).toString('base64url');
  res.cookie(COOKIE, `${payload}.${sign(payload)}`, {
    httpOnly: true, sameSite: 'lax', secure: req.secure, maxAge: SESSION_HOURS * 3600e3, path: '/',
  });
}

function logout(res) {
  res.clearCookie(COOKIE, { path: '/' });
}

function current(req) {
  const raw = req.cookies?.[COOKIE];
  if (!raw) return null;
  const [payload, sig] = raw.split('.');
  if (!payload || !sig) return null;
  const expected = sign(payload);
  if (expected.length !== sig.length || !crypto.timingSafeEqual(Buffer.from(expected), Buffer.from(sig))) return null;
  try {
    const data = JSON.parse(Buffer.from(payload, 'base64url').toString('utf8'));
    if (data.exp < Date.now()) return null;
    if (data.v !== String(settings.get('admin_password_hash') || '').slice(-12)) return null;
    return data;
  } catch {
    return null;
  }
}

// Simple in-memory brute-force protection for the login form.
const attempts = new Map();
function tooManyAttempts(ip) {
  const a = attempts.get(ip);
  if (!a) return false;
  if (Date.now() - a.first > 15 * 60e3) {
    attempts.delete(ip);
    return false;
  }
  return a.count >= 8;
}
function recordFailure(ip) {
  const a = attempts.get(ip) || { count: 0, first: Date.now() };
  a.count++;
  attempts.set(ip, a);
}
function clearFailures(ip) {
  attempts.delete(ip);
}

module.exports = {
  hashPassword, verifyPassword, hasAdmin, init, createAdmin, checkLogin, login, logout, current,
  tooManyAttempts, recordFailure, clearFailures,
};
