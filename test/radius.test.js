'use strict';
// End-to-end tests: a simulated MikroTik NAS talks RADIUS to the billing server.
const test = require('node:test');
const assert = require('node:assert/strict');
const dgram = require('node:dgram');
const crypto = require('node:crypto');

const db = require('../src/db');
db.open(':memory:');
const settings = require('../src/settings');
settings.init();
settings.set({ radius_secret: 'testing123' });
const R = require('../src/radius/codec');
const radius = require('../src/radius/server');
const vouchers = require('../src/services/vouchers');
const payments = require('../src/services/payments');

const SECRET = 'testing123';
let srv;
let authPort;
let acctPort;

test.before(async () => {
  srv = await radius.start({ host: '127.0.0.1', authPort: 0, acctPort: 0 });
  authPort = srv.auth.address().port;
  acctPort = srv.acct.address().port;
});
test.after(() => srv.close());

function send(port, packet) {
  return new Promise((resolve, reject) => {
    const sock = dgram.createSocket('udp4');
    const timer = setTimeout(() => { sock.close(); reject(new Error('timeout')); }, 2000);
    sock.on('message', (msg) => { clearTimeout(timer); sock.close(); resolve(R.decode(msg)); });
    sock.send(packet, port, '127.0.0.1');
  });
}

function accessRequest(username, password, mac, { chap = false, secret = SECRET } = {}) {
  const attrs = [R.encodeAttr(R.ATTR.USER_NAME, username), R.encodeAttr(R.ATTR.CALLING_STATION_ID, mac)];
  let chapId;
  if (chap) {
    chapId = Buffer.from([7]);
    const challenge = crypto.randomBytes(16);
    const hash = crypto.createHash('md5').update(Buffer.concat([chapId, Buffer.from(password), challenge])).digest();
    attrs.push(R.encodeAttr(R.ATTR.CHAP_PASSWORD, Buffer.concat([chapId, hash])));
    attrs.push(R.encodeAttr(R.ATTR.CHAP_CHALLENGE, challenge));
  } else {
    attrs.push((auth) => R.encodeAttr(R.ATTR.USER_PASSWORD, R.encryptPassword(password, secret, auth)));
  }
  return R.encodeRequest(R.CODES.ACCESS_REQUEST, crypto.randomInt(256), attrs, secret);
}

function checkResponseAuth(resp, reqAuth) {
  const copy = Buffer.from(resp.raw);
  reqAuth.copy(copy, 4);
  const expected = crypto.createHash('md5').update(Buffer.concat([copy, Buffer.from(SECRET)])).digest();
  assert.ok(expected.equals(resp.authenticator), 'response authenticator valid');
  // Message-Authenticator
  const ma = resp.attributes.find((a) => a.type === R.ATTR.MESSAGE_AUTHENTICATOR);
  if (ma) {
    copy.fill(0, ma.offset + 2, ma.offset + 18);
    const mac = crypto.createHmac('md5', SECRET).update(copy).digest();
    assert.ok(mac.equals(ma.value), 'message authenticator valid');
  }
}

function vendorAttr(resp, type) {
  for (const a of resp.attributes) {
    if (a.type === R.ATTR.VENDOR_SPECIFIC && a.value.readUInt32BE(0) === R.MIKROTIK_VENDOR && a.value[4] === type) {
      return a.value.subarray(6);
    }
  }
  return undefined;
}

function planId(name) {
  return db.get().prepare('SELECT id FROM plans WHERE name = ?').get(name).id;
}

test('PAP login with valid voucher is accepted with plan limits', async () => {
  const v = vouchers.createVoucher({ planId: planId('Daily Pass') });
  const { packet, authenticator } = accessRequest(v.code, v.code, 'AA:BB:CC:DD:EE:01');
  const resp = await send(authPort, packet);
  assert.equal(resp.code, R.CODES.ACCESS_ACCEPT);
  checkResponseAuth(resp, authenticator);
  const timeout = R.attrInt(resp, R.ATTR.SESSION_TIMEOUT);
  assert.ok(timeout > 86000 && timeout <= 86400, `session timeout ${timeout}`);
  assert.equal(vendorAttr(resp, R.MIKROTIK.RATE_LIMIT).toString(), '3000k/8000k');
  const after = vouchers.findByCode(v.code);
  assert.equal(after.status, 'active');
  assert.deepEqual(JSON.parse(after.macs), ['AA:BB:CC:DD:EE:01']);
});

test('formatted code (with dash, lowercase) is accepted', async () => {
  const v = vouchers.createVoucher({ planId: planId('Quick Hour') });
  const typed = vouchers.formatCode(v.code).toLowerCase();
  const { packet } = accessRequest(typed, typed, 'AA:BB:CC:DD:EE:02');
  const resp = await send(authPort, packet);
  assert.equal(resp.code, R.CODES.ACCESS_ACCEPT);
});

test('CHAP login works', async () => {
  const v = vouchers.createVoucher({ planId: planId('Quick Hour') });
  const { packet } = accessRequest(v.code, v.code, 'AA:BB:CC:DD:EE:03', { chap: true });
  const resp = await send(authPort, packet);
  assert.equal(resp.code, R.CODES.ACCESS_ACCEPT);
});

test('wrong code and wrong secret are rejected', async () => {
  const bad = accessRequest('ZZZZZZZZ', 'ZZZZZZZZ', 'AA:BB:CC:DD:EE:04');
  const resp = await send(authPort, bad.packet);
  assert.equal(resp.code, R.CODES.ACCESS_REJECT);
  assert.match(R.attrString(resp, R.ATTR.REPLY_MESSAGE), /Invalid/);

  const v = vouchers.createVoucher({ planId: planId('Quick Hour') });
  const wrongSecret = accessRequest(v.code, v.code, 'AA:BB:CC:DD:EE:05', { secret: 'nope' });
  const resp2 = await send(authPort, wrongSecret.packet);
  assert.equal(resp2.code, R.CODES.ACCESS_REJECT);
});

test('single-device voucher rejects a second device', async () => {
  const v = vouchers.createVoucher({ planId: planId('Quick Hour') });
  assert.equal((await send(authPort, accessRequest(v.code, v.code, '11:11:11:11:11:11').packet)).code, R.CODES.ACCESS_ACCEPT);
  assert.equal((await send(authPort, accessRequest(v.code, v.code, '11:11:11:11:11:11').packet)).code, R.CODES.ACCESS_ACCEPT);
  const resp = await send(authPort, accessRequest(v.code, v.code, '22:22:22:22:22:22').packet);
  assert.equal(resp.code, R.CODES.ACCESS_REJECT);
  assert.match(R.attrString(resp, R.ATTR.REPLY_MESSAGE), /another device/);
});

test('expired voucher is rejected', async () => {
  const v = vouchers.createVoucher({ planId: planId('Quick Hour') });
  db.get().prepare("UPDATE vouchers SET status='active', activated_at=?, expires_at=? WHERE id=?").run(Date.now() - 7200e3, Date.now() - 1000, v.id);
  const resp = await send(authPort, accessRequest(v.code, v.code, '33:33:33:33:33:33').packet);
  assert.equal(resp.code, R.CODES.ACCESS_REJECT);
  assert.match(R.attrString(resp, R.ATTR.REPLY_MESSAGE), /expired/);
});

test('accounting tracks data and data-capped plans run out', async () => {
  const v = vouchers.createVoucher({ planId: planId('2GB Bundle') });
  const mac = '44:44:44:44:44:44';
  const first = await send(authPort, accessRequest(v.code, v.code, mac).packet);
  assert.equal(first.code, R.CODES.ACCESS_ACCEPT);
  assert.equal(R.attrInt({ attributes: [{ type: 1, value: vendorAttr(first, R.MIKROTIK.TOTAL_LIMIT) }] }, 1), 2048 * 1024 * 1024);

  const acct = (status, inBytes, outBytes) => R.encodeRequest(R.CODES.ACCOUNTING_REQUEST, 9, [
    R.encodeAttr(R.ATTR.ACCT_STATUS_TYPE, status),
    R.encodeAttr(R.ATTR.ACCT_SESSION_ID, '81000001'),
    R.encodeAttr(R.ATTR.USER_NAME, v.code),
    R.encodeAttr(R.ATTR.CALLING_STATION_ID, mac),
    R.encodeAttr(R.ATTR.NAS_IDENTIFIER, 'zax-hap'),
    R.encodeAttr(R.ATTR.ACCT_SESSION_TIME, 600),
    R.encodeAttr(R.ATTR.ACCT_INPUT_OCTETS, inBytes),
    R.encodeAttr(R.ATTR.ACCT_OUTPUT_OCTETS, outBytes),
  ], SECRET).packet;

  assert.equal((await send(acctPort, acct(1, 0, 0))).code, R.CODES.ACCOUNTING_RESPONSE);
  assert.equal((await send(acctPort, acct(3, 100e6, 900e6))).code, R.CODES.ACCOUNTING_RESPONSE);
  assert.equal(vouchers.findByCode(v.code).bytes_used, 1000e6);

  const second = await send(authPort, accessRequest(v.code, v.code, mac).packet);
  assert.equal(second.code, R.CODES.ACCESS_ACCEPT);
  const remaining = vendorAttr(second, R.MIKROTIK.TOTAL_LIMIT).readUInt32BE(0);
  assert.equal(remaining, 2048 * 1024 * 1024 - 1000e6);

  await send(acctPort, acct(2, 300e6, 1900e6));
  const third = await send(authPort, accessRequest(v.code, v.code, mac).packet);
  assert.equal(third.code, R.CODES.ACCESS_REJECT);
  assert.match(R.attrString(third, R.ATTR.REPLY_MESSAGE), /exhausted/);
});

test('payment fulfillment is idempotent and creates one voucher', () => {
  const plan = db.get().prepare('SELECT * FROM plans WHERE name = ?').get('Weekly Pass');
  const order = payments.createOrder({ plan, phone: '08031234567', email: 'a@b.co', gateway: 'demo' });
  const a = payments.fulfill(order.reference);
  const b = payments.fulfill(order.reference);
  assert.equal(a.status, 'success');
  assert.equal(a.voucher_code, b.voucher_code);
  assert.throws(() => {
    const o2 = payments.createOrder({ plan, phone: '08031234567', email: 'a@b.co', gateway: 'paystack' });
    payments.fulfill(o2.reference, { amountKobo: 100 });
  }, /less than/);
});

test('phone normalisation', () => {
  assert.equal(payments.normalizePhone('+234 803 123 4567'), '08031234567');
  assert.equal(payments.normalizePhone('2348031234567'), '08031234567');
  assert.ok(payments.isValidPhone('08031234567'));
  assert.ok(!payments.isValidPhone('0803123'));
});
