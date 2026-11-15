'use strict';
// Built-in RADIUS server: MikroTik hotspot asks us whether a voucher may log in,
// and reports usage (accounting) back to us. Works through MTN CGNAT because the
// router initiates the UDP traffic.
const dgram = require('node:dgram');
const R = require('./codec');
const settings = require('../settings');
const vouchers = require('../services/vouchers');

const { ATTR, CODES, MIKROTIK, MIKROTIK_VENDOR } = R;

function formatMac(raw) {
  if (!raw) return '';
  const hex = raw.replace(/[^0-9a-fA-F]/g, '');
  return hex.length === 12 ? hex.match(/../g).join(':').toUpperCase() : raw.toUpperCase();
}

function handleAccess(packet, secret) {
  const ma = R.verifyMessageAuthenticator(packet, secret);
  if (ma === false) return null; // forged / wrong secret: drop silently
  const username = R.attrString(packet, ATTR.USER_NAME) || '';
  const mac = formatMac(R.attrString(packet, ATTR.CALLING_STATION_ID));
  const papRaw = R.attr(packet, ATTR.USER_PASSWORD);

  const checkPassword = (code) => {
    if (papRaw) return vouchers.normalizeCode(R.decryptPassword(papRaw, secret, packet.authenticator)) === code;
    if (R.attr(packet, ATTR.CHAP_PASSWORD)) {
      // The portal always logs in with password = voucher code; try the formatted variant too.
      return R.verifyChap(packet, code) || R.verifyChap(packet, vouchers.formatCode(code));
    }
    return false;
  };

  let decision;
  try {
    decision = vouchers.authorize({ username, checkPassword, mac });
  } catch (err) {
    console.error('[radius] authorize failed:', err);
    decision = { ok: false, reason: 'Server error, please try again' };
  }
  vouchers.logAuth(username, mac, decision.ok ? 'accept' : 'reject', decision.reason);

  if (!decision.ok) {
    return R.encodeResponse(CODES.ACCESS_REJECT, packet, [R.encodeAttr(ATTR.REPLY_MESSAGE, decision.reason)], secret);
  }
  const attrs = [
    R.encodeAttr(ATTR.SESSION_TIMEOUT, decision.sessionTimeout),
    R.encodeAttr(ATTR.ACCT_INTERIM_INTERVAL, decision.interim),
  ];
  if (decision.rateLimit) attrs.push(R.encodeVendor(MIKROTIK_VENDOR, MIKROTIK.RATE_LIMIT, decision.rateLimit));
  if (decision.totalLimitBytes > 0) {
    const b = BigInt(decision.totalLimitBytes);
    attrs.push(R.encodeVendor(MIKROTIK_VENDOR, MIKROTIK.TOTAL_LIMIT, Number(b & 0xffffffffn)));
    if (b >> 32n) attrs.push(R.encodeVendor(MIKROTIK_VENDOR, MIKROTIK.TOTAL_LIMIT_GIGAWORDS, Number(b >> 32n)));
  }
  return R.encodeResponse(CODES.ACCESS_ACCEPT, packet, attrs, secret);
}

function handleAccounting(packet, secret) {
  if (!R.verifyAccountingAuthenticator(packet, secret)) return null;
  const giga = (g, o) => (R.attrInt(packet, g) || 0) * 4294967296 + (R.attrInt(packet, o) || 0);
  const causes = { 1: 'User-Request', 2: 'Lost-Carrier', 4: 'Idle-Timeout', 5: 'Session-Timeout', 6: 'Admin-Reset', 10: 'NAS-Request' };
  try {
    vouchers.account({
      statusType: R.attrInt(packet, ATTR.ACCT_STATUS_TYPE),
      sessionId: R.attrString(packet, ATTR.ACCT_SESSION_ID),
      nasId: R.attrString(packet, ATTR.NAS_IDENTIFIER) || R.attrIp(packet, ATTR.NAS_IP_ADDRESS) || '',
      username: R.attrString(packet, ATTR.USER_NAME),
      mac: formatMac(R.attrString(packet, ATTR.CALLING_STATION_ID)),
      ip: R.attrIp(packet, ATTR.FRAMED_IP_ADDRESS),
      sessionTime: R.attrInt(packet, ATTR.ACCT_SESSION_TIME) || 0,
      inputBytes: giga(ATTR.ACCT_INPUT_GIGAWORDS, ATTR.ACCT_INPUT_OCTETS),
      outputBytes: giga(ATTR.ACCT_OUTPUT_GIGAWORDS, ATTR.ACCT_OUTPUT_OCTETS),
      terminateCause: causes[R.attrInt(packet, ATTR.ACCT_TERMINATE_CAUSE)] || '',
    });
  } catch (err) {
    console.error('[radius] accounting failed:', err);
    return null; // no response -> NAS retries
  }
  return R.encodeResponse(CODES.ACCOUNTING_RESPONSE, packet, [], secret);
}

function handle(msg) {
  const secret = settings.get('radius_secret');
  if (!secret) return null;
  let packet;
  try {
    packet = R.decode(msg);
  } catch {
    return null;
  }
  if (packet.code === CODES.ACCESS_REQUEST) return handleAccess(packet, secret);
  if (packet.code === CODES.ACCOUNTING_REQUEST) return handleAccounting(packet, secret);
  return null;
}

function listen(port, host) {
  return new Promise((resolve, reject) => {
    const sock = dgram.createSocket('udp4');
    sock.on('message', (msg, rinfo) => {
      const reply = handle(msg);
      if (reply) sock.send(reply, rinfo.port, rinfo.address);
    });
    sock.once('error', reject);
    sock.bind(port, host, () => {
      sock.removeListener('error', reject);
      sock.on('error', (err) => console.error('[radius] socket error:', err));
      resolve(sock);
    });
  });
}

async function start({ host = '0.0.0.0', authPort = 1812, acctPort = 1813 } = {}) {
  const auth = await listen(authPort, host);
  const acct = await listen(acctPort, host);
  return { auth, acct, close: () => { auth.close(); acct.close(); } };
}

module.exports = { start, handle };
