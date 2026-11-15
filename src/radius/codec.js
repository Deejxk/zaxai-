'use strict';
// Minimal RADIUS (RFC 2865 / 2866 / 3579) packet codec with MikroTik vendor attributes.
const crypto = require('node:crypto');

const CODES = {
  ACCESS_REQUEST: 1,
  ACCESS_ACCEPT: 2,
  ACCESS_REJECT: 3,
  ACCOUNTING_REQUEST: 4,
  ACCOUNTING_RESPONSE: 5,
};

const ATTR = {
  USER_NAME: 1,
  USER_PASSWORD: 2,
  CHAP_PASSWORD: 3,
  NAS_IP_ADDRESS: 4,
  FRAMED_IP_ADDRESS: 8,
  REPLY_MESSAGE: 18,
  CLASS: 25,
  VENDOR_SPECIFIC: 26,
  SESSION_TIMEOUT: 27,
  IDLE_TIMEOUT: 28,
  CALLED_STATION_ID: 30,
  CALLING_STATION_ID: 31,
  NAS_IDENTIFIER: 32,
  ACCT_STATUS_TYPE: 40,
  ACCT_INPUT_OCTETS: 42,
  ACCT_OUTPUT_OCTETS: 43,
  ACCT_SESSION_ID: 44,
  ACCT_SESSION_TIME: 46,
  ACCT_TERMINATE_CAUSE: 49,
  ACCT_INPUT_GIGAWORDS: 52,
  ACCT_OUTPUT_GIGAWORDS: 53,
  CHAP_CHALLENGE: 60,
  MESSAGE_AUTHENTICATOR: 80,
  ACCT_INTERIM_INTERVAL: 85,
};

const MIKROTIK_VENDOR = 14988;
const MIKROTIK = {
  RECV_LIMIT: 1,
  XMIT_LIMIT: 2,
  RATE_LIMIT: 8,
  TOTAL_LIMIT: 17,
  TOTAL_LIMIT_GIGAWORDS: 18,
};

const md5 = (...parts) => crypto.createHash('md5').update(Buffer.concat(parts)).digest();

function decode(buf) {
  if (buf.length < 20) throw new Error('packet too short');
  const length = buf.readUInt16BE(2);
  if (length < 20 || length > buf.length) throw new Error('bad length');
  const packet = {
    code: buf[0],
    identifier: buf[1],
    length,
    authenticator: buf.subarray(4, 20),
    attributes: [],
    raw: buf.subarray(0, length),
  };
  let off = 20;
  while (off < length) {
    const type = buf[off];
    const len = buf[off + 1];
    if (len < 2 || off + len > length) throw new Error('bad attribute');
    packet.attributes.push({ type, value: buf.subarray(off + 2, off + len), offset: off });
    off += len;
  }
  return packet;
}

function attr(packet, type) {
  const a = packet.attributes.find((x) => x.type === type);
  return a ? a.value : undefined;
}

function attrString(packet, type) {
  const v = attr(packet, type);
  return v ? v.toString('utf8') : undefined;
}

function attrInt(packet, type) {
  const v = attr(packet, type);
  return v && v.length >= 4 ? v.readUInt32BE(0) : undefined;
}

function attrIp(packet, type) {
  const v = attr(packet, type);
  return v && v.length === 4 ? Array.from(v).join('.') : undefined;
}

/** Decrypt a PAP User-Password (RFC 2865 §5.2). */
function decryptPassword(encrypted, secret, authenticator) {
  const s = Buffer.from(secret, 'utf8');
  const out = Buffer.alloc(encrypted.length);
  let prev = authenticator;
  for (let i = 0; i < encrypted.length; i += 16) {
    const b = md5(s, prev);
    for (let j = 0; j < 16 && i + j < encrypted.length; j++) out[i + j] = encrypted[i + j] ^ b[j];
    prev = encrypted.subarray(i, i + 16);
  }
  let end = out.length;
  while (end > 0 && out[end - 1] === 0) end--;
  return out.subarray(0, end).toString('utf8');
}

/** Encrypt a PAP password (used by tests / simulated NAS). */
function encryptPassword(password, secret, authenticator) {
  const p = Buffer.from(password, 'utf8');
  const padded = Buffer.alloc(Math.max(16, Math.ceil(p.length / 16) * 16));
  p.copy(padded);
  const s = Buffer.from(secret, 'utf8');
  const out = Buffer.alloc(padded.length);
  let prev = authenticator;
  for (let i = 0; i < padded.length; i += 16) {
    const b = md5(s, prev);
    for (let j = 0; j < 16; j++) out[i + j] = padded[i + j] ^ b[j];
    prev = out.subarray(i, i + 16);
  }
  return out;
}

/** Verify CHAP-Password against a known plaintext password. */
function verifyChap(packet, password) {
  const chap = attr(packet, ATTR.CHAP_PASSWORD);
  if (!chap || chap.length !== 17) return false;
  const challenge = attr(packet, ATTR.CHAP_CHALLENGE) || packet.authenticator;
  const expected = md5(chap.subarray(0, 1), Buffer.from(password, 'utf8'), challenge);
  return crypto.timingSafeEqual(expected, chap.subarray(1));
}

/** Verify the Message-Authenticator attribute of a request if present. Returns null when absent. */
function verifyMessageAuthenticator(packet, secret) {
  const a = packet.attributes.find((x) => x.type === ATTR.MESSAGE_AUTHENTICATOR);
  if (!a) return null;
  const copy = Buffer.from(packet.raw);
  copy.fill(0, a.offset + 2, a.offset + 18);
  const mac = crypto.createHmac('md5', secret).update(copy).digest();
  return a.value.length === 16 && crypto.timingSafeEqual(mac, a.value);
}

/** Verify the Request Authenticator of an Accounting-Request (RFC 2866 §3). */
function verifyAccountingAuthenticator(packet, secret) {
  const copy = Buffer.from(packet.raw);
  copy.fill(0, 4, 20);
  const expected = md5(copy, Buffer.from(secret, 'utf8'));
  return crypto.timingSafeEqual(expected, packet.authenticator);
}

function encodeAttr(type, value) {
  let v;
  if (Buffer.isBuffer(value)) v = value;
  else if (typeof value === 'number') {
    v = Buffer.alloc(4);
    v.writeUInt32BE(Math.max(0, Math.min(0xffffffff, Math.floor(value))), 0);
  } else v = Buffer.from(String(value), 'utf8');
  if (v.length > 253) v = v.subarray(0, 253);
  return Buffer.concat([Buffer.from([type, v.length + 2]), v]);
}

function encodeVendor(vendorId, vendorType, value) {
  const inner = encodeAttr(vendorType, value);
  const vid = Buffer.alloc(4);
  vid.writeUInt32BE(vendorId, 0);
  return encodeAttr(ATTR.VENDOR_SPECIFIC, Buffer.concat([vid, inner]));
}

/**
 * Build a response packet. attrs is an array of pre-encoded attribute buffers.
 * Always adds a Message-Authenticator (required by RouterOS 7.15+ "require-message-auth").
 */
function encodeResponse(code, request, attrs, secret) {
  const withMa = code !== CODES.ACCOUNTING_RESPONSE;
  const ma = Buffer.concat([Buffer.from([ATTR.MESSAGE_AUTHENTICATOR, 18]), Buffer.alloc(16)]);
  const body = Buffer.concat(withMa ? [...attrs, ma] : attrs);
  const length = 20 + body.length;
  const header = Buffer.alloc(20);
  header[0] = code;
  header[1] = request.identifier;
  header.writeUInt16BE(length, 2);
  request.authenticator.copy(header, 4);
  const packet = Buffer.concat([header, body]);
  // Message-Authenticator is computed with the Request Authenticator in place.
  if (withMa) crypto.createHmac('md5', secret).update(packet).digest().copy(packet, length - 16);
  // Then the Response Authenticator over the whole packet.
  md5(packet, Buffer.from(secret, 'utf8')).copy(packet, 4);
  return packet;
}

/** Build a request packet (used by tests to simulate a MikroTik NAS). */
function encodeRequest(code, identifier, attrs, secret) {
  const isAcct = code === CODES.ACCOUNTING_REQUEST;
  const auth = isAcct ? Buffer.alloc(16) : crypto.randomBytes(16);
  const body = Buffer.concat(attrs.map((a) => (typeof a === 'function' ? a(auth) : a)));
  const header = Buffer.alloc(20);
  header[0] = code;
  header[1] = identifier;
  header.writeUInt16BE(20 + body.length, 2);
  auth.copy(header, 4);
  const packet = Buffer.concat([header, body]);
  if (isAcct) md5(packet, Buffer.from(secret, 'utf8')).copy(packet, 4);
  return { packet, authenticator: Buffer.from(packet.subarray(4, 20)) };
}

module.exports = {
  CODES,
  ATTR,
  MIKROTIK,
  MIKROTIK_VENDOR,
  decode,
  attr,
  attrString,
  attrInt,
  attrIp,
  decryptPassword,
  encryptPassword,
  verifyChap,
  verifyMessageAuthenticator,
  verifyAccountingAuthenticator,
  encodeAttr,
  encodeVendor,
  encodeResponse,
  encodeRequest,
};
