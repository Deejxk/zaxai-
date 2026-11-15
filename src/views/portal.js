'use strict';
// Customer-facing captive portal pages.
const { html, raw, esc, money, duration, dataLabel, speedLabel, bytes, remaining, logo, icon, page } = require('./ui');

function shell(s, { title, hero, content, compact = false }) {
  const support = [];
  if (s.support_whatsapp) {
    const wa = s.support_whatsapp.replace(/[^0-9]/g, '').replace(/^0/, '234');
    support.push(html`<a href="https://wa.me/${wa}" class="foot-link">${icon('whatsapp')} WhatsApp</a>`);
  }
  if (s.support_phone) support.push(html`<a href="tel:${s.support_phone}" class="foot-link">${icon('phone')} ${s.support_phone}</a>`);

  return page({
    title: `${title} · ${s.business_name}`,
    bodyClass: 'portal-body',
    body: html`
<div class="portal">
  <header class="hero ${compact ? 'hero-compact' : ''}">
    <div class="hero-glow" aria-hidden="true"></div>
    <div class="hero-inner">
      <nav class="topbar">
        <a href="/" class="brand-link" aria-label="${s.business_name} home">${logo(s.business_name)}</a>
        <a href="/status" class="topbar-link">${icon('search')}<span>Check my plan</span></a>
      </nav>
      ${hero}
    </div>
  </header>
  <main class="sheet">${content}</main>
  <footer class="portal-foot">
    ${support.length ? html`<div class="foot-support"><span>Need help?</span>${support}</div>` : ''}
    <div class="foot-legal">${icon('shield')} Payments secured by Paystack · © ${new Date().getFullYear()} ${s.business_name}${s.hotspot_location ? ` · ${s.hotspot_location}` : ''}</div>
  </footer>
</div>
<script src="/static/js/portal.js" defer></script>`,
  });
}

function heroBlock({ eyebrow, heading, text, ctx }) {
  return html`
  <div class="hero-copy">
    ${eyebrow ? html`<span class="eyebrow"><span class="dot"></span>${eyebrow}</span>` : ''}
    <h1>${heading}</h1>
    ${text ? html`<p>${text}</p>` : ''}
    ${ctx && ctx.mac ? html`<div class="device-chip">${icon('devices')} Device <b>${ctx.mac}</b></div>` : ''}
  </div>`;
}

function planCard(p, s) {
  const feats = [
    [icon('clock'), `Valid for ${duration(p.duration_min)}`],
    [icon('data'), dataLabel(p.data_mb)],
    [icon('bolt'), speedLabel(p.download_mbps)],
    [icon('devices'), `${p.devices} device${p.devices > 1 ? 's' : ''}`],
  ];
  return html`
  <a class="plan ${p.popular ? 'plan-popular' : ''}" href="/checkout/${p.id}">
    <div class="plan-top">
      <span class="plan-name">${p.name}</span>
      ${p.popular ? html`<span class="badge badge-brand">Most popular</span>` : ''}
    </div>
    <div class="plan-price"><span class="cur">${s.currency_symbol}</span>${Number(p.price).toLocaleString('en-NG')}</div>
    <div class="plan-desc">${p.description || `for ${duration(p.duration_min)}`}</div>
    <ul class="plan-feats">${feats.map(([i, t]) => html`<li>${i}<span>${t}</span></li>`)}</ul>
    <span class="plan-cta">Buy now ${icon('arrow')}</span>
  </a>`;
}

function home(s, { plans, ctx, error, tab }) {
  const content = html`
  ${error ? html`<div class="alert alert-danger">${icon('alert')}<div>${error}</div></div>` : ''}
  <div class="tabs" data-tabs>
    <button type="button" class="tab ${tab !== 'voucher' ? 'active' : ''}" data-tab="plans">${icon('wifi')} Buy a plan</button>
    <button type="button" class="tab ${tab === 'voucher' ? 'active' : ''}" data-tab="voucher">${icon('ticket')} I have a voucher</button>
  </div>

  <section class="tab-panel ${tab !== 'voucher' ? 'active' : ''}" data-panel="plans">
    <div class="section-head">
      <h2>Choose your plan</h2>
      <p>Your time starts counting the moment you connect.</p>
    </div>
    <div class="plans">${plans.map((p) => planCard(p, s))}</div>
    ${plans.length === 0 ? html`<div class="empty">No plans are available right now. Please check back shortly.</div>` : ''}
  </section>

  <section class="tab-panel ${tab === 'voucher' ? 'active' : ''}" data-panel="voucher">
    <div class="card card-pad voucher-card">
      <div class="voucher-icon">${icon('ticket')}</div>
      <h2>Enter your voucher code</h2>
      <p class="muted">Bought a voucher card or paid earlier? Enter the code to connect.</p>
      <form class="voucher-form" data-voucher-form action="/voucher" method="post">
        <input class="input input-code" name="code" placeholder="XXXX-XXXX" autocomplete="off" autocapitalize="characters"
          spellcheck="false" maxlength="12" required>
        <button class="btn btn-primary btn-lg btn-block" type="submit">Connect ${icon('arrow')}</button>
        <div class="form-msg" data-msg role="status"></div>
      </form>
      <a href="/recover" class="link-subtle">Lost your code? Recover it here</a>
    </div>
  </section>

  <section class="steps">
    <div class="step"><span class="step-n">1</span><div><b>Pick a plan</b><p>Hourly, daily, weekly or monthly.</p></div></div>
    <div class="step"><span class="step-n">2</span><div><b>Pay securely</b><p>Card, bank transfer or USSD.</p></div></div>
    <div class="step"><span class="step-n">3</span><div><b>You're online</b><p>We connect your device instantly.</p></div></div>
  </section>`;

  return shell(s, {
    title: 'Get online',
    hero: heroBlock({ eyebrow: 'Hotspot online', heading: raw('Fast Wi-Fi, <span class="grad">pay as you go</span>'), text: s.tagline, ctx }),
    content,
  });
}

function checkout(s, { plan, ctx, error, values = {}, demo }) {
  const content = html`
  <a href="/" class="back-link">← All plans</a>
  <div class="checkout">
    <div class="card card-pad summary">
      <div class="summary-head">
        <div>
          <div class="kicker">You're buying</div>
          <h2>${plan.name}</h2>
        </div>
        <div class="summary-price">${money(plan.price, s.currency_symbol)}</div>
      </div>
      <ul class="summary-list">
        <li>${icon('clock')}<span>Validity</span><b>${duration(plan.duration_min)}</b></li>
        <li>${icon('data')}<span>Data</span><b>${plan.data_mb ? dataLabel(plan.data_mb).replace(' data', '') : 'Unlimited'}</b></li>
        <li>${icon('bolt')}<span>Speed</span><b>${plan.download_mbps > 0 ? `${+plan.download_mbps} Mbps` : 'Full speed'}</b></li>
        <li>${icon('devices')}<span>Devices</span><b>${plan.devices}</b></li>
      </ul>
      <p class="fine">Validity starts from your first login. Unused vouchers never expire.</p>
    </div>

    <form class="card card-pad pay-form" method="post" action="/checkout/${plan.id}" data-pay-form>
      <h3>Your details</h3>
      <p class="muted">We'll use your phone number to help you recover your voucher.</p>
      ${error ? html`<div class="alert alert-danger">${icon('alert')}<div>${error}</div></div>` : ''}
      <label class="field">
        <span>Phone number</span>
        <input class="input" type="tel" name="phone" placeholder="0803 123 4567" inputmode="tel" required value="${values.phone || ''}">
      </label>
      <label class="field">
        <span>Email address <em>(optional, for receipt)</em></span>
        <input class="input" type="email" name="email" placeholder="you@example.com" value="${values.email || ''}">
      </label>
      <button class="btn btn-primary btn-lg btn-block" type="submit">
        ${icon('shield')} Pay ${money(plan.price, s.currency_symbol)} securely
      </button>
      ${demo
        ? html`<div class="alert alert-warn">${icon('alert')}<div><b>Demo mode.</b> Paystack keys are not set yet, so payment is simulated. Add your keys in Admin → Settings to accept real payments.</div></div>`
        : html`<div class="pay-methods"><span>Card</span><span>Bank transfer</span><span>USSD</span><span>Bank</span></div>`}
    </form>
  </div>`;

  return shell(s, {
    title: `Buy ${plan.name}`,
    compact: true,
    hero: heroBlock({ heading: 'Secure checkout', text: 'Complete payment and you will be online in seconds.', ctx }),
    content,
  });
}

function demoPay(s, { order }) {
  const content = html`
  <div class="card card-pad center narrow">
    <div class="demo-badge">DEMO PAYMENT</div>
    <h2>${money(order.amount, s.currency_symbol)}</h2>
    <p class="muted">${order.plan_name} · Ref ${order.reference}</p>
    <form method="post" action="/pay/demo/${order.reference}" class="stack">
      <button class="btn btn-primary btn-lg btn-block" name="result" value="success" type="submit">Simulate successful payment</button>
      <button class="btn btn-ghost btn-block" name="result" value="failed" type="submit">Simulate failed payment</button>
    </form>
  </div>`;
  return shell(s, { title: 'Demo payment', compact: true, hero: heroBlock({ heading: 'Test checkout' }), content });
}

function success(s, { order, voucher, connectUrl }) {
  const content = html`
  <div class="card card-pad center narrow success-card">
    <div class="success-ring">${icon('check')}</div>
    <h2>Payment successful</h2>
    <p class="muted">Your <b>${order.plan_name}</b> plan is ready.</p>

    <div class="code-box">
      <div class="code-label">Your voucher code</div>
      <div class="code-value" data-code="${voucher.code}">${voucher.display}</div>
      <button type="button" class="btn btn-soft btn-sm" data-copy="${voucher.display}">${icon('copy')} Copy code</button>
    </div>

    ${connectUrl
      ? html`<a class="btn btn-primary btn-lg btn-block" href="${connectUrl}" data-autoconnect>${icon('wifi')} Connect now</a>
        <p class="fine" data-countdown>Connecting you automatically in <b>5</b>s…</p>`
      : html`<div class="alert alert-info">${icon('wifi')}<div>Connect to the <b>${s.business_name}</b> Wi-Fi, open any website and enter this code under <b>“I have a voucher”</b>.</div></div>`}

    <ul class="receipt">
      <li><span>Reference</span><b>${order.reference}</b></li>
      <li><span>Amount</span><b>${money(order.amount, s.currency_symbol)}</b></li>
      <li><span>Phone</span><b>${order.phone || '—'}</b></li>
    </ul>
    <p class="fine">Save this code. You can use it to reconnect any time until your plan expires.</p>
  </div>`;
  return shell(s, { title: 'Payment successful', compact: true, hero: heroBlock({ heading: "You're all set!" }), content });
}

function pending(s, { order }) {
  const content = html`
  <div class="card card-pad center narrow" data-poll="${order.reference}">
    <div class="spinner"></div>
    <h2>Confirming your payment…</h2>
    <p class="muted">This usually takes a few seconds. Bank transfers can take up to a few minutes. Please keep this page open.</p>
    <ul class="receipt">
      <li><span>Reference</span><b>${order.reference}</b></li>
      <li><span>Plan</span><b>${order.plan_name}</b></li>
      <li><span>Amount</span><b>${money(order.amount, s.currency_symbol)}</b></li>
    </ul>
    <a href="/pay/callback?reference=${order.reference}" class="btn btn-soft btn-block">Check again</a>
  </div>`;
  return shell(s, { title: 'Confirming payment', compact: true, hero: heroBlock({ heading: 'Almost there' }), content });
}

function failed(s, { order }) {
  const content = html`
  <div class="card card-pad center narrow">
    <div class="fail-ring">${icon('alert')}</div>
    <h2>Payment not completed</h2>
    <p class="muted">Your payment for <b>${order.plan_name}</b> was not successful. You have not been charged.</p>
    <a href="/checkout/${order.plan_id}" class="btn btn-primary btn-lg btn-block">Try again</a>
    <a href="/" class="btn btn-ghost btn-block">Choose another plan</a>
  </div>`;
  return shell(s, { title: 'Payment failed', compact: true, hero: heroBlock({ heading: 'Something went wrong' }), content });
}

function statusPage(s, { code, info, error, connectUrl, online }) {
  let body = '';
  if (info) {
    const stateLabel = {
      unused: ['Not yet used', 'badge-neutral'],
      active: ['Active', 'badge-success'],
      expired: ['Expired', 'badge-danger'],
      exhausted: ['Data exhausted', 'badge-danger'],
      disabled: ['Disabled', 'badge-danger'],
    }[info.state] || [info.state, 'badge-neutral'];
    const pct = info.dataCapBytes ? Math.min(100, Math.round((info.bytesUsed / info.dataCapBytes) * 100)) : 0;
    const timePct = info.expiresAt ? Math.min(100, Math.round((1 - info.remainingMs / (info.durationMin * 60000)) * 100)) : 0;
    body = html`
    <div class="card card-pad status-card">
      <div class="status-head">
        <div><div class="kicker">Voucher</div><div class="mono big">${info.display}</div></div>
        <span class="badge ${stateLabel[1]}">${stateLabel[0]}</span>
      </div>
      <div class="plan-chip">${icon('layers')} ${info.plan}</div>
      <div class="meter-block">
        <div class="meter-label"><span>Time remaining</span><b data-remaining="${info.state === 'active' ? info.expiresAt : ''}">${info.state === 'active' ? remaining(info.remainingMs) : info.state === 'unused' ? duration(info.durationMin) : '0m'}</b></div>
        <div class="meter"><span style="width:${100 - timePct}%"></span></div>
      </div>
      ${info.dataCapBytes ? html`
      <div class="meter-block">
        <div class="meter-label"><span>Data used</span><b>${bytes(info.bytesUsed)} of ${bytes(info.dataCapBytes)}</b></div>
        <div class="meter meter-data"><span style="width:${pct}%"></span></div>
      </div>` : html`<div class="meter-label"><span>Data used</span><b>${bytes(info.bytesUsed)} · Unlimited</b></div>`}
      <div class="meter-label"><span>Devices</span><b>${info.devicesUsed} of ${info.devices}</b></div>
      ${info.expiresAt ? html`<div class="meter-label"><span>Expires</span><b>${new Date(info.expiresAt).toLocaleString('en-NG', { timeZone: 'Africa/Lagos', dateStyle: 'medium', timeStyle: 'short' })}</b></div>` : ''}
      ${connectUrl && ['unused', 'active'].includes(info.state) && !online ? html`<a href="${connectUrl}" class="btn btn-primary btn-block">${icon('wifi')} Connect with this voucher</a>` : ''}
      ${['expired', 'exhausted'].includes(info.state) ? html`<a href="/" class="btn btn-primary btn-block">Buy a new plan</a>` : ''}
    </div>`;
  }
  const content = html`
  ${online ? html`<div class="alert alert-success">${icon('check')}<div><b>You're connected!</b> Enjoy your browsing.</div></div>` : ''}
  <form class="card card-pad lookup" method="get" action="/status">
    <label class="field"><span>Voucher code</span>
      <input class="input input-code" name="code" placeholder="XXXX-XXXX" value="${code || ''}" autocomplete="off" autocapitalize="characters" required></label>
    <button class="btn btn-dark btn-block" type="submit">${icon('search')} Check status</button>
  </form>
  ${error ? html`<div class="alert alert-danger">${icon('alert')}<div>${error}</div></div>` : ''}
  ${body}`;
  return shell(s, {
    title: online ? 'Connected' : 'Check my plan',
    compact: true,
    hero: heroBlock({ heading: online ? "You're online" : 'Check my plan', text: online ? 'Your device is connected to the internet.' : 'See time and data left on your voucher.' }),
    content,
  });
}

function recover(s, { results, error, phone, ref, ctx }) {
  const content = html`
  <form class="card card-pad lookup" method="post" action="/recover">
    <h3>Recover your voucher</h3>
    <p class="muted">Enter the phone number you paid with. For your security we show vouchers bought from this device, or enter your payment reference.</p>
    <label class="field"><span>Phone number</span>
      <input class="input" type="tel" name="phone" value="${phone || ''}" placeholder="0803 123 4567" required></label>
    <label class="field"><span>Payment reference <em>(if using another device)</em></span>
      <input class="input" name="reference" value="${ref || ''}" placeholder="ZX..." autocomplete="off"></label>
    <button class="btn btn-dark btn-block" type="submit">${icon('search')} Find my voucher</button>
  </form>
  ${error ? html`<div class="alert alert-danger">${icon('alert')}<div>${error}</div></div>` : ''}
  ${results && results.length ? html`
  <div class="card card-pad">
    <h3>Your vouchers</h3>
    <ul class="recover-list">
      ${results.map((r) => html`<li>
        <div><div class="mono">${r.display}</div><div class="fine">${r.plan_name} · ${new Date(r.paid_at).toLocaleDateString('en-NG')}</div></div>
        <a class="btn btn-soft btn-sm" href="/status?code=${r.code}">View</a>
      </li>`)}
    </ul>
  </div>` : ''}`;
  return shell(s, { title: 'Recover voucher', compact: true, hero: heroBlock({ heading: 'Recover voucher', ctx }), content });
}

module.exports = { home, checkout, demoPay, success, pending, failed, statusPage, recover, esc };
