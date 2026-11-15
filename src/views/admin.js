'use strict';
// Admin back-office pages.
const { html, raw, esc, money, duration, dataLabel, bytes, remaining, dateTime, logo, icon, page } = require('./ui');

const NAV = [
  ['/admin', 'grid', 'Dashboard'],
  ['/admin/plans', 'layers', 'Plans'],
  ['/admin/vouchers', 'ticket', 'Vouchers'],
  ['/admin/sales', 'card', 'Sales'],
  ['/admin/online', 'users', 'Online users'],
  ['/admin/router', 'router', 'Router setup'],
  ['/admin/settings', 'gear', 'Settings'],
];

function layout(s, { title, active, actions = '', content, flash, user }) {
  return page({
    title: `${title} · ${s.business_name} Admin`,
    bodyClass: 'admin-body',
    body: html`
<div class="admin">
  <aside class="side" data-side>
    <div class="side-brand">${logo(s.business_name)}<span class="side-tag">Admin</span></div>
    <nav class="side-nav">
      ${NAV.map(([href, ic, label]) => html`<a href="${href}" class="${active === href ? 'active' : ''}">${icon(ic)}<span>${label}</span></a>`)}
    </nav>
    <div class="side-foot">
      <a href="/" target="_blank" rel="noopener" class="side-portal">${icon('wifi')}<span>View portal</span></a>
      <form method="post" action="/admin/logout"><button class="side-logout" type="submit">${icon('logout')}<span>Sign out ${user ? html`<b>${user.u}</b>` : ''}</span></button></form>
    </div>
  </aside>
  <div class="main">
    <header class="main-top">
      <button class="menu-btn" type="button" data-menu aria-label="Menu">${icon('menu')}</button>
      <h1>${title}</h1>
      <div class="main-actions">${actions}</div>
    </header>
    <div class="main-body">
      ${flash ? html`<div class="alert alert-${flash.type || 'success'}">${icon(flash.type === 'danger' ? 'alert' : 'check')}<div>${flash.text}</div></div>` : ''}
      ${content}
    </div>
  </div>
</div>
<div class="tooltip" data-tooltip hidden></div>
<script src="/static/js/admin.js" defer></script>`,
  });
}

function authShell(s, { title, subtitle, content }) {
  return page({
    title: `${title} · ${s.business_name}`,
    bodyClass: 'auth-body',
    body: html`
<div class="auth">
  <div class="auth-card card card-pad">
    <div class="auth-brand">${logo(s.business_name)}</div>
    <h1>${title}</h1>
    <p class="muted">${subtitle}</p>
    ${content}
  </div>
  <p class="auth-foot">${s.business_name} Hotspot Billing</p>
</div>`,
  });
}

function login(s, { error }) {
  return authShell(s, {
    title: 'Welcome back',
    subtitle: 'Sign in to manage your hotspot business.',
    content: html`
    ${error ? html`<div class="alert alert-danger">${icon('alert')}<div>${error}</div></div>` : ''}
    <form method="post" action="/admin/login" class="stack">
      <label class="field"><span>Username</span><input class="input" name="username" autocomplete="username" required autofocus></label>
      <label class="field"><span>Password</span><input class="input" type="password" name="password" autocomplete="current-password" required></label>
      <button class="btn btn-primary btn-lg btn-block" type="submit">Sign in</button>
    </form>`,
  });
}

function setup(s, { error }) {
  return authShell(s, {
    title: 'Create your admin account',
    subtitle: 'First-time setup. This account controls plans, payments and vouchers.',
    content: html`
    ${error ? html`<div class="alert alert-danger">${icon('alert')}<div>${error}</div></div>` : ''}
    <form method="post" action="/admin/setup" class="stack">
      <label class="field"><span>Business name</span><input class="input" name="business_name" value="${s.business_name}" required></label>
      <label class="field"><span>Admin username</span><input class="input" name="username" value="admin" required></label>
      <label class="field"><span>Password <em>(min. 8 characters)</em></span><input class="input" type="password" name="password" minlength="8" autocomplete="new-password" required></label>
      <label class="field"><span>Confirm password</span><input class="input" type="password" name="confirm" minlength="8" autocomplete="new-password" required></label>
      <button class="btn btn-primary btn-lg btn-block" type="submit">Create account</button>
    </form>`,
  });
}

function stat(label, value, sub, ic) {
  return html`<div class="stat card">
    <div class="stat-top"><span class="stat-label">${label}</span><span class="stat-icon">${icon(ic)}</span></div>
    <div class="stat-value">${value}</div>
    ${sub ? html`<div class="stat-sub">${sub}</div>` : ''}
  </div>`;
}

function revenueChart(days, sym) {
  const W = 640;
  const H = 220;
  const padL = 44;
  const padB = 26;
  const padT = 12;
  const max = Math.max(...days.map((d) => d.total), 1);
  const nice = (() => {
    const p = 10 ** Math.floor(Math.log10(max));
    for (const m of [1, 2, 2.5, 5, 10]) if (m * p >= max) return m * p;
    return max;
  })();
  const plotW = W - padL - 8;
  const plotH = H - padT - padB;
  const slot = plotW / days.length;
  const barW = Math.max(6, Math.min(28, slot - 6));
  const y = (v) => padT + plotH - (v / nice) * plotH;
  const grid = [0, 0.5, 1].map((f) => {
    const v = nice * f;
    return `<line x1="${padL}" x2="${W - 8}" y1="${y(v)}" y2="${y(v)}" class="grid"/>
      <text x="${padL - 8}" y="${y(v) + 4}" text-anchor="end" class="axis">${v >= 1000 ? `${+(v / 1000).toFixed(1)}k` : v}</text>`;
  }).join('');
  const bars = days.map((d, i) => {
    const x = padL + i * slot + (slot - barW) / 2;
    const h = Math.max(d.total > 0 ? 2 : 0, padT + plotH - y(d.total));
    const r = Math.min(4, barW / 2, h);
    const top = padT + plotH - h;
    // Rounded top corners only, anchored to the baseline.
    const path = h > 0
      ? `M${x},${padT + plotH} V${top + r} Q${x},${top} ${x + r},${top} H${x + barW - r} Q${x + barW},${top} ${x + barW},${top + r} V${padT + plotH} Z`
      : '';
    const label = i % 2 === days.length % 2 ? '' : `<text x="${x + barW / 2}" y="${H - 8}" text-anchor="middle" class="axis">${esc(d.short)}</text>`;
    const tip = esc(`${d.label}: ${money(d.total, sym)} · ${d.count} sale${d.count === 1 ? '' : 's'}`);
    return `<g class="bar-g" data-tip="${tip}">
      <rect x="${padL + i * slot}" y="${padT}" width="${slot}" height="${plotH}" class="hit"/>
      ${path ? `<path d="${path}" class="bar"/>` : ''}${label}</g>`;
  }).join('');
  return raw(`<svg viewBox="0 0 ${W} ${H}" class="chart" role="img" aria-label="Revenue for the last ${days.length} days">
    ${grid}<line x1="${padL}" x2="${W - 8}" y1="${padT + plotH}" y2="${padT + plotH}" class="baseline"/>${bars}</svg>`);
}

function setupChecklist(s, checks) {
  const items = [
    [checks.paystack, 'Add your Paystack keys', 'Accept card, transfer and USSD payments.', '/admin/settings'],
    [checks.server, 'Set your server address', 'Needed to generate your MikroTik configuration.', '/admin/settings'],
    [checks.router, 'Connect your MikroTik router', 'Paste the generated script into your router terminal.', '/admin/router'],
  ];
  if (items.every(([ok]) => ok)) return '';
  return html`<section class="card card-pad checklist">
    <div class="card-head"><h2>Finish setting up</h2><span class="muted">${items.filter(([ok]) => ok).length} of ${items.length} done</span></div>
    <ul>${items.map(([ok, t, d, href]) => html`<li class="${ok ? 'done' : ''}">
      <span class="check-dot">${ok ? icon('check') : ''}</span>
      <div><b>${t}</b><p>${d}</p></div>
      ${ok ? '' : html`<a class="btn btn-soft btn-sm" href="${href}">Set up</a>`}
    </li>`)}</ul>
  </section>`;
}

function dashboard(s, d) {
  const sym = s.currency_symbol;
  const content = html`
  ${setupChecklist(s, d.checks)}
  <div class="stats">
    ${stat('Revenue today', money(d.today.total, sym), `${d.today.count} sale${d.today.count === 1 ? '' : 's'}`, 'trend')}
    ${stat('Revenue this month', money(d.month.total, sym), `${d.month.count} sales`, 'card')}
    ${stat('Online now', d.online, 'connected devices', 'users')}
    ${stat('Active vouchers', d.activeVouchers, `${d.unusedVouchers} unused in stock`, 'ticket')}
  </div>
  <div class="grid-2">
    <section class="card card-pad">
      <div class="card-head"><div><h2>Revenue</h2><p class="muted">Last 14 days · ${money(d.days.reduce((a, b) => a + b.total, 0), sym)}</p></div></div>
      ${revenueChart(d.days, sym)}
      <details class="table-toggle"><summary>View as table</summary>
        <table class="table table-compact"><thead><tr><th>Day</th><th class="num">Sales</th><th class="num">Revenue</th></tr></thead>
        <tbody>${d.days.map((x) => html`<tr><td>${x.label}</td><td class="num">${x.count}</td><td class="num">${money(x.total, sym)}</td></tr>`)}</tbody></table>
      </details>
    </section>
    <section class="card card-pad">
      <div class="card-head"><div><h2>Top plans</h2><p class="muted">Last 30 days</p></div></div>
      ${d.topPlans.length ? html`<ul class="rank">${d.topPlans.map((p) => html`<li>
        <div class="rank-row"><span>${p.name}</span><b>${money(p.total, sym)}</b></div>
        <div class="rank-bar"><span style="width:${Math.max(3, Math.round((p.total / d.topPlans[0].total) * 100))}%"></span></div>
        <div class="fine">${p.count} sale${p.count === 1 ? '' : 's'}</div></li>`)}</ul>` : html`<div class="empty">No sales yet.</div>`}
    </section>
  </div>
  <div class="grid-2">
    <section class="card">
      <div class="card-head card-pad-x"><h2>Recent sales</h2><a href="/admin/sales" class="link">View all</a></div>
      ${salesTable(d.recentSales, sym, true)}
    </section>
    <section class="card">
      <div class="card-head card-pad-x"><h2>Login activity</h2><span class="muted">from your router</span></div>
      ${d.authLog.length ? html`<div class="table-wrap"><table class="table"><tbody>${d.authLog.map((a) => html`<tr>
        <td><span class="mono">${a.username}</span><div class="fine">${a.mac || '—'}</div></td>
        <td>${a.result === 'accept' ? html`<span class="badge badge-success">Accepted</span>` : html`<span class="badge badge-danger">Rejected</span><div class="fine">${a.reason}</div>`}</td>
        <td class="num fine">${dateTime(a.created_at)}</td></tr>`)}</tbody></table></div>`
        : html`<div class="empty card-pad">No login attempts yet. Once your MikroTik is connected, logins appear here.</div>`}
    </section>
  </div>`;
  return layout(s, { title: 'Dashboard', active: '/admin', content, user: d.user, flash: d.flash });
}

function salesTable(rows, sym, compact = false) {
  if (!rows.length) return html`<div class="empty card-pad">No transactions yet.</div>`;
  const badge = { success: 'badge-success', pending: 'badge-warn', failed: 'badge-danger' };
  return html`<div class="table-wrap"><table class="table">
    <thead><tr><th>Reference</th><th>Plan</th><th>Customer</th>${compact ? '' : html`<th>Voucher</th><th>Channel</th>`}<th>Status</th><th class="num">Amount</th>${compact ? '' : html`<th>Date</th>`}</tr></thead>
    <tbody>${rows.map((t) => html`<tr>
      <td><span class="mono">${t.reference}</span>${compact ? html`<div class="fine">${dateTime(t.created_at)}</div>` : ''}</td>
      <td>${t.plan_name}</td>
      <td>${t.phone || '—'}</td>
      ${compact ? '' : html`<td class="mono">${t.voucher_code || '—'}</td><td>${t.channel || t.gateway}</td>`}
      <td><span class="badge ${badge[t.status] || ''}">${t.status}</span></td>
      <td class="num">${money(t.amount, sym)}</td>
      ${compact ? '' : html`<td class="fine">${dateTime(t.created_at)}</td>`}
    </tr>`)}</tbody></table></div>`;
}

function sales(s, { rows, user, filter, totals }) {
  const content = html`
  <form class="filters" method="get">
    <select class="input" name="status" onchange="this.form.submit()">
      ${['all', 'success', 'pending', 'failed'].map((v) => html`<option value="${v}" ${filter.status === v ? raw('selected') : ''}>${v === 'all' ? 'All statuses' : v[0].toUpperCase() + v.slice(1)}</option>`)}
    </select>
    <input class="input" name="q" value="${filter.q || ''}" placeholder="Search phone or reference">
    <button class="btn btn-soft" type="submit">${icon('search')} Search</button>
  </form>
  <div class="stats stats-3">
    ${stat('Successful sales', totals.count, '', 'check')}
    ${stat('Total revenue', money(totals.total, s.currency_symbol), '', 'trend')}
    ${stat('Average sale', money(totals.count ? Math.round(totals.total / totals.count) : 0, s.currency_symbol), '', 'card')}
  </div>
  <section class="card">${salesTable(rows, s.currency_symbol)}</section>`;
  return layout(s, {
    title: 'Sales', active: '/admin/sales', content, user,
    actions: html`<a class="btn btn-soft" href="/admin/sales.csv">${icon('download')} Export CSV</a>`,
  });
}

function plans(s, { rows, user, flash }) {
  const content = html`
  <div class="plan-admin-grid">
    ${rows.map((p) => html`<article class="card card-pad plan-admin ${p.active ? '' : 'is-off'}">
      <div class="plan-admin-head">
        <div><h3>${p.name}</h3><div class="fine">${p.description}</div></div>
        <div class="plan-admin-badges">
          ${p.popular ? html`<span class="badge badge-brand">Popular</span>` : ''}
          <span class="badge ${p.active ? 'badge-success' : 'badge-neutral'}">${p.active ? 'Live' : 'Hidden'}</span>
        </div>
      </div>
      <div class="plan-admin-price">${money(p.price, s.currency_symbol)}</div>
      <ul class="spec">
        <li><span>Validity</span><b>${duration(p.duration_min)}</b></li>
        <li><span>Data</span><b>${dataLabel(p.data_mb).replace(' data', '')}</b></li>
        <li><span>Speed</span><b>${p.download_mbps > 0 ? `${+p.download_mbps}↓ / ${+p.upload_mbps || '∞'}↑ Mbps` : 'Unlimited'}</b></li>
        <li><span>Devices</span><b>${p.devices}</b></li>
        <li><span>Sold</span><b>${p.sold}</b></li>
      </ul>
      <div class="row-actions">
        <a class="btn btn-soft btn-sm" href="/admin/plans/${p.id}">Edit</a>
        <form method="post" action="/admin/plans/${p.id}/toggle"><button class="btn btn-ghost btn-sm" type="submit">${p.active ? 'Hide' : 'Publish'}</button></form>
      </div>
    </article>`)}
  </div>`;
  return layout(s, {
    title: 'Plans', active: '/admin/plans', content, user, flash,
    actions: html`<a class="btn btn-primary" href="/admin/plans/new">${icon('plus')} New plan</a>`,
  });
}

function planForm(s, { plan, user, error }) {
  const p = plan || { name: '', description: '', price: '', duration_min: 60, data_mb: 0, download_mbps: 5, upload_mbps: 2, devices: 1, popular: 0, active: 1, sort_order: 0 };
  const unit = p.duration_min % 1440 === 0 ? 'days' : p.duration_min % 60 === 0 ? 'hours' : 'minutes';
  const amount = unit === 'days' ? p.duration_min / 1440 : unit === 'hours' ? p.duration_min / 60 : p.duration_min;
  const content = html`
  ${error ? html`<div class="alert alert-danger">${icon('alert')}<div>${error}</div></div>` : ''}
  <form class="card card-pad form-grid" method="post" action="${plan ? `/admin/plans/${plan.id}` : '/admin/plans'}">
    <label class="field span-2"><span>Plan name</span><input class="input" name="name" value="${p.name}" placeholder="e.g. Daily Pass" required></label>
    <label class="field span-2"><span>Short description</span><input class="input" name="description" value="${p.description}" placeholder="e.g. Unlimited access for a full day"></label>
    <label class="field"><span>Price (${s.currency_symbol})</span><input class="input" type="number" name="price" min="0" step="1" value="${p.price}" required></label>
    <div class="field"><span>Validity</span><div class="input-group">
      <input class="input" type="number" name="duration_value" min="1" value="${amount}" required>
      <select class="input" name="duration_unit">${['minutes', 'hours', 'days'].map((u) => html`<option ${u === unit ? raw('selected') : ''}>${u}</option>`)}</select>
    </div></div>
    <label class="field"><span>Data cap (MB) <em>0 = unlimited</em></span><input class="input" type="number" name="data_mb" min="0" value="${p.data_mb}"></label>
    <label class="field"><span>Devices per voucher</span><input class="input" type="number" name="devices" min="1" max="10" value="${p.devices}"></label>
    <label class="field"><span>Download speed (Mbps) <em>0 = unlimited</em></span><input class="input" type="number" step="0.5" name="download_mbps" min="0" value="${p.download_mbps}"></label>
    <label class="field"><span>Upload speed (Mbps) <em>0 = unlimited</em></span><input class="input" type="number" step="0.5" name="upload_mbps" min="0" value="${p.upload_mbps}"></label>
    <label class="field"><span>Display order</span><input class="input" type="number" name="sort_order" value="${p.sort_order}"></label>
    <div class="field"><span>Options</span>
      <label class="check"><input type="checkbox" name="active" value="1" ${p.active ? raw('checked') : ''}> Show on portal</label>
      <label class="check"><input type="checkbox" name="popular" value="1" ${p.popular ? raw('checked') : ''}> Highlight as “Most popular”</label>
    </div>
    <div class="span-2 form-actions">
      <a class="btn btn-ghost" href="/admin/plans">Cancel</a>
      <button class="btn btn-primary" type="submit">${plan ? 'Save changes' : 'Create plan'}</button>
    </div>
  </form>`;
  return layout(s, { title: plan ? `Edit ${plan.name}` : 'New plan', active: '/admin/plans', content, user });
}

function voucherList(s, { rows, plans: planRows, user, filter, flash, batches }) {
  const badge = { unused: 'badge-neutral', active: 'badge-success', expired: 'badge-danger', disabled: 'badge-danger' };
  const content = html`
  <div class="grid-2 grid-wide-left">
    <section class="card">
      <form class="filters card-pad-x card-pad-top" method="get">
        <select class="input" name="status" onchange="this.form.submit()">
          ${['all', 'unused', 'active', 'expired', 'disabled'].map((v) => html`<option value="${v}" ${filter.status === v ? raw('selected') : ''}>${v === 'all' ? 'All vouchers' : v[0].toUpperCase() + v.slice(1)}</option>`)}
        </select>
        <input class="input" name="q" value="${filter.q || ''}" placeholder="Code, phone or batch">
        <button class="btn btn-soft" type="submit">${icon('search')}</button>
      </form>
      ${rows.length ? html`<div class="table-wrap"><table class="table">
        <thead><tr><th>Code</th><th>Plan</th><th>Status</th><th>Usage</th><th>Expires</th><th></th></tr></thead>
        <tbody>${rows.map((v) => html`<tr>
          <td><span class="mono">${v.display}</span><div class="fine">${v.source === 'online' ? v.phone || 'Online' : `Batch ${v.batch || ''}`}</div></td>
          <td>${v.plan_name}</td>
          <td><span class="badge ${badge[v.status] || ''}">${v.status}</span></td>
          <td>${bytes(v.bytes_used)}<div class="fine">${JSON.parse(v.macs).length}/${v.devices} device</div></td>
          <td class="fine">${v.expires_at ? (v.expires_at > Date.now() ? `in ${remaining(v.expires_at - Date.now())}` : dateTime(v.expires_at)) : 'Not started'}</td>
          <td class="num"><form method="post" action="/admin/vouchers/${v.id}/toggle" class="inline">
            <button class="btn btn-ghost btn-sm" type="submit">${v.status === 'disabled' ? 'Enable' : 'Disable'}</button></form>
            <form method="post" action="/admin/vouchers/${v.id}/reset" class="inline" data-confirm="Reset devices for this voucher? The customer can then log in on a new device.">
            <button class="btn btn-ghost btn-sm" type="submit" title="Clear device lock">Reset</button></form></td>
        </tr>`)}</tbody></table></div>` : html`<div class="empty card-pad">No vouchers match.</div>`}
    </section>
    <div class="stack">
      <section class="card card-pad">
        <h2>Generate vouchers</h2>
        <p class="muted">Print voucher cards to sell offline through attendants or shops.</p>
        <form method="post" action="/admin/vouchers/generate" class="stack">
          <label class="field"><span>Plan</span><select class="input" name="plan_id" required>
            ${planRows.map((p) => html`<option value="${p.id}">${p.name} · ${money(p.price, s.currency_symbol)}</option>`)}</select></label>
          <label class="field"><span>Quantity</span><input class="input" type="number" name="quantity" min="1" max="500" value="20" required></label>
          <button class="btn btn-primary btn-block" type="submit">${icon('plus')} Generate</button>
        </form>
      </section>
      <section class="card card-pad">
        <h2>Recent batches</h2>
        ${batches.length ? html`<ul class="batch-list">${batches.map((b) => html`<li>
          <div><b>${b.batch}</b><div class="fine">${b.plan_name} · ${b.count} cards · ${b.unused} unused</div></div>
          <a class="btn btn-soft btn-sm" href="/admin/vouchers/print/${encodeURIComponent(b.batch)}" target="_blank">${icon('printer')} Print</a>
        </li>`)}</ul>` : html`<div class="empty">No batches yet.</div>`}
      </section>
    </div>
  </div>`;
  return layout(s, { title: 'Vouchers', active: '/admin/vouchers', content, user, flash });
}

function printCards(s, { batch, rows }) {
  return page({
    title: `Voucher cards ${batch}`,
    bodyClass: 'print-body',
    body: html`
<div class="print-bar"><b>${rows.length} voucher cards · ${batch}</b><button class="btn btn-primary" onclick="window.print()">${icon('printer')} Print</button></div>
<div class="cards-sheet">
  ${rows.map((v) => html`<div class="vcard">
    <div class="vcard-top">${logo(s.business_name)}<span class="vcard-price">${money(v.price, s.currency_symbol)}</span></div>
    <div class="vcard-plan">${v.plan_name} · ${duration(v.duration_min)}${v.data_mb ? ` · ${dataLabel(v.data_mb)}` : ''}</div>
    <div class="vcard-code">${v.display}</div>
    <div class="vcard-help">Connect to <b>${s.business_name}</b> Wi-Fi → tap “I have a voucher” → enter code.</div>
  </div>`)}
</div>`,
  });
}

function online(s, { rows, user }) {
  const content = html`
  <section class="card">
    ${rows.length ? html`<div class="table-wrap"><table class="table">
      <thead><tr><th>Voucher</th><th>Plan</th><th>Device</th><th>Connected</th><th class="num">Downloaded</th><th class="num">Uploaded</th><th>Time left</th></tr></thead>
      <tbody>${rows.map((r) => html`<tr>
        <td class="mono">${r.display}</td><td>${r.plan_name || '—'}</td>
        <td><span class="mono">${r.mac}</span><div class="fine">${r.ip}</div></td>
        <td class="fine">${remaining(r.session_time * 1000)}</td>
        <td class="num">${bytes(r.output_bytes)}</td><td class="num">${bytes(r.input_bytes)}</td>
        <td class="fine">${r.expires_at ? remaining(r.expires_at - Date.now()) : '—'}</td>
      </tr>`)}</tbody></table></div>`
      : html`<div class="empty card-pad">${icon('users')}<p>Nobody is online right now.</p><p class="fine">Sessions appear here when your MikroTik sends accounting updates (every 5 minutes).</p></div>`}
  </section>`;
  return layout(s, { title: 'Online users', active: '/admin/online', content, user, actions: html`<span class="badge badge-success">${rows.length} online</span>` });
}

function routerSetup(s, { user, script, portalUrl, lastSeen }) {
  const content = html`
  <div class="status-strip card card-pad">
    <div>${lastSeen ? html`<span class="badge badge-success">Router connected</span> <span class="muted">Last RADIUS request ${dateTime(lastSeen)}</span>`
      : html`<span class="badge badge-warn">Waiting for router</span> <span class="muted">No requests received from your MikroTik yet.</span>`}</div>
  </div>

  <section class="card card-pad">
    <h2>How your network connects</h2>
    <div class="topology">
      <div class="node"><b>MTN ODU</b><span>Internet (WAN)</span></div><div class="link-line"></div>
      <div class="node node-brand"><b>MikroTik router</b><span>Hotspot + walled garden</span></div><div class="link-line"></div>
      <div class="node"><b>Access points</b><span>Customers' Wi-Fi</span></div>
    </div>
    <p class="muted">The MikroTik redirects new devices to this portal. After payment it asks ${s.business_name} (via RADIUS) whether the voucher is valid, and enforces time, speed and data limits.</p>
  </section>

  <section class="card card-pad">
    <div class="card-head"><div><h2>Step 1 · Create the hotspot</h2></div></div>
    <ol class="steps-list">
      <li>Connect the MTN ODU's LAN/PoE cable to <b>ether1</b> of your MikroTik. Plug your access point(s) into the other ports.</li>
      <li>In WinBox open <b>IP → DHCP Client</b> and add one on <b>ether1</b> so the MikroTik gets internet from the ODU.</li>
      <li>Open <b>IP → Hotspot → Hotspot Setup</b>, choose your LAN bridge/Wi-Fi interface and accept the defaults. When asked for <b>DNS name</b> enter <code>${s.hotspot_dns}</code>. Skip creating a local user.</li>
    </ol>
  </section>

  <section class="card card-pad">
    <div class="card-head"><div><h2>Step 2 · Paste this script into the MikroTik terminal</h2><p class="muted">WinBox → New Terminal → paste → Enter</p></div>
      <button class="btn btn-soft btn-sm" type="button" data-copy-target="#rsc">${icon('copy')} Copy</button></div>
    ${!s.server_ip || !s.portal_host ? html`<div class="alert alert-warn">${icon('alert')}<div>Set your <b>server public IP</b> and <b>portal domain</b> in <a href="/admin/settings">Settings</a> first — the script below uses placeholders.</div></div>` : ''}
    <pre class="code" id="rsc">${script}</pre>
  </section>

  <section class="card card-pad">
    <div class="card-head"><div><h2>Step 3 · Upload the redirect page</h2><p class="muted">Replaces the default MikroTik login page so customers land on ${portalUrl}</p></div></div>
    <ol class="steps-list">
      <li>Download <a class="link" href="/admin/router/login.html">login.html</a> and <a class="link" href="/admin/router/alogin.html">alogin.html</a>.</li>
      <li>In WinBox open <b>Files</b>, open the <code>hotspot</code> folder and drag both files in (replace the existing ones).</li>
      <li>Connect a phone to your Wi-Fi and open any website — the ${s.business_name} portal should appear.</li>
    </ol>
  </section>`;
  return layout(s, { title: 'Router setup', active: '/admin/router', content, user });
}

function settingsPage(s, { user, flash, publicUrl }) {
  const mask = (v) => (v ? `${v.slice(0, 7)}••••••${v.slice(-4)}` : '');
  const content = html`
  <form method="post" action="/admin/settings" class="stack">
    <section class="card card-pad form-grid">
      <div class="span-2"><h2>Business</h2><p class="muted">Shown on your customer portal and voucher cards.</p></div>
      <label class="field"><span>Business name</span><input class="input" name="business_name" value="${s.business_name}" required></label>
      <label class="field"><span>Location <em>(optional)</em></span><input class="input" name="hotspot_location" value="${s.hotspot_location}" placeholder="e.g. Ikeja, Lagos"></label>
      <label class="field span-2"><span>Tagline</span><input class="input" name="tagline" value="${s.tagline}"></label>
      <label class="field"><span>Support phone</span><input class="input" name="support_phone" value="${s.support_phone}" placeholder="0803 123 4567"></label>
      <label class="field"><span>WhatsApp number</span><input class="input" name="support_whatsapp" value="${s.support_whatsapp}" placeholder="0803 123 4567"></label>
    </section>

    <section class="card card-pad form-grid">
      <div class="span-2"><h2>Paystack payments</h2><p class="muted">Get your keys at dashboard.paystack.com → Settings → API Keys &amp; Webhooks. Set the webhook URL to <code>${publicUrl}/paystack/webhook</code>.</p></div>
      <label class="field"><span>Public key</span><input class="input mono" name="paystack_public_key" value="${s.paystack_public_key}" placeholder="pk_live_..."></label>
      <label class="field"><span>Secret key ${s.paystack_secret_key ? html`<em>saved: ${mask(s.paystack_secret_key)}</em>` : ''}</span><input class="input mono" type="password" name="paystack_secret_key" placeholder="${s.paystack_secret_key ? 'Leave blank to keep current key' : 'sk_live_...'}" autocomplete="off"></label>
      <label class="check span-2"><input type="checkbox" name="demo_payments" value="1" ${s.demo_payments === '1' ? raw('checked') : ''}> Allow simulated (demo) payments while Paystack keys are not set</label>
    </section>

    <section class="card card-pad form-grid">
      <div class="span-2"><h2>Server &amp; router</h2><p class="muted">Used to generate your MikroTik configuration.</p></div>
      <label class="field"><span>Portal domain</span><input class="input mono" name="portal_host" value="${s.portal_host}" placeholder="wifi.yourdomain.com"></label>
      <label class="field"><span>Server public IP</span><input class="input mono" name="server_ip" value="${s.server_ip}" placeholder="203.0.113.10"></label>
      <label class="field"><span>Hotspot DNS name</span><input class="input mono" name="hotspot_dns" value="${s.hotspot_dns}"></label>
      <label class="field"><span>RADIUS secret</span><input class="input mono" name="radius_secret" value="${s.radius_secret}" required minlength="8"></label>
    </section>

    <div class="form-actions"><button class="btn btn-primary btn-lg" type="submit">Save settings</button></div>
  </form>

  <form method="post" action="/admin/password" class="card card-pad form-grid">
    <div class="span-2"><h2>Change password</h2></div>
    <label class="field"><span>Current password</span><input class="input" type="password" name="current" autocomplete="current-password" required></label>
    <span></span>
    <label class="field"><span>New password</span><input class="input" type="password" name="password" minlength="8" autocomplete="new-password" required></label>
    <label class="field"><span>Confirm new password</span><input class="input" type="password" name="confirm" minlength="8" autocomplete="new-password" required></label>
    <div class="span-2 form-actions"><button class="btn btn-dark" type="submit">Update password</button></div>
  </form>`;
  return layout(s, { title: 'Settings', active: '/admin/settings', content, user, flash });
}

module.exports = { login, setup, dashboard, sales, plans, planForm, voucherList, printCards, online, routerSetup, settingsPage };
