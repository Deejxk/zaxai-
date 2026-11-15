'use strict';
// SQLite storage using Node's built-in driver (no native modules to compile).
const fs = require('node:fs');
const path = require('node:path');
const { DatabaseSync } = require('node:sqlite');
const config = require('./config');

let db;

const SCHEMA = `
CREATE TABLE IF NOT EXISTS settings (
  key   TEXT PRIMARY KEY,
  value TEXT
);

CREATE TABLE IF NOT EXISTS plans (
  id             INTEGER PRIMARY KEY AUTOINCREMENT,
  name           TEXT NOT NULL,
  description    TEXT DEFAULT '',
  price          INTEGER NOT NULL,            -- in Naira
  duration_min   INTEGER NOT NULL,            -- validity after first login, in minutes
  data_mb        INTEGER NOT NULL DEFAULT 0,  -- 0 = unlimited
  download_mbps  REAL NOT NULL DEFAULT 0,     -- 0 = unlimited
  upload_mbps    REAL NOT NULL DEFAULT 0,     -- 0 = unlimited
  devices        INTEGER NOT NULL DEFAULT 1,
  popular        INTEGER NOT NULL DEFAULT 0,
  active         INTEGER NOT NULL DEFAULT 1,
  sort_order     INTEGER NOT NULL DEFAULT 0,
  created_at     INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS vouchers (
  id             INTEGER PRIMARY KEY AUTOINCREMENT,
  code           TEXT NOT NULL UNIQUE,
  plan_id        INTEGER NOT NULL REFERENCES plans(id),
  source         TEXT NOT NULL DEFAULT 'online',  -- online | batch | manual
  batch          TEXT,
  status         TEXT NOT NULL DEFAULT 'unused',  -- unused | active | expired | disabled
  phone          TEXT,
  macs           TEXT NOT NULL DEFAULT '[]',
  bytes_used     INTEGER NOT NULL DEFAULT 0,
  activated_at   INTEGER,
  expires_at     INTEGER,
  last_seen_at   INTEGER,
  created_at     INTEGER NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_vouchers_status ON vouchers(status);
CREATE INDEX IF NOT EXISTS idx_vouchers_phone ON vouchers(phone);

CREATE TABLE IF NOT EXISTS transactions (
  id             INTEGER PRIMARY KEY AUTOINCREMENT,
  reference      TEXT NOT NULL UNIQUE,
  plan_id        INTEGER NOT NULL REFERENCES plans(id),
  amount         INTEGER NOT NULL,             -- Naira
  phone          TEXT,
  email          TEXT,
  mac            TEXT,
  ip             TEXT,
  login_url      TEXT,
  dst            TEXT,
  gateway        TEXT NOT NULL DEFAULT 'paystack', -- paystack | demo
  status         TEXT NOT NULL DEFAULT 'pending',  -- pending | success | failed
  voucher_id     INTEGER REFERENCES vouchers(id),
  channel        TEXT,
  paid_at        INTEGER,
  created_at     INTEGER NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_tx_status ON transactions(status, paid_at);

CREATE TABLE IF NOT EXISTS sessions (
  id              INTEGER PRIMARY KEY AUTOINCREMENT,
  acct_session_id TEXT NOT NULL,
  nas_id          TEXT NOT NULL DEFAULT '',
  voucher_id      INTEGER REFERENCES vouchers(id),
  username        TEXT,
  mac             TEXT,
  ip              TEXT,
  started_at      INTEGER NOT NULL,
  updated_at      INTEGER NOT NULL,
  stopped_at      INTEGER,
  session_time    INTEGER NOT NULL DEFAULT 0,
  input_bytes     INTEGER NOT NULL DEFAULT 0,
  output_bytes    INTEGER NOT NULL DEFAULT 0,
  terminate_cause TEXT,
  UNIQUE (acct_session_id, nas_id)
);
CREATE INDEX IF NOT EXISTS idx_sessions_open ON sessions(stopped_at, updated_at);

CREATE TABLE IF NOT EXISTS auth_log (
  id         INTEGER PRIMARY KEY AUTOINCREMENT,
  username   TEXT,
  mac        TEXT,
  result     TEXT NOT NULL,
  reason     TEXT,
  created_at INTEGER NOT NULL
);
`;

function open(file) {
  if (db) return db;
  const target = file || path.join(config.dataDir, 'zaxconnect.db');
  if (target !== ':memory:') fs.mkdirSync(path.dirname(target), { recursive: true });
  db = new DatabaseSync(target);
  db.exec('PRAGMA journal_mode = WAL; PRAGMA foreign_keys = ON; PRAGMA busy_timeout = 5000;');
  db.exec(SCHEMA);
  seedPlans();
  return db;
}

function get() {
  if (!db) open();
  return db;
}

function seedPlans() {
  const count = db.prepare('SELECT COUNT(*) AS n FROM plans').get().n;
  if (count > 0) return;
  const now = Date.now();
  const insert = db.prepare(`INSERT INTO plans
    (name, description, price, duration_min, data_mb, download_mbps, upload_mbps, devices, popular, sort_order, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`);
  const plans = [
    ['Quick Hour', 'Perfect for a quick browse', 100, 60, 0, 5, 2, 1, 0, 1],
    ['3 Hours', 'Stream, work and chat', 200, 180, 0, 5, 2, 1, 0, 2],
    ['Daily Pass', 'Unlimited access for a full day', 350, 1440, 0, 8, 3, 1, 1, 3],
    ['Weekly Pass', '7 days of unlimited browsing', 1500, 10080, 0, 8, 3, 2, 0, 4],
    ['Monthly Pass', '30 days, share with 2 devices', 5000, 43200, 0, 10, 4, 2, 0, 5],
    ['2GB Bundle', '2GB to use within 3 days', 500, 4320, 2048, 10, 4, 1, 0, 6],
  ];
  for (const p of plans) insert.run(...p, now);
}

/** Run fn inside a transaction. */
function tx(fn) {
  const d = get();
  d.exec('BEGIN IMMEDIATE');
  try {
    const out = fn(d);
    d.exec('COMMIT');
    return out;
  } catch (err) {
    d.exec('ROLLBACK');
    throw err;
  }
}

module.exports = { open, get, tx };
