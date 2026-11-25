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
    if (e.target.closest('#zp-theme,[data-zp-theme-toggle]')) { toggleTheme(); }
    if (e.target.closest('#zp-install,[data-zp-install]')) { installApp(); }
    var rf = e.target.closest('#zp-refresh');
    if (rf && !rf.classList.contains('is-spinning')) {
      rf.classList.add('is-spinning');
      var started = Date.now();
      var done = function (ok) { setTimeout(function () { rf.classList.remove('is-spinning'); if (ok) toast('Updated'); }, Math.max(0, 600 - (Date.now() - started))); };
      if (window.zpRefresh) { window.zpRefresh().then(function () { done(true); }, function () { location.reload(); }); }
      else { location.reload(); }
    }
    if (e.target.closest('[data-zp-top]')) { e.preventDefault(); window.scrollTo({ top: 0, behavior: 'smooth' }); }
    var cp = e.target.closest('[data-zp-copy]');
    if (cp) { copy(cp.getAttribute('data-zp-copy'), cp.getAttribute('data-zp-copied')); }
    var reveal = e.target.closest('[data-zp-reveal]');
    if (reveal) { var inp = reveal.parentNode.querySelector('input'); inp.type = inp.type === 'password' ? 'text' : 'password'; }
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') { $$('.zp-sheet').forEach(function (s) { s.hidden = true; }); $$('.zp-menu__panel').forEach(function (p) { p.hidden = true; }); }
  });
  function openSheet(id) { var s = document.getElementById(id); if (s) s.hidden = false; }

  /* Swipe a bottom sheet down with a finger to close it, like a native app. */
  (function () {
    var drag = null;
    document.addEventListener('touchstart', function (e) {
      var panel = e.target.closest && e.target.closest('.zp-sheet__panel');
      if (!panel || e.touches.length !== 1 || window.innerWidth > 700) { drag = null; return; }
      // Inside a scrolled list the finger scrolls the content first.
      if (panel.scrollTop > 0 && !e.target.closest('.zp-sheet__grip')) { drag = null; return; }
      drag = { panel: panel, sheet: panel.closest('.zp-sheet'), y: e.touches[0].clientY, t: Date.now(), dy: 0, on: false };
    }, { passive: true });
    document.addEventListener('touchmove', function (e) {
      if (!drag) return;
      var dy = e.touches[0].clientY - drag.y;
      if (!drag.on) {
        if (dy < -6) { drag = null; return; }
        if (dy < 8) return;
        drag.on = true; drag.panel.style.transition = 'none'; drag.panel.style.animation = 'none';
      }
      drag.dy = Math.max(0, dy);
      if (e.cancelable) e.preventDefault();
      drag.panel.style.transform = 'translateY(' + drag.dy + 'px)';
      var bd = drag.sheet.querySelector('.zp-sheet__backdrop');
      if (bd) bd.style.opacity = String(Math.max(0.15, 1 - drag.dy / (drag.panel.offsetHeight || 300)));
    }, { passive: false });
    function end() {
      if (!drag) return;
      var d = drag; drag = null;
      if (!d.on) return;
      var bd = d.sheet.querySelector('.zp-sheet__backdrop');
      var fast = d.dy / Math.max(1, Date.now() - d.t) > 0.5 && d.dy > 30;
      var close = fast || d.dy > Math.min(140, d.panel.offsetHeight * 0.3);
      d.panel.style.transition = 'transform .22s cubic-bezier(.2,.8,.2,1)';
      if (bd) bd.style.transition = 'opacity .22s ease';
      d.panel.style.transform = close ? 'translateY(105%)' : 'translateY(0)';
      if (bd) bd.style.opacity = close ? '0' : '1';
      setTimeout(function () {
        if (close) d.sheet.hidden = true;
        d.panel.style.transition = ''; d.panel.style.transform = ''; d.panel.style.animation = close ? '' : 'none';
        if (bd) { bd.style.transition = ''; bd.style.opacity = ''; }
        if (close) d.panel.style.animation = '';
      }, 230);
    }
    document.addEventListener('touchend', end); document.addEventListener('touchcancel', end);
  })();

  /* ---------- AJAX helper ---------- */
  function api(act, params, body) {
    var url = ZP.ajax + (ZP.ajax.indexOf('?') > -1 ? '&' : '?') + 'act=' + act + '&nonce=' + encodeURIComponent(ZP.nonce);
    Object.keys(params || {}).forEach(function (k) { url += '&' + k + '=' + encodeURIComponent(params[k]); });
    var opt = { credentials: 'same-origin', headers: { 'Accept': 'application/json' } };
    if (body) { opt.method = 'POST'; opt.body = body; }
    // Location and period lists rarely change: keep them for a few minutes so the buy page opens instantly.
    var ck = (act === 'options' || act === 'extend_days') ? 'zpo:' + (ZP.uid || 0) + ':' + act + ':' + JSON.stringify(params || {}) : null;
    if (ck) { try { var hit = JSON.parse(sessionStorage.getItem(ck) || 'null'); if (hit && Date.now() - hit.t < 300000) return Promise.resolve(hit.d); } catch (e) {} }
    return fetch(url, opt).then(function (r) {
      // The app can stay open for days on a phone: when the security token has aged out, get a fresh one and try once more.
      if (r.status === 403 && !api.retrying && ZP.notify) {
        api.retrying = true;
        return fetch(ZP.notify.url, { credentials: 'same-origin', headers: { Accept: 'application/json' } }).then(function (x) { return x.json(); }).then(function (d) {
          if (d && d.ajax) ZP.nonce = d.ajax;
          return api(act, params, body);
        }).then(function (v) { api.retrying = false; return { ok: true, data: v, again: true }; }, function (e) { api.retrying = false; throw e; });
      }
      return r.json();
    }).then(function (j) {
      if (j.again) return j.data;
      if (!j.ok) throw new Error(j.error || 'Something went wrong');
      if (ck && act === 'options' && !(j.data.countries || []).length) ck = null;
      if (ck) { try { sessionStorage.setItem(ck, JSON.stringify({ t: Date.now(), d: j.data })); } catch (e) {} }
      return j.data;
    });
  }
  var proxyClick = null;

  /* Everything that binds to elements inside the page content. Runs on load and after every in-app page switch. */
  function initPage(silent) {
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
    if (silent || !target || matchMedia('(prefers-reduced-motion: reduce)').matches) return;
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

  /* ---------- Reseller analytics: 7 / 30 day switch, tap a day for details ---------- */
  $$('[data-zp-ranges]').forEach(function (card) {
    $$('.zp-seg button', card).forEach(function (b) {
      b.addEventListener('click', function () {
        var r = b.getAttribute('data-range');
        $$('.zp-seg button', card).forEach(function (x) { x.classList.toggle('is-active', x === b); });
        $$('[data-range]', card).forEach(function (x) { if (x.tagName !== 'BUTTON' && !x.classList.contains('zp-chart')) x.hidden = x.getAttribute('data-range') !== r; });
      });
    });
  });
  $$('.zp-chart').forEach(function (ch) {
      var tip = $('.zp-chart__tip', ch), plot = $('.zp-chart__plot', ch);
      function show(col) {
        var p = (col.getAttribute('data-tip') || '').split('|');
        $$('.zp-chart__col', ch).forEach(function (x) { x.classList.toggle('is-on', x === col); });
        tip.innerHTML = ''; ['span', 'strong', 'small'].forEach(function (t, i) { var e = document.createElement(t); e.textContent = p[i] || ''; tip.appendChild(e); });
        tip.hidden = false;
        var pr = plot.getBoundingClientRect(), cr = col.getBoundingClientRect(), x = cr.left - pr.left + cr.width / 2, w = tip.offsetWidth;
        tip.style.left = Math.max(4, Math.min(pr.width - w - 4, x - w / 2)) + 'px';
      }
      $$('.zp-chart__col', ch).forEach(function (col) {
        col.addEventListener('mouseenter', function () { show(col); });
        col.addEventListener('focus', function () { show(col); });
        col.addEventListener('click', function () { show(col); });
      });
      plot.addEventListener('mouseleave', function () { tip.hidden = true; $$('.zp-chart__col', ch).forEach(function (x) { x.classList.remove('is-on'); }); });
    });
  $$('[data-zp-txfilter]').forEach(function (seg) {
    $$('button', seg).forEach(function (b) {
      b.addEventListener('click', function () {
        var dir = b.getAttribute('data-dir'), shown = 0;
        $$('button', seg).forEach(function (x) { x.classList.toggle('is-active', x === b); });
        $$('#zp-txlist > li[data-dir]').forEach(function (li) { var on = dir === 'all' || li.getAttribute('data-dir') === dir; li.hidden = !on; if (on) shown++; });
        var none = $('#zp-txnone'); if (!none) { none = document.createElement('p'); none.id = 'zp-txnone'; none.className = 'zp-muted'; none.textContent = 'Nothing of this kind on this page.'; $('#zp-txlist').after(none); }
        none.hidden = shown > 0;
      });
    });
  });
  $$('[data-zp-share]').forEach(function (b) {
    b.addEventListener('click', function () {
      var url = b.getAttribute('data-zp-share'), title = b.getAttribute('data-zp-share-title') || document.title;
      if (navigator.share) { navigator.share({ title: title, text: 'Buy private proxies from ' + title, url: url }).catch(function () {}); }
      else { copy(url, 'Store link copied'); }
    });
  });

  /* ---------- Setup guides: device tabs and the step-by-step walkthrough player ---------- */
  $$('[data-zp-setup]').forEach(function (box) {
    var players = [];
    $$('[data-zp-vid]', box).forEach(function (v) {
      var slides = $$('.zp-vid__slide', v), caps = $$('.zp-vid__caps p', v), bars = $$('.zp-vid__progress i', v), i = 0, timer = null, DUR = 5200;
      function show(n) {
        i = (n + slides.length) % slides.length;
        slides.forEach(function (s, k) { s.classList.toggle('is-on', k === i); });
        caps.forEach(function (c, k) { c.classList.toggle('is-on', k === i); });
        bars.forEach(function (b, k) { b.classList.toggle('is-done', k < i); b.classList.remove('is-now'); });
        void v.offsetWidth; if (v.classList.contains('is-playing')) bars[i].classList.add('is-now');
      }
      function stop() { clearInterval(timer); timer = null; v.classList.remove('is-playing'); bars.forEach(function (b) { b.classList.remove('is-now'); }); }
      function play() { stop(); v.classList.add('is-playing'); show(i); timer = setInterval(function () { if (!document.body.contains(v)) { stop(); return; } if (i === slides.length - 1) { stop(); bars[i].classList.add('is-done'); return; } show(i + 1); }, DUR); }
      v.style.setProperty('--dur', DUR + 'ms');
      v.addEventListener('click', function (e) {
        var b = e.target.closest('[data-vid]'); if (!b) return;
        var a = b.getAttribute('data-vid');
        if (a === 'play') { if (timer) stop(); else { if (i === slides.length - 1) i = 0; play(); } }
        else { stop(); show(i + (a === 'next' ? 1 : -1)); }
      });
      $$('.zp-vid__progress i', v).forEach(function (b, k) { b.addEventListener('click', function () { stop(); show(k); }); });
      players.push({ el: v, stop: stop, reset: function () { stop(); show(0); } });
    });
    $$('[data-setup]', box).forEach(function (tab) {
      tab.addEventListener('click', function () {
        var key = tab.getAttribute('data-setup');
        $$('[data-setup]', box).forEach(function (t) { t.classList.toggle('is-active', t === tab); });
        $$('[data-setup-pane]', box).forEach(function (p) { p.hidden = p.getAttribute('data-setup-pane') !== key; });
        players.forEach(function (p) { p.reset(); });
      });
    });
    // Open the guide for the device being used.
    var ua = navigator.userAgent, guess = /android/i.test(ua) ? 'android' : (/iphone|ipad|ipod/i.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1)) ? 'ios' : 'windows';
    var auto = box.querySelector('[data-setup="' + guess + '"]'); if (auto && !auto.classList.contains('is-active')) auto.click();
  });

  /* ---------- Fold-out sections ---------- */
  $$('[data-zp-openfold]').forEach(function (a) {
    a.addEventListener('click', function (e) {
      var f = document.getElementById(a.getAttribute('data-zp-openfold')); if (!f) return;
      e.preventDefault(); f.open = true;
      setTimeout(function () { var y = f.getBoundingClientRect().top + window.pageYOffset - 76; window.scrollTo({ top: y, behavior: 'smooth' }); }, 30);
    });
  });

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
      var tries = 0;
      (function fetchOptions() {
        api('options', { type: type }).then(function (data) {
          if (f.type.value !== type) return;
          opts = data; renderCountries($('#zp-country-search').value.trim().toLowerCase()); renderDays();
          setNote('Choose a location and period to see your price.');
        }).catch(function (e) {
          if (f.type.value !== type) return;
          if (++tries < 3) { setTimeout(fetchOptions, 1200 * tries); return; } // a slow connection gets two more quiet attempts
          var box = $('#zp-countries');
          box.innerHTML = '<div class="zp-loadfail"><strong>Locations could not be loaded</strong><span></span><button type="button" class="zp-btn zp-btn--soft zp-btn--sm">Try again</button></div>';
          box.querySelector('span').textContent = e.message || 'Check your connection and try again.';
          box.querySelector('button').addEventListener('click', function () { loadType(type); });
          setNote(e.message, true);
        });
      })();
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

    if (proxyClick) document.removeEventListener('click', proxyClick);
    proxyClick = function (e) {
      var card = e.target.closest('.zp-proxy'); if (!card) return;
      if (e.target.closest('[data-zp-copy-proxy]')) copy(line(card), 'Proxy copied');
      if (e.target.closest('[data-zp-toggle-secret]')) {
        var s = card.querySelector('.zp-secret');
        s.textContent = s.textContent.indexOf('•') > -1 ? s.getAttribute('data-secret') : '••••••••';
      }
      if (e.target.closest('[data-zp-extend-one]')) openExtend([card]);
    };
    document.addEventListener('click', proxyClick);
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

  /* ---------- Reseller sign-up: suggest a store link from the brand ---------- */
  var rsBrand = $('#zp-rs-brand'), rsSlug = $('#zp-rs-slug');
  if (rsBrand && rsSlug) {
    var rsTouched = false;
    rsSlug.addEventListener('input', function () { rsTouched = true; rsSlug.value = rsSlug.value.toLowerCase().replace(/[^a-z0-9-]/g, ''); });
    rsBrand.addEventListener('input', function () { if (!rsTouched) rsSlug.value = rsBrand.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 30); });
  }

  /* ---------- Reseller unlock: show the total when a domain is added ---------- */
  var rsDomain = $('#zp-rs-domain'), rsPay = $('#zp-rs-pay');
  if (rsDomain && rsPay) {
    var rsTotal = function () {
      rsDomain.value = rsDomain.value.toLowerCase().replace(/\.cloud$/, '').replace(/[^a-z0-9-]/g, '');
      var total = parseFloat(rsPay.dataset.fee) + (rsDomain.value ? parseFloat(rsPay.dataset.domain) : 0);
      rsPay.querySelector('span').textContent = 'Pay ' + money(total) + ' and unlock' + (rsDomain.value ? ' with ' + rsDomain.value + '.cloud' : '');
    };
    rsDomain.addEventListener('input', rsTotal);
  }

  /* ---------- Reseller store design: colour picker ---------- */
  var rsColor = $('#zp-rs-color'), rsColorText = $('#zp-rs-colortext');
  if (rsColor && rsColorText) {
    rsColor.addEventListener('input', function () { rsColorText.value = rsColor.value; });
    rsColorText.addEventListener('input', function () { if (/^#[0-9a-fA-F]{6}$/.test(rsColorText.value)) rsColor.value = rsColorText.value; });
    $$('.zp-swatches button').forEach(function (b) { b.addEventListener('click', function () { rsColor.value = rsColorText.value = b.getAttribute('data-color'); }); });
  }

  /* ---------- Reseller domain: say at once whether the name is free ---------- */
  $$('[data-zp-domain]').forEach(function (inp) {
    var hint = inp.closest('.zp-field').querySelector('.zp-domhint'), timer = null, seq = 0;
    if (!hint) return;
    inp.addEventListener('input', function () {
      clearTimeout(timer);
      var name = inp.value.toLowerCase().replace(/\.cloud$/, '').replace(/[^a-z0-9-]/g, '');
      if (inp.value !== name) inp.value = name;
      inp.setCustomValidity('');
      if (name.length < 3) { hint.hidden = true; return; }
      hint.hidden = false; hint.className = 'zp-domhint'; hint.textContent = 'Checking ' + name + '.cloud…';
      timer = setTimeout(function () {
        var mine = ++seq;
        api('domain_check', { domain: name }).then(function (d) {
          if (mine !== seq) return;
          hint.className = 'zp-domhint is-ok'; hint.textContent = '✓ ' + d.domain + ' is available';
        }).catch(function (e) {
          if (mine !== seq) return;
          hint.className = 'zp-domhint is-bad'; hint.textContent = '✕ ' + e.message;
          inp.setCustomValidity(e.message);
        });
      }, 450);
    });
  });

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
  }
  initPage(false);

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
  var headerBtn = $('#zp-install');
  function hideInstallRows() { if (standalone) $$('#zp-install,[data-zp-install]').forEach(function (x) { var row = x.closest('.zp-prefs'); (row || x).hidden = true; }); }
  hideInstallRows();
  if (!standalone && isIOS && headerBtn) { headerBtn.hidden = false; }
  window.addEventListener('beforeinstallprompt', function (e) { e.preventDefault(); deferred = e; if (headerBtn) headerBtn.hidden = false; });
  window.addEventListener('appinstalled', function () { deferred = null; if (headerBtn) headerBtn.hidden = true; toast('App installed'); });
  function installApp() {
    if (deferred) { deferred.prompt(); deferred.userChoice.then(function () { deferred = null; }); }
    else if (isIOS && $('#zp-ios-install')) { openSheet('zp-ios-install'); }
    else { toast('Open your browser menu and choose “Install app” or “Add to Home screen”.'); }
  }

  /* ---------- Instant in-app navigation ----------
     Panel links swap the page content from a local copy straight away (no reload, the header and
     footer menu never move), then the fresh version is fetched and swapped in quietly. */
  var main = $('#zp-content');
  if (main && ZP.base && window.fetch && window.history && history.pushState && window.DOMParser) (function () {
    var KEY = 'zpp:' + (ZP.uid || 0) + ':', mem = {}, dirty = false, current = location.href, token = 0;
    try { // never show another account's pages in the same tab
      if (sessionStorage.getItem('zp-uid') !== String(ZP.uid)) { Object.keys(sessionStorage).forEach(function (k) { if (k.indexOf('zpp:') === 0 || k.indexOf('zpo:') === 0) sessionStorage.removeItem(k); }); sessionStorage.setItem('zp-uid', String(ZP.uid)); }
    } catch (e) {}
    function norm(u) { var a = new URL(u, location.href); a.hash = ''; a.searchParams.delete('sent'); a.searchParams.delete('source'); return a.href; }
    function get(u) { u = norm(u); if (mem[u]) return mem[u]; try { var v = JSON.parse(sessionStorage.getItem(KEY + u) || 'null'); if (v) mem[u] = v; return v; } catch (e) { return null; } }
    function put(u, v) { u = norm(u); mem[u] = v; try { if (v.html.length < 300000) sessionStorage.setItem(KEY + u, JSON.stringify(v)); } catch (e) {} }
    function isPanel(a) {
      if (!a || a.target === '_blank' || a.hasAttribute('download') || a.origin !== location.origin) return false;
      if (a.href.indexOf(ZP.base) !== 0) return false;
      var rest = a.href.slice(ZP.base.length).split(/[?#]/)[0].replace(/^\/+|\/+$/g, '');
      var q = new URL(a.href).searchParams, page = q.get('zpp') !== null ? q.get('zpp') : rest;
      return (ZP.pages || []).indexOf(page || ZP.home) > -1;
    }
    function parse(text) {
      var doc = new DOMParser().parseFromString(text, 'text/html'), m = doc.getElementById('zp-content');
      if (!m) return null;
      Array.prototype.forEach.call(m.querySelectorAll(':scope > .zp-alert[role="status"]'), function (x) { x.remove(); });
      var chip = doc.querySelector('.zp-top__actions .zp-chip'), h = doc.querySelector('.zp-top__title');
      return { html: m.innerHTML, title: doc.title, heading: h ? h.textContent : '', chip: chip ? chip.innerHTML : '', nonce: (text.match(/"nonce":"([a-f0-9]+)"/) || [])[1] || '' };
    }
    function load(u) {
      return fetch(u, { credentials: 'same-origin', headers: { 'X-ZP-Nav': '1' } }).then(function (r) {
        if (r.redirected && norm(r.url) !== norm(u)) { var e = new Error('redirect'); e.to = r.url; throw e; }
        if (!r.ok) throw new Error('http');
        return r.text();
      }).then(function (t) { var v = parse(t); if (!v) throw new Error('shape'); put(u, v); return v; });
    }
    function pageOf(u) {
      var a = new URL(u, location.href), q = a.searchParams;
      return (q.get('zpp') !== null ? q.get('zpp') : a.href.slice(ZP.base.length).split(/[?#]/)[0].replace(/^\/+|\/+$/g, '')) || ZP.home;
    }
    function setActive(u) {
      var target = norm(u).split('?')[0], any = false;
      $$('.zp-nav__item, #zp-more .zp-sheet__grid a').forEach(function (a) { a.classList.toggle('is-active', a.href && norm(a.href).split('?')[0] === target); });
      $$('.zp-tabbar:not(.zp-tabbar--rs) a.zp-tab').forEach(function (a) { var on = norm(a.href).split('?')[0] === target; a.classList.toggle('is-active', on); any = any || on; });
      var more = $('.zp-tabbar:not(.zp-tabbar--rs) button.zp-tab'); if (more) more.classList.toggle('is-active', !any);
      // Inside the reseller dashboard the footer menu becomes the reseller menu.
      var rs = $('.zp-tabbar--rs');
      if (rs) {
        var inRs = pageOf(u) === 'reseller', tab = new URL(u, location.href).searchParams.get('tab') || 'overview', hit = false;
        document.body.classList.toggle('zp-in-rs', inRs);
        $$('[data-rs-tab]').forEach(function (a) { var on = inRs && a.getAttribute('data-rs-tab') === tab; a.classList.toggle('is-active', on); if (on && a.classList.contains('zp-tab')) hit = true; });
        var rmore = $('button.zp-tab', rs); if (rmore) rmore.classList.toggle('is-active', inRs && !hit);
      }
    }
    function syncRs() { // keep the reseller menu's counters in step with the page that was just loaded
      var rs = $('.zp-tabbar--rs'); if (!rs) return;
      $$('.zp-subtabs a').forEach(function (a) {
        var tab = new URL(a.href, location.href).searchParams.get('tab') || 'overview', b = a.querySelector('b'), n = b ? b.textContent : '';
        $$('[data-rs-tab="' + tab + '"] .zp-tab__badge, [data-rs-badge="' + tab + '"]', document).forEach(function (x) { x.textContent = n; x.hidden = !n; });
      });
    }
    function render(v, quiet) {
      main.classList.toggle('zp-quiet', !!quiet);
      main.innerHTML = v.html;
      document.title = v.title;
      var h = $('.zp-top__title'); if (h && v.heading) h.textContent = v.heading;
      var chip = $('.zp-top__actions .zp-chip'); if (chip && v.chip) chip.innerHTML = v.chip;
      if (v.nonce) ZP.nonce = v.nonce;
      dirty = false; hideInstallRows(); syncRs(); initPage(!!quiet);
    }
    function refresh(u, mine, shown) {
      load(u).then(function (v) {
        if (mine !== token || dirty) return;                 // user moved on, or is busy on this page
        if (!shown || shown.html !== v.html) render(v, !!shown);
        else { var chip = $('.zp-top__actions .zp-chip'); if (chip && v.chip) chip.innerHTML = v.chip; }
      }).catch(function (e) { if (mine !== token) return; if (e.to) location.href = e.to; else if (!shown) location.href = u; });
    }
    function go(u, push) {
      var mine = ++token, cached = get(u);
      $$('.zp-sheet').forEach(function (x) { x.hidden = true; }); $$('.zp-menu__panel').forEach(function (x) { x.hidden = true; });
      setActive(u);
      if (push) history.pushState({ zp: 1 }, '', u);
      current = u;
      main.classList.add('zp-fast');
      if (cached) { render(cached, false); }
      else { main.classList.remove('zp-quiet'); main.innerHTML = '<div class="zp-skel"><i></i><i></i><i></i></div>'; }
      window.scrollTo(0, 0);
      refresh(u, mine, cached);
    }
    document.addEventListener('click', function (e) {
      if (e.defaultPrevented || e.button || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
      var a = e.target.closest('a[href]');
      if (!isPanel(a)) return;
      if (a.hash && norm(a.href) === norm(location.href)) return; // an anchor on this page: let the browser scroll
      e.preventDefault();
      if (norm(a.href) === norm(location.href)) { window.scrollTo({ top: 0, behavior: 'smooth' }); refresh(location.href, ++token, get(location.href)); return; }
      go(a.href, true);
    });
    window.addEventListener('popstate', function () { go(location.href, false); });
    ['input', 'change'].forEach(function (ev) { main.addEventListener(ev, function () { dirty = true; }); });
    main.addEventListener('click', function (e) { if (e.target.closest('button, .zp-country, .zp-proxy__check')) dirty = true; });
    // remember the page we are on, then warm up the rest so the first tap on every tab is instant
    var here = parse(document.documentElement.outerHTML); if (here) put(location.href, here);
    var warm = function () {
      var seen = {}, list = [];
      $$('.zp-tabbar a.zp-tab, .zp-nav__item').forEach(function (a) { var u = norm(a.href); if (isPanel(a) && !seen[u] && u !== norm(location.href)) { seen[u] = 1; list.push(u); } });
      (function next() { var u = list.shift(); if (!u) return; load(u).catch(function () {}).then(function () { setTimeout(next, 150); }); })();
    };
    if ('requestIdleCallback' in window) requestIdleCallback(warm, { timeout: 2500 }); else setTimeout(warm, 1200);
    document.addEventListener('visibilitychange', function () { if (!document.hidden && !dirty) refresh(location.href, token, get(location.href)); });
    // The refresh icon: fetch this page again and swap it in, without a full reload.
    window.zpRefresh = function () {
      var mine = ++token;
      return load(location.href).then(function (v) { if (mine === token) render(v, true); });
    };
  })();

  /* ---------- Notifications: bell, sound, phone alerts ---------- */
  (function () {
    var N = ZP.notify, bell = document.getElementById('zp-bell');
    if (!N || !bell) return;
    var panel = document.getElementById('zp-bell-panel'), list = document.getElementById('zp-bell-list'), dot = document.getElementById('zp-bell-dot');
    var pushBox = document.getElementById('zp-bell-push'), pushText = document.getElementById('zp-bell-push-text'), foot = document.getElementById('zp-bell-foot');
    var lastKey = 'zp-nlast:' + ZP.uid, items = [], unread = 0, timer = null;
    var ICON = {
      sale: '<path d="M4 7h16l-1.5 11a2 2 0 0 1-2 1.7h-9a2 2 0 0 1-2-1.700z"/><path d="M8.500 7a3.500 3.500 0 0 1 7 0"/>',
      deposit: '<rect x="3" y="6" width="18" height="13" rx="2.500"/><path d="M3 10.500h18M7 15h3"/>',
      wallet: '<rect x="3" y="6" width="18" height="13" rx="2.500"/><path d="M16 12.500h2"/>',
      order: '<path d="m5 12.500 4.500 4.500L19 7.500"/>',
      ticket: '<path d="M4 5h16v11H9l-5 4z"/>',
      store: '<path d="M4 9.500 5.500 4h13L20 9.500"/><path d="M5 10v10h14V10"/><path d="M10 20v-5h4v5"/>',
      user: '<circle cx="12" cy="8" r="3.600"/><path d="M5 20a7 7 0 0 1 14 0"/>',
      alert: '<path d="M12 4 2.500 20h19z"/><path d="M12 10v4.500M12 17.300v.2"/>'
    };
    function esc(t) { var d = document.createElement('div'); d.textContent = t == null ? '' : String(t); return d.innerHTML; }
    function getLast() { try { return parseInt(localStorage.getItem(lastKey) || '0', 10) || 0; } catch (e) { return 0; } }
    function setLast(v) { try { localStorage.setItem(lastKey, String(v)); } catch (e) {} }

    function draw() {
      dot.hidden = unread < 1; dot.textContent = unread > 9 ? '9+' : String(unread);
      bell.classList.toggle('has-unread', unread > 0);
      try { if (navigator.setAppBadge) { if (unread > 0) navigator.setAppBadge(unread); else navigator.clearAppBadge(); } } catch (e) {}
      var clr = document.getElementById('zp-bell-clear'); if (clr) clr.hidden = !items.length;
      if (!items.length) { list.innerHTML = '<div class="zp-bell__empty"><svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="1.600" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9a6 6 0 0 1 12 0c0 6 2.500 7.500 2.500 7.500h-17S6 15 6 9"/><path d="M10 20a2.200 2.200 0 0 0 4 0"/></svg><strong>You are all caught up</strong><span>New activity will show up here.</span></div>'; return; }
      list.innerHTML = items.map(function (n) {
        var tag = n.url ? 'a' : 'div';
        return '<div class="zp-noterow" data-id="' + n.id + '"><' + tag + (n.url ? ' href="' + esc(n.url) + '"' : '') + ' class="zp-note zp-note--' + esc(n.type) + (n.read ? '' : ' is-new') + '">' +
          '<span class="zp-note__ico"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.800" stroke-linecap="round" stroke-linejoin="round">' + (ICON[n.type] || ICON.alert) + '</svg></span>' +
          '<span class="zp-note__main"><strong>' + esc(n.title) + '</strong>' + (n.body ? '<span>' + esc(n.body) + '</span>' : '') + '<em>' + esc(n.time) + '</em></span></' + tag + '>' +
          '<button type="button" class="zp-note__del" aria-label="Delete this notification" title="Delete"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.200" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg></button></div>';
      }).join('');
    }

    /* A short two-note chime, plus a buzz on phones. */
    var actx = null;
    function ring() {
      try {
        var AC = window.AudioContext || window.webkitAudioContext; if (!AC) return;
        actx = actx || new AC(); if (actx.state === 'suspended') actx.resume();
        [[880, 0], [1174.700, 0.16], [1568, 0.32]].forEach(function (x) {
          var o = actx.createOscillator(), g = actx.createGain(), t = actx.currentTime + x[1];
          o.type = 'sine'; o.frequency.value = x[0];
          g.gain.setValueAtTime(0.0001, t); g.gain.exponentialRampToValueAtTime(0.28, t + 0.02); g.gain.exponentialRampToValueAtTime(0.0001, t + 0.45);
          o.connect(g); g.connect(actx.destination); o.start(t); o.stop(t + 0.5);
        });
      } catch (e) {}
      try { if (navigator.vibrate) navigator.vibrate([180, 90, 180]); } catch (e) {}
    }

    function apply(d, quiet) {
      if (!d || !d.ok) return;
      items = d.items || []; unread = d.unread || 0;
      var top = items.length ? items[0].id : 0, last = getLast();
      if (top > last) {
        var fresh = items.filter(function (n) { return n.id > last && !n.read; });
        if (fresh.length && !quiet && last > 0) {
          ring(); toast(fresh[0].title);
          if (window.zpRefresh && panel.hidden) { try { window.zpRefresh().catch(function () {}); } catch (e) {} }
        }
        setLast(top);
      }
      if (d.ajax) ZP.nonce = d.ajax;
      if (d.nonce) N.nonce = d.nonce;
      if (!panel.hidden && unread) { unread = 0; post({ do: 'read' }); }
      draw();
    }
    function fetchNow(quiet) {
      return fetch(N.url, { credentials: 'same-origin', headers: { Accept: 'application/json' } }).then(function (r) { return r.ok ? r.json() : null; }).then(function (d) { apply(d, quiet); }).catch(function () {});
    }
    function post(data) {
      var fd = new FormData(); fd.append('_zpnonce', N.nonce); Object.keys(data).forEach(function (k) { fd.append(k, data[k]); });
      return fetch(N.url, { method: 'POST', credentials: 'same-origin', body: fd }).then(function (r) { return r.ok ? r.json() : null; }).catch(function () { return null; });
    }
    function schedule() { clearInterval(timer); timer = setInterval(function () { if (!document.hidden) fetchNow(false); }, 20000); }

    function open() {
      $$('.zp-menu__panel').forEach(function (p) { p.hidden = true; });
      panel.hidden = false; bell.setAttribute('aria-expanded', 'true');
      fetchNow(true).then(function () { if (unread) { post({ do: 'read' }); unread = 0; dot.hidden = true; bell.classList.remove('has-unread'); try { if (navigator.clearAppBadge) navigator.clearAppBadge(); } catch (e) {} } });
      pushState();
    }
    function close() { if (panel.hidden) return; panel.hidden = true; bell.setAttribute('aria-expanded', 'false'); items.forEach(function (n) { n.read = 1; }); draw(); }
    bell.addEventListener('click', function (e) { e.stopPropagation(); if (panel.hidden) open(); else close(); });
    document.addEventListener('click', function (e) {
      if (e.target.closest('#zp-bell-close')) { close(); return; }
      if (!panel.hidden && !e.target.closest('#zp-bell-panel') && !e.target.closest('#zp-bell') && document.body.contains(e.target)) close();
      if (e.target.closest('#zp-bell-panel a.zp-note')) close();
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });

    /* ----- Delete one / delete all ----- */
    list.addEventListener('click', function (e) {
      var del = e.target.closest('.zp-note__del'); if (!del) return;
      e.preventDefault(); e.stopPropagation();
      var row = del.closest('.zp-noterow'), id = parseInt(row.getAttribute('data-id'), 10);
      row.classList.add('is-gone');
      setTimeout(function () { items = items.filter(function (n) { return n.id !== id; }); unread = items.filter(function (n) { return !n.read; }).length; draw(); }, 200);
      post({ do: 'delete', id: id });
    });
    document.addEventListener('click', function (e) {
      var c = e.target.closest('#zp-bell-clear'); if (!c) return;
      e.stopPropagation();
      if (!c.classList.contains('is-sure')) { c.classList.add('is-sure'); c.textContent = 'Tap again to delete all'; setTimeout(function () { c.classList.remove('is-sure'); c.textContent = 'Delete all'; }, 3500); return; }
      c.classList.remove('is-sure'); c.textContent = 'Delete all';
      items = []; unread = 0; draw(); post({ do: 'clear' }).then(function () { toast('Notifications deleted'); });
    });

    /* ----- Phone alerts ----- */
    var canPush = 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window && window.isSecureContext && N.key;
    var isIOS = /iphone|ipad|ipod/i.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    var standalone = (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) || window.navigator.standalone === true;
    function keyBytes(b64) { var pad = '='.repeat((4 - b64.length % 4) % 4), raw = atob((b64 + pad).replace(/-/g, '+').replace(/_/g, '/')), out = new Uint8Array(raw.length); for (var i = 0; i < raw.length; i++) out[i] = raw.charCodeAt(i); return out; }
    function b64(buf) { var b = new Uint8Array(buf), s = ''; for (var i = 0; i < b.length; i++) s += String.fromCharCode(b[i]); return btoa(s).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, ''); }
    function reg() { return navigator.serviceWorker.ready; }
    function send(sub, hello) { return post({ do: 'subscribe', endpoint: sub.endpoint, p256dh: b64(sub.getKey('p256dh')), auth: b64(sub.getKey('auth')), hello: hello ? '1' : '' }); }
    function pushState() {
      var btn = document.getElementById('zp-bell-enable');
      if (!canPush) {
        foot.hidden = true;
        if (isIOS && !standalone) { pushBox.hidden = false; btn.hidden = true; pushText.textContent = 'On iPhone: tap the Share button, choose "Add to Home Screen", then open the app from your home screen and turn on alerts here.'; }
        else pushBox.hidden = true;
        return;
      }
      if (Notification.permission === 'denied') { pushBox.hidden = false; foot.hidden = true; btn.hidden = true; pushText.textContent = 'Alerts are blocked for this site. Allow notifications in your browser or phone settings, then come back here.'; return; }
      reg().then(function (r) { return r.pushManager.getSubscription(); }).then(function (sub) {
        var on = !!sub && Notification.permission === 'granted';
        pushBox.hidden = on; foot.hidden = !on; btn.hidden = false;
      }).catch(function () { pushBox.hidden = true; foot.hidden = true; });
    }
    function enable() {
      var btn = document.getElementById('zp-bell-enable'); btn.disabled = true;
      ring();
      Notification.requestPermission().then(function (perm) {
        if (perm !== 'granted') throw new Error('denied');
        return reg();
      }).then(function (r) {
        return r.pushManager.getSubscription().then(function (sub) { return sub || r.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: keyBytes(N.key) }); });
      }).then(function (sub) { return send(sub, true); }).then(function (d) {
        btn.disabled = false; if (!d || !d.ok) throw new Error('save');
        toast('Phone alerts are on'); apply(d, true); pushState();
      }).catch(function (err) { btn.disabled = false; toast(err && err.message === 'denied' ? 'Notifications were not allowed' : 'Could not turn on alerts on this device'); pushState(); });
    }
    function disable() {
      reg().then(function (r) { return r.pushManager.getSubscription(); }).then(function (sub) {
        if (!sub) return; var ep = sub.endpoint; return sub.unsubscribe().then(function () { return post({ do: 'unsubscribe', endpoint: ep }); });
      }).then(function () { toast('Phone alerts turned off'); pushState(); }).catch(function () { pushState(); });
    }
    document.addEventListener('click', function (e) {
      if (e.target.closest('#zp-bell-enable')) enable();
      if (e.target.closest('#zp-bell-disable')) disable();
    });
    // Keep this device registered (the browser can rotate its push address).
    if (canPush && Notification.permission === 'granted') {
      reg().then(function (r) { return r.pushManager.getSubscription(); }).then(function (sub) { if (sub) send(sub, false); }).catch(function () {});
    }
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.addEventListener('message', function (e) { if (e.data && e.data.zp === 'push') fetchNow(false); });
    }
    document.addEventListener('visibilitychange', function () { if (!document.hidden) fetchNow(false); });
    // First visit on this device: remember where we are without ringing for old items.
    fetchNow(getLast() === 0).then(function () { if (getLast() === 0) setLast(items.length ? items[0].id : 1); });
    schedule();
  })();

  /* ---------- Keyboard open on phones: keep the top header in view ---------- */
  (function () {
    var top = document.querySelector('.zp-shell .zp-top'), vv = window.visualViewport, root = document.documentElement;
    if (!top) return;
    var typing = false, raf = 0;
    function isField(el) { return el && (el.tagName === 'TEXTAREA' || el.tagName === 'SELECT' || (el.tagName === 'INPUT' && !/^(checkbox|radio|button|submit|file|range|color|hidden)$/i.test(el.type))); }
    function place() {
      raf = 0;
      // iOS slides the whole page up under the keyboard; move the fixed header back to the top of what is visible.
      var small = vv ? vv.height < window.innerHeight - 140 : false;
      // Only while the keyboard is really open. During normal scrolling the header is never moved:
      // iOS reports small offsets while the page bounces, and following them made the header jump.
      var off = (vv && typing && small) ? Math.max(0, Math.round(vv.offsetTop)) : 0;
      top.style.transform = off ? 'translate3d(0,' + off + 'px,0)' : '';
      root.classList.toggle('zp-kb', (typing || small) && window.innerWidth <= 900);
    }
    function queue() { if (!raf) raf = requestAnimationFrame(place); }
    if (vv) { vv.addEventListener('resize', queue); vv.addEventListener('scroll', function () { if (typing || top.style.transform) queue(); }); }
    window.addEventListener('scroll', function () { if (typing || top.style.transform) queue(); }, { passive: true });
    document.addEventListener('focusin', function (e) { if (isField(e.target) && e.target.tagName !== 'SELECT') { typing = true; queue(); setTimeout(queue, 350); } });
    document.addEventListener('focusout', function () { typing = false; setTimeout(queue, 120); setTimeout(queue, 500); });
  })();
})();
