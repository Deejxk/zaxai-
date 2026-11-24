/* ZaxProxy panel behaviour */
(function () {
  'use strict';
  var ZP = window.ZP || {};
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var money = function (n) { n = Number(n); var whole = Math.abs(n - Math.round(n)) < 0.005; return (ZP.cur || '') + n.toFixed(whole ? 0 : 2).replace(/\B(?=(\d{3})+(?!\d))/g, ','); };

  /* ---------- Theme ---------- */
  function setTheme(t) {
    document.documentElement.setAttribute('data-theme', t);
    try { localStorage.setItem('zp-theme', t); } catch (e) {}
    var meta = $('#zp-theme-color');
    if (meta) meta.setAttribute('content', t === 'light' ? '#f4f5fa' : '#090b12');
  }
  function toggleTheme() {
    setTheme(document.documentElement.getAttribute('data-theme') === 'light' ? 'dark' : 'light');
  }
  setTheme(document.documentElement.getAttribute('data-theme') || 'dark');
  $$('#zp-theme,[data-zp-theme-toggle]').forEach(function (b) { b.addEventListener('click', toggleTheme); });

  /* ---------- Toasts ---------- */
  function toast(msg) {
    var box = $('#zp-toasts');
    if (!box) return;
    var el = document.createElement('div');
    el.className = 'zp-toast';
    el.textContent = msg;
    box.appendChild(el);
    setTimeout(function () { el.remove(); }, 2500);
  }
  function copy(text, label) {
    var done = function () { toast(label || 'Copied to clipboard'); };
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(text).then(done, fallback);
    } else { fallback(); }
    function fallback() {
      var ta = document.createElement('textarea');
      ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
      document.body.appendChild(ta); ta.select();
      try { document.execCommand('copy'); done(); } catch (e) { toast('Could not copy'); }
      ta.remove();
    }
  }

  /* ---------- Menus & sheets ---------- */
  document.addEventListener('click', function (e) {
    var trigger = e.target.closest('[data-zp-menu]');
    $$('.zp-menu__panel').forEach(function (p) {
      if (trigger && p.previousElementSibling === trigger) { p.hidden = !p.hidden; }
      else if (!e.target.closest('.zp-menu__panel')) { p.hidden = true; }
    });
    var opener = e.target.closest('[data-zp-sheet]');
    if (opener) { openSheet(opener.getAttribute('data-zp-sheet')); }
    if (e.target.closest('[data-zp-close]')) { var s = e.target.closest('.zp-sheet'); if (s) s.hidden = true; }
    var cp = e.target.closest('[data-zp-copy]');
    if (cp) { copy(cp.getAttribute('data-zp-copy'), cp.getAttribute('data-zp-copied')); }
    var reveal = e.target.closest('[data-zp-reveal]');
    if (reveal) { var inp = reveal.parentNode.querySelector('input'); inp.type = inp.type === 'password' ? 'text' : 'password'; }
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') { $$('.zp-sheet').forEach(function (s) { s.hidden = true; }); $$('.zp-menu__panel').forEach(function (p) { p.hidden = true; }); }
  });
  function openSheet(id) { var s = document.getElementById(id); if (s) s.hidden = false; }

  /* ---------- Submit feedback ---------- */
  $$('form[method="post"]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      if (e.defaultPrevented) return;
      var btn = e.submitter && e.submitter.form === f ? e.submitter : f.querySelector('button[type="submit"]');
      if (btn && !btn.hasAttribute('form')) { setTimeout(function () { btn.classList.add('is-loading'); btn.disabled = true; }, 0); }
    });
  });

  /* ---------- Count-up numbers ---------- */
  $$('[data-zp-count]').forEach(function (el) {
    var target = parseFloat(el.getAttribute('data-zp-count')) || 0;
    var dec = parseInt(el.getAttribute('data-decimals') || '0', 10);
    var prefix = el.getAttribute('data-prefix') || '';
    if (!target || matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    var start = null, dur = 900;
    if (dec && Math.abs(target - Math.round(target)) < 0.005) dec = 0;
    var fmt = function (v) { return prefix + v.toFixed(dec).replace(/\B(?=(\d{3})+(?!\d))/g, ','); };
    function step(ts) {
      if (!start) start = ts;
      var p = Math.min(1, (ts - start) / dur);
      el.textContent = fmt(target * (1 - Math.pow(1 - p, 3)));
      if (p < 1) requestAnimationFrame(step);
    }
    el.textContent = fmt(0);
    requestAnimationFrame(step);
  });

  /* ---------- AJAX helper ---------- */
  function api(act, params, body) {
    var url = ZP.ajax + (ZP.ajax.indexOf('?') > -1 ? '&' : '?') + 'act=' + act + '&nonce=' + encodeURIComponent(ZP.nonce);
    Object.keys(params || {}).forEach(function (k) { url += '&' + k + '=' + encodeURIComponent(params[k]); });
    var opt = { credentials: 'same-origin', headers: { 'Accept': 'application/json' } };
    if (body) { opt.method = 'POST'; opt.body = body; }
    return fetch(url, opt).then(function (r) { return r.json(); }).then(function (j) {
      if (!j.ok) throw new Error(j.error || 'Something went wrong');
      return j.data;
    });
  }

  /* ---------- Buy form ---------- */
  var buy = $('#zp-buy');
  if (buy) (function () {
    var f = buy.elements, opts = null, quoteTimer = null, quoteSeq = 0;
    var labels = {};
    $$('.zp-typepick__item', buy).forEach(function (b) { labels[b.getAttribute('data-type')] = b.querySelector('strong').textContent; });
    var btns = [$('#zp-buy-btn'), $('#zp-bar-btn')];
    var note = $('#zp-sum-note');

    function setReady(ok) { btns.forEach(function (b) { b.disabled = !ok; }); }
    function setNote(text, err) { note.textContent = text; note.classList.toggle('is-error', !!err); }
    function setTotal(text) {
      ['#zp-sum-total', '#zp-bar-total'].forEach(function (s) { var el = $(s); el.textContent = text; el.classList.remove('zp-bump'); void el.offsetWidth; el.classList.add('zp-bump'); });
    }

    function loadType(type) {
      f.type.value = type; buy.setAttribute('data-type', type);
      $$('.zp-typepick__item', buy).forEach(function (b) { b.classList.toggle('is-active', b.getAttribute('data-type') === type); });
      $('#zp-sum-type').textContent = labels[type];
      $('#zp-mobile-fields').hidden = type !== 'mobile';
      $('#zp-protocol-field').hidden = type !== 'ipv6';
      var min = type === 'ipv6' ? 10 : 1;
      f.qty.min = min; if (+f.qty.value < min) f.qty.value = min;
      $('#zp-qty-hint').textContent = type === 'ipv6' ? 'Minimum 10 for IPv6.' : '';
      f.country.value = ''; f.days.value = '';
      $('#zp-sum-country').textContent = '—'; $('#zp-sum-days').textContent = '—'; $('#zp-sum-qty').textContent = f.qty.value;
      $('#zp-countries').innerHTML = '<div class="zp-skeleton-grid"><i></i><i></i><i></i><i></i><i></i><i></i></div>';
      $('#zp-days').innerHTML = '';
      setReady(false); setTotal('—'); $('#zp-sum-unit').textContent = '—';
      setNote('Loading available locations…');
      api('options', { type: type }).then(function (data) {
        if (f.type.value !== type) return;
        opts = data; renderCountries(''); renderDays();
        setNote('Choose a location and period to see your price.');
      }).catch(function (e) {
        $('#zp-countries').innerHTML = '';
        setNote(e.message, true);
      });
    }

    function renderCountries(filter) {
      var box = $('#zp-countries'); box.innerHTML = '';
      var list = (opts.countries || []).filter(function (c) { return !filter || c.name.toLowerCase().indexOf(filter) > -1 || c.code.toLowerCase().indexOf(filter) > -1; });
      if (!list.length) { box.innerHTML = '<p class="zp-muted">No locations found.</p>'; return; }
      list.forEach(function (c, i) {
        var b = document.createElement('button');
        b.type = 'button'; b.className = 'zp-country' + (f.country.value === c.code ? ' is-active' : '');
        b.style.animationDelay = Math.min(i * 18, 400) + 'ms';
        var flag = document.createElement('i'); flag.textContent = c.flag;
        var name = document.createElement('span'); name.textContent = c.name;
        b.appendChild(flag); b.appendChild(name);
        b.addEventListener('click', function () {
          f.country.value = c.code;
          $$('.zp-country', box).forEach(function (x) { x.classList.remove('is-active'); });
          b.classList.add('is-active');
          $('#zp-sum-country').textContent = c.flag + ' ' + c.name;
          if (f.type.value === 'mobile') renderOperators(c.code);
          quote();
        });
        box.appendChild(b);
      });
    }

    function renderOperators(code) {
      var ops = (opts.tariffs && opts.tariffs[code]) || [];
      f.operator.innerHTML = '';
      ops.forEach(function (o) { var op = document.createElement('option'); op.value = o.name; op.textContent = o.name; f.operator.appendChild(op); });
      renderRotation();
    }
    function renderRotation() {
      var ops = (opts.tariffs && opts.tariffs[f.country.value]) || [];
      var cur = ops.filter(function (o) { return o.name === f.operator.value; })[0];
      f.rotation.innerHTML = '';
      ((cur && cur.rotation) || []).forEach(function (r) {
        var op = document.createElement('option'); op.value = r; op.textContent = r === 0 ? 'On demand (by link)' : 'Every ' + r + ' min'; f.rotation.appendChild(op);
      });
    }

    function renderDays() {
      var box = $('#zp-days'); box.innerHTML = '';
      (opts.days || []).forEach(function (d) {
        var b = document.createElement('button'); b.type = 'button';
        b.textContent = d + (d === 1 ? ' day' : ' days');
        b.addEventListener('click', function () {
          f.days.value = d;
          $$('button', box).forEach(function (x) { x.classList.remove('is-active'); });
          b.classList.add('is-active');
          $('#zp-sum-days').textContent = b.textContent;
          quote();
        });
        box.appendChild(b);
      });
    }

    function quote() {
      clearTimeout(quoteTimer);
      $('#zp-sum-qty').textContent = f.qty.value;
      $('#zp-goal-text').hidden = f.goal.value !== 'other';
      $('#zp-authip').hidden = f.auth_type.value !== 'ip';
      if (!f.country.value || !f.days.value) { setReady(false); return; }
      setReady(false); setNote('Calculating price…');
      quoteTimer = setTimeout(function () {
        var seq = ++quoteSeq;
        var fd = new FormData(buy);
        // Price does not depend on these; send safe placeholders so a half-typed value does not block the quote.
        if (f.goal.value === 'other' && (f.goal_text.value || '').length < 5) fd.set('goal_text', 'general use');
        fd.set('auth_type', 'login');
        api('quote', {}, fd).then(function (q) {
          if (seq !== quoteSeq) return;
          $('#zp-sum-unit').textContent = money(q.unit);
          setTotal(money(q.total));
          if (q.balance < q.total) {
            setNote('You need ' + money(q.total - q.balance) + ' more in your wallet for this order.', true);
            setReady(true);
            btns.forEach(function (b) { b.lastChild.textContent = 'Add funds to continue'; });
          } else {
            setNote('Charged from your wallet. Delivery starts immediately.');
            setReady(true);
            btns.forEach(function (b) { b.lastChild.textContent = 'Pay from wallet'; });
          }
        }).catch(function (e) {
          if (seq !== quoteSeq) return;
          setTotal('—'); $('#zp-sum-unit').textContent = '—'; setNote(e.message, true);
        });
      }, 350);
    }

    $$('.zp-typepick__item', buy).forEach(function (b) { b.addEventListener('click', function () { loadType(b.getAttribute('data-type')); }); });
    $('#zp-country-search').addEventListener('input', function () { if (opts) renderCountries(this.value.trim().toLowerCase()); });
    $$('.zp-qty button', buy).forEach(function (b) {
      b.addEventListener('click', function () {
        var v = (parseInt(f.qty.value, 10) || 0) + parseInt(b.getAttribute('data-step'), 10);
        f.qty.value = Math.max(parseInt(f.qty.min, 10) || 1, Math.min(1000, v)); quote();
      });
    });
    f.qty.addEventListener('input', quote);
    f.goal.addEventListener('change', quote);
    f.operator.addEventListener('change', function () { renderRotation(); quote(); });
    f.rotation.addEventListener('change', quote);
    $$('input[name="auth_type"]', buy).forEach(function (r) { r.addEventListener('change', quote); });
    buy.addEventListener('submit', function (e) {
      var err = '';
      if (!f.country.value) err = 'Choose a location.';
      else if (!f.days.value) err = 'Choose a rental period.';
      else if (f.goal.value === 'other' && f.goal_text.value.trim().length < 5) err = 'Describe your use case in at least 5 characters.';
      else if (f.auth_type.value === 'ip' && !/^(\d{1,3}\.){3}\d{1,3}$/.test(f.auth_ip.value.trim())) err = 'Enter the IPv4 address to whitelist.';
      if (err) { e.preventDefault(); setNote(err, true); toast(err); }
    });
    loadType(buy.getAttribute('data-type'));
  })();

  /* ---------- Proxies ---------- */
  var bulk = $('#zp-bulk');
  if (bulk) (function () {
    var checks = $$('.zp-proxy__check');
    var extendBtn = $('#zp-extend-open');
    var exportLink = $('#zp-export');

    function selected() { return checks.filter(function (c) { return c.checked; }).map(function (c) { return c.closest('.zp-proxy'); }); }
    function line(card) {
      var d = card.dataset, proto = $('#zp-export-proto').value, fmt = $('#zp-export-format').value;
      var port = proto === 'socks5' ? (d.socks || d.http) : (d.http || d.socks);
      var host = d.ip.indexOf(':') > -1 ? '[' + d.ip + ']' : d.ip;
      var auth = d.login ? d.login + ':' + d.pass : '';
      if (fmt === 'user:pass@ip:port') return (auth ? auth + '@' : '') + host + ':' + port;
      if (fmt === 'url') return (proto === 'socks5' ? 'socks5' : 'http') + '://' + (auth ? encodeURIComponent(d.login) + ':' + encodeURIComponent(d.pass) + '@' : '') + host + ':' + port;
      if (fmt === 'ip:port') return host + ':' + port;
      return host + ':' + port + (auth ? ':' + auth : '');
    }
    function refresh() {
      var sel = selected();
      $('#zp-sel-count').textContent = sel.length;
      $$('.zp-proxy').forEach(function (c) { c.classList.toggle('is-selected', !!(c.querySelector('.zp-proxy__check') || {}).checked); });
      var types = {}; sel.forEach(function (c) { types[c.dataset.type] = 1; });
      extendBtn.disabled = !sel.length || Object.keys(types).length !== 1;
      var url = exportLink.getAttribute('data-base') + '&format=' + encodeURIComponent($('#zp-export-format').value) + '&protocol=' + $('#zp-export-proto').value;
      if (sel.length) url += '&ids=' + sel.map(function (c) { return c.dataset.id; }).join(',');
      exportLink.href = url;
    }
    checks.forEach(function (c) { c.addEventListener('change', refresh); });
    $('#zp-check-all').addEventListener('change', function () { var on = this.checked; checks.forEach(function (c) { if (!c.disabled) c.checked = on; }); refresh(); });
    $('#zp-export-format').addEventListener('change', refresh);
    $('#zp-export-proto').addEventListener('change', refresh);
    $('#zp-copy-sel').addEventListener('click', function () {
      var sel = selected(); if (!sel.length) sel = $$('.zp-proxy');
      copy(sel.map(line).join('\n'), sel.length + (sel.length === 1 ? ' proxy copied' : ' proxies copied'));
    });

    document.addEventListener('click', function (e) {
      var card = e.target.closest('.zp-proxy'); if (!card) return;
      if (e.target.closest('[data-zp-copy-proxy]')) copy(line(card), 'Proxy copied');
      if (e.target.closest('[data-zp-toggle-secret]')) {
        var s = card.querySelector('.zp-secret');
        s.textContent = s.textContent.indexOf('•') > -1 ? s.getAttribute('data-secret') : '••••••••';
      }
      if (e.target.closest('[data-zp-extend-one]')) openExtend([card]);
    });
    $$('.zp-note input').forEach(function (i) { i.addEventListener('change', function () { i.form.submit(); }); });
    extendBtn.addEventListener('click', function () { openExtend(selected()); });

    /* Extend modal */
    var modal = $('#zp-extend'), form = modal.querySelector('form'), seq = 0, current = [];
    function openExtend(cards) {
      if (!cards.length) return;
      current = cards;
      var type = cards[0].dataset.type;
      $('#zp-extend-ids').innerHTML = cards.map(function (c) { return '<input type="hidden" name="proxy_ids[]" value="' + parseInt(c.dataset.id, 10) + '">'; }).join('');
      $('#zp-extend-count').textContent = cards.length;
      $('#zp-extend-hint').textContent = type === 'ipv6' ? 'IPv6 is renewed per batch: every proxy from the same order is extended together.' : 'Choose how long to extend for. Time is added to the current expiry.';
      $('#zp-extend-total').textContent = '—'; $('#zp-extend-note').textContent = '';
      $('#zp-extend-btn').disabled = true; form.elements.days.value = '';
      var box = $('#zp-extend-days'); box.innerHTML = '<span class="zp-spinner"></span>';
      modal.hidden = false;
      api('extend_days', { type: type }).then(function (d) {
        box.innerHTML = '';
        d.days.forEach(function (n) {
          var b = document.createElement('button'); b.type = 'button'; b.textContent = n + (n === 1 ? ' day' : ' days');
          b.addEventListener('click', function () {
            $$('button', box).forEach(function (x) { x.classList.remove('is-active'); }); b.classList.add('is-active');
            form.elements.days.value = n; extendQuote();
          });
          box.appendChild(b);
        });
      }).catch(function (e) { box.textContent = e.message; });
    }
    function extendQuote() {
      var s = ++seq, note = $('#zp-extend-note'), btn = $('#zp-extend-btn');
      btn.disabled = true; note.classList.remove('is-error'); note.textContent = 'Calculating price…';
      api('extend_quote', {}, new FormData(form)).then(function (q) {
        if (s !== seq) return;
        $('#zp-extend-total').textContent = money(q.total);
        $('#zp-extend-count').textContent = q.count;
        if (q.balance < q.total) { note.textContent = 'You need ' + money(q.total - q.balance) + ' more in your wallet.'; note.classList.add('is-error'); btn.textContent = 'Add funds to continue'; }
        else { note.textContent = 'Charged from your wallet.'; btn.textContent = 'Pay from wallet'; }
        btn.disabled = false;
      }).catch(function (e) { if (s !== seq) return; note.textContent = e.message; note.classList.add('is-error'); });
    }
    refresh();
  })();

  /* ---------- Wallet ---------- */
  var dep = $('#zp-deposit');
  if (dep) {
    var amount = dep.elements.amount, manual = $('#zp-manual');
    var sync = function () {
      var gw = (dep.querySelector('input[name="gateway"]:checked') || {}).value;
      if (manual) manual.hidden = gw !== 'manual';
      var bankAmt = $('#zp-bank-amount'); if (bankAmt) bankAmt.textContent = money(parseFloat(amount.value || 0));
      var noteInput = dep.elements.note; if (noteInput) noteInput.required = gw === 'manual';
      var dbtn = $('#zp-deposit-btn span'); if (dbtn) dbtn.textContent = dbtn.parentNode.getAttribute(gw === 'manual' ? 'data-manual' : 'data-default');
      $$('[data-amount]', dep).forEach(function (b) { b.classList.toggle('is-active', parseFloat(b.dataset.amount) === parseFloat(amount.value)); });
    };
    $$('[data-amount]', dep).forEach(function (b) { b.addEventListener('click', function () { amount.value = b.dataset.amount; sync(); }); });
    $$('input[name="gateway"]', dep).forEach(function (r) { r.addEventListener('change', sync); });
    amount.addEventListener('input', sync);
    var copyAmt = $('#zp-copy-amount'); if (copyAmt) copyAmt.addEventListener('click', function () { copy(String(parseFloat(amount.value || 0)), 'Amount copied'); });
    sync();
  }

  /* ---------- Reveal on scroll (public pages) ---------- */
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('is-in'); io.unobserve(en.target); } });
    }, { threshold: 0.12 });
    $$('.zp-reveal').forEach(function (el) { io.observe(el); });
  } else { $$('.zp-reveal').forEach(function (el) { el.classList.add('is-in'); }); }
  var pnav = $('#zp-pnav-toggle');
  if (pnav) pnav.addEventListener('click', function () { document.body.classList.toggle('zp-pnav-open'); });

  /* ---------- PWA: service worker + install ---------- */
  if ('serviceWorker' in navigator && ZP.sw && window.isSecureContext) {
    window.addEventListener('load', function () { navigator.serviceWorker.register(ZP.sw, { scope: ZP.scope || '/' }).catch(function () {}); });
  }
  var deferred = null;
  var standalone = matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
  var isIOS = /iphone|ipad|ipod/i.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
  var installBtns = $$('#zp-install,[data-zp-install]');
  var headerBtn = $('#zp-install');
  if (standalone) { installBtns.forEach(function (b) { var row = b.closest('.zp-prefs'); (row || b).hidden = true; }); }
  else if (isIOS && headerBtn) { headerBtn.hidden = false; }
  window.addEventListener('beforeinstallprompt', function (e) { e.preventDefault(); deferred = e; if (headerBtn) headerBtn.hidden = false; });
  window.addEventListener('appinstalled', function () { deferred = null; if (headerBtn) headerBtn.hidden = true; toast('App installed'); });
  installBtns.forEach(function (b) {
    b.addEventListener('click', function () {
      if (deferred) { deferred.prompt(); deferred.userChoice.then(function () { deferred = null; }); }
      else if (isIOS && $('#zp-ios-install')) { openSheet('zp-ios-install'); }
      else { toast('Open your browser menu and choose “Install app” or “Add to Home screen”.'); }
    });
  });
})();
