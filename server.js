'use strict';
const path = require('node:path');
const express = require('express');
const config = require('./src/config');
const db = require('./src/db');
const settings = require('./src/settings');
const auth = require('./src/auth');
const vouchers = require('./src/services/vouchers');
const radius = require('./src/radius/server');

function parseCookies(req, res, next) {
  req.cookies = {};
  const header = req.headers.cookie;
  if (header) {
    for (const part of header.split(';')) {
      const i = part.indexOf('=');
      if (i === -1) continue;
      const k = part.slice(0, i).trim();
      try { req.cookies[k] = decodeURIComponent(part.slice(i + 1).trim()); } catch { /* ignore */ }
    }
  }
  next();
}

function createApp() {
  const app = express();
  app.disable('x-powered-by');
  if (config.trustProxy) app.set('trust proxy', 1);

  app.use((req, res, next) => {
    res.set('X-Content-Type-Options', 'nosniff');
    res.set('Referrer-Policy', 'same-origin');
    res.set('X-Frame-Options', 'DENY');
    next();
  });
  app.use(parseCookies);
  app.use(express.urlencoded({ extended: false, limit: '100kb' }));

  app.use('/static', express.static(path.join(__dirname, 'public'), { maxAge: '7d' }));
  app.get('/static/fonts/jakarta.woff2', (req, res) => {
    res.set('Cache-Control', 'public, max-age=31536000, immutable');
    res.sendFile(require.resolve('@fontsource-variable/plus-jakarta-sans/files/plus-jakarta-sans-latin-wght-normal.woff2'));
  });
  app.get('/healthz', (req, res) => res.json({ ok: true }));

  app.use('/admin', require('./src/routes/admin'));
  app.use('/', require('./src/routes/portal'));

  app.use((req, res) => res.redirect('/'));
  app.use((err, req, res, next) => { // eslint-disable-line no-unused-vars
    console.error(err);
    res.status(500).send('Something went wrong. Please go back and try again.');
  });
  return app;
}

async function main() {
  db.open();
  settings.init();
  auth.init();

  const app = createApp();
  app.listen(config.port, () => {
    console.log(`ZaxConnect portal running on http://localhost:${config.port}`);
    if (!auth.hasAdmin()) console.log(`  → Create your admin account at http://localhost:${config.port}/admin/setup`);
  });

  if (config.radius.enabled) {
    try {
      await radius.start(config.radius);
      console.log(`RADIUS listening on udp/${config.radius.authPort} (auth) and udp/${config.radius.acctPort} (accounting)`);
    } catch (err) {
      console.error(`RADIUS failed to start (${err.code || err.message}). Ports below 1024 need root; 1812/1813 normally do not.`);
    }
  }

  vouchers.housekeeping();
  setInterval(() => {
    try { vouchers.housekeeping(); } catch (err) { console.error('housekeeping:', err); }
  }, 60000).unref();
}

if (require.main === module) main();

module.exports = { createApp };
