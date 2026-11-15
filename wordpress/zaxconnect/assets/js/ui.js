(function () {
  'use strict';
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var root = document.documentElement;

  // Light / dark toggle (remembered per device)
  function currentTheme() {
    var t = root.getAttribute('data-theme');
    if (t) return t;
    return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  }
  $$('[data-theme-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var next = currentTheme() === 'dark' ? 'light' : 'dark';
      root.setAttribute('data-theme', next);
      try { localStorage.setItem('zx-theme', next); } catch (e) {}
      $$('[data-theme-toggle] svg').forEach(function (s) { s.style.animation = 'none'; void s.offsetWidth; s.style.animation = ''; });
    });
  });

  // Dashboard drawer (sidebar on small screens)
  var side = $('[data-side]');
  var backdrop = $('[data-backdrop]');
  function setDrawer(open) {
    if (!side) return;
    side.classList.toggle('open', open);
    if (backdrop) backdrop.classList.toggle('open', open);
  }
  $$('[data-menu]').forEach(function (b) { b.addEventListener('click', function (e) { e.preventDefault(); setDrawer(!side.classList.contains('open')); }); });
  if (backdrop) backdrop.addEventListener('click', function () { setDrawer(false); });

  // Copy buttons
  $$('[data-copy]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var target = btn.getAttribute('data-copy');
      var text = target.charAt(0) === '#' ? ($(target) || {}).textContent || '' : target;
      var label = btn.innerHTML;
      var done = function () { btn.textContent = 'Copied!'; setTimeout(function () { btn.innerHTML = label; }, 1600); };
      if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(text).then(done, done);
      else {
        var ta = document.createElement('textarea'); ta.value = text; document.body.appendChild(ta); ta.select();
        try { document.execCommand('copy'); } catch (e) {}
        ta.remove(); done();
      }
    });
  });

  // Confirmations
  $$('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) { if (!window.confirm(f.getAttribute('data-confirm'))) e.preventDefault(); });
  });

  // Voucher code inputs: XXXX-XXXX
  $$('.input-code').forEach(function (input) {
    input.addEventListener('input', function () {
      var v = input.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 8);
      input.value = v.length > 4 ? v.slice(0, 4) + '-' + v.slice(4) : v;
    });
  });

  // Portal: plan picker + sticky continue bar
  var bar = $('[data-select-bar]');
  $$('[data-plan]').forEach(function (card) {
    card.addEventListener('click', function (e) {
      if (window.matchMedia('(min-width: 861px)').matches) return; // desktop cards use their own Buy button
      e.preventDefault();
      $$('[data-plan]').forEach(function (c) { c.classList.toggle('on', c === card); c.setAttribute('aria-pressed', c === card); });
      if (bar) {
        bar.classList.remove('hide');
        $('[data-sel-name]', bar).textContent = card.getAttribute('data-name') + ' · ' + card.getAttribute('data-price');
        $('[data-sel-go]', bar).setAttribute('href', card.getAttribute('data-href'));
      }
    });
  });
  // Plan filter chips
  $$('[data-filter]').forEach(function (chip) {
    chip.addEventListener('click', function () {
      var cat = chip.getAttribute('data-filter');
      $$('[data-filter]').forEach(function (c) { c.classList.toggle('on', c === chip); });
      $$('[data-plan]').forEach(function (c) { c.classList.toggle('hide', cat !== 'all' && c.getAttribute('data-cat') !== cat); });
    });
  });

  // Portal: voucher form checks the code, then logs in through the router
  var vf = $('[data-voucher-form]');
  if (vf) {
    vf.addEventListener('submit', function (e) {
      e.preventDefault();
      var msg = $('[data-msg]', vf); var btn = $('button[type=submit]', vf);
      msg.className = 'form-msg'; msg.textContent = 'Checking voucher…'; btn.disabled = true;
      fetch(vf.getAttribute('data-api'), {
        method: 'POST', headers: { 'Content-Type': 'application/json' }, credentials: 'same-origin',
        body: JSON.stringify({ code: vf.code.value })
      }).then(function (r) { return r.json(); }).then(function (d) {
        msg.textContent = d.message; msg.className = 'form-msg ' + (d.ok ? 'ok' : 'err');
        if (d.ok && d.loginUrl) window.location.href = d.loginUrl;
        else if (d.ok) setTimeout(function () { window.location.href = d.statusUrl; }, 1200);
        else btn.disabled = false;
      }).catch(function () { vf.submit(); });
    });
  }

  // Success page: auto-connect countdown
  var auto = $('[data-autoconnect]'); var cd = $('[data-countdown]');
  if (auto && cd) {
    var n = 5;
    var timer = setInterval(function () { n -= 1; cd.textContent = n + 's'; if (n <= 0) { clearInterval(timer); window.location.href = auto.href; } }, 1000);
  }

  // Pending payment: poll the order status
  var poll = $('[data-poll]');
  if (poll) {
    var url = poll.getAttribute('data-poll'); var go = poll.getAttribute('data-go'); var tries = 0;
    var waitReady = poll.hasAttribute('data-poll-ready');
    var check = function () {
      tries += 1;
      fetch(url, { credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (d) {
        var done = waitReady ? (d.ready || tries >= 15) : (d.status !== 'pending' || tries % 5 === 0);
        if (done) window.location.href = go;
        else setTimeout(check, waitReady ? 2500 : 4000);
      }).catch(function () { setTimeout(check, 6000); });
    };
    setTimeout(check, waitReady ? 2000 : 3000);
  }

  // Live countdowns
  $$('[data-until]').forEach(function (el) {
    var end = Number(el.getAttribute('data-until'));
    if (!end) return;
    var tick = function () {
      var s = Math.max(0, Math.floor((end - Date.now()) / 1000));
      var d = Math.floor(s / 86400), h = Math.floor((s % 86400) / 3600), m = Math.floor((s % 3600) / 60);
      el.textContent = d ? d + 'd ' + h + 'h' : h ? h + 'h ' + m + 'm' : m + 'm ' + (s % 60) + 's';
    };
    tick(); setInterval(tick, 1000);
  });

  // Branding forms: colour swatches + logo upload preview
  $$('[data-swatches]').forEach(function (wrap) {
    var input = $(wrap.getAttribute('data-swatches'));
    var update = function (val) {
      $$('.swatch', wrap).forEach(function (s) { s.classList.toggle('on', s.getAttribute('data-color').toLowerCase() === val.toLowerCase()); });
      document.dispatchEvent(new CustomEvent('zx:brand', { detail: { color: val } }));
    };
    $$('.swatch', wrap).forEach(function (s) {
      s.addEventListener('click', function () { input.value = s.getAttribute('data-color'); update(input.value); });
    });
    input.addEventListener('input', function () { if (/^#[0-9a-f]{6}$/i.test(input.value)) update(input.value); });
  });
  $$('[data-logo-input]').forEach(function (file) {
    var target = $(file.getAttribute('data-logo-input'));
    var msg = $(file.getAttribute('data-logo-msg') || '#none');
    file.addEventListener('change', function () {
      var f = file.files && file.files[0];
      if (!f) return;
      if (!/^image\/(png|jpeg|webp)$/.test(f.type)) { if (msg) msg.textContent = 'Use a PNG, JPG or WebP image.'; return; }
      if (f.size > 500 * 1024) { if (msg) msg.textContent = 'Logo must be smaller than 500 KB.'; return; }
      var reader = new FileReader();
      reader.onload = function () {
        target.value = reader.result;
        if (msg) msg.textContent = f.name + ' ready';
        document.dispatchEvent(new CustomEvent('zx:brand', { detail: { logo: reader.result } }));
      };
      reader.readAsDataURL(f);
    });
  });

  // Glass header gets stronger once you scroll
  var nav = $('[data-nav]');
  if (nav) {
    var onScroll = function () { nav.classList.toggle('scrolled', window.scrollY > 8); };
    onScroll(); window.addEventListener('scroll', onScroll, { passive: true });
  }
  // Toast notifications fade away
  $$('[data-toast]').forEach(function (t) {
    setTimeout(function () { t.style.transition = 'opacity .4s, transform .4s'; t.style.opacity = '0'; t.style.transform = 'translate(-50%, -10px)'; }, 4200);
    setTimeout(function () { if (t.parentNode) t.parentNode.removeChild(t); }, 4800);
  });
  // Wallet quick amounts
  var amt = $('[data-amount]');
  $$('[data-quick]').forEach(function (b) {
    b.addEventListener('click', function () {
      if (amt) amt.value = b.getAttribute('data-quick');
      $$('[data-quick]').forEach(function (x) { x.classList.toggle('on', x === b); });
    });
  });
  // Animated balance counter
  $$('[data-count]').forEach(function (el) {
    var end = Number(el.getAttribute('data-count')) || 0;
    if (!end) return;
    var start = performance.now(), dur = 900;
    var step = function (now) {
      var p = Math.min(1, (now - start) / dur), v = Math.round(end * (1 - Math.pow(1 - p, 3)));
      el.textContent = '₦' + v.toLocaleString('en-NG');
      if (p < 1) requestAnimationFrame(step);
    };
    requestAnimationFrame(step);
  });
})();
