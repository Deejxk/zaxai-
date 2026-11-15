'use strict';
// Loads configuration from environment variables (and an optional .env file).
const fs = require('node:fs');
const path = require('node:path');

const ROOT = path.resolve(__dirname, '..');

function loadDotEnv() {
  const file = path.join(ROOT, '.env');
  if (!fs.existsSync(file)) return;
  for (const raw of fs.readFileSync(file, 'utf8').split(/\r?\n/)) {
    const line = raw.trim();
    if (!line || line.startsWith('#')) continue;
    const eq = line.indexOf('=');
    if (eq === -1) continue;
    const key = line.slice(0, eq).trim();
    let value = line.slice(eq + 1).trim();
    if ((value.startsWith('"') && value.endsWith('"')) || (value.startsWith("'") && value.endsWith("'"))) {
      value = value.slice(1, -1);
    }
    if (process.env[key] === undefined) process.env[key] = value;
  }
}
loadDotEnv();

const env = process.env;

module.exports = {
  ROOT,
  port: Number(env.PORT || 3000),
  // Public base URL of this server, e.g. https://wifi.zaxconnect.com (used for Paystack callbacks).
  publicUrl: (env.PUBLIC_URL || '').replace(/\/+$/, ''),
  dataDir: path.resolve(ROOT, env.DATA_DIR || 'data'),
  sessionSecret: env.SESSION_SECRET || '',
  adminUser: env.ADMIN_USERNAME || 'admin',
  adminPassword: env.ADMIN_PASSWORD || '',
  radius: {
    enabled: env.RADIUS_ENABLED !== 'false',
    host: env.RADIUS_HOST || '0.0.0.0',
    authPort: Number(env.RADIUS_AUTH_PORT || 1812),
    acctPort: Number(env.RADIUS_ACCT_PORT || 1813),
  },
  trustProxy: env.TRUST_PROXY !== 'false',
};
