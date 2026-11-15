(function () {
  'use strict';
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

  // Tabs
  $$('[data-tab]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var name = btn.getAttribute('data-tab');
      $$('[data-tab]').forEach(function (b) { b.classList.toggle('active', b === btn); });
      $$('[data-panel]').forEach(function (p) { p.classList.toggle('active', p.getAttribute('data-panel') === name); });
    });
  });

  // Format voucher input as XXXX-XXXX
  $$('.input-code').forEach(function (input) {
    input.addEventListener('input', function () {
      var v = input.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 8);
      input.value = v.length > 4 ? v.slice(0, 4) + '-' + v.slice(4) : v;
    });
  });

  // Voucher form: validate with the server, then log in through the router
  var vf = $('[data-voucher-form]');
  if (vf) {
    vf.addEventListener('submit', function (e) {
      e.preventDefault();
      var msg = $('[data-msg]', vf);
      var btn = $('button', vf);
      msg.className = 'form-msg';
      msg.textContent = 'Checking voucher…';
      btn.disabled = true;
      fetch('/api/voucher/check', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ code: vf.code.value }),
        credentials: 'same-origin'
      }).then(function (r) { return r.json(); }).then(function (data) {
        msg.textContent = data.message;
        msg.className = 'form-msg ' + (data.ok ? 'ok' : 'err');
        if (data.ok && data.loginUrl) window.location.href = data.loginUrl;
        else if (data.ok) setTimeout(function () { window.location.href = data.statusUrl; }, 1200);
        else btn.disabled = false;
      }).catch(function () { vf.submit(); });
    });
  }

  // Copy buttons
  $$('[data-copy]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var text = btn.getAttribute('data-copy');
      var done = function () { var t = btn.innerHTML; btn.textContent = 'Copied!'; setTimeout(function () { btn.innerHTML = t; }, 1600); };
      if (navigator.clipboard) navigator.clipboard.writeText(text).then(done, done);
      else { var ta = document.createElement('textarea'); ta.value = text; document.body.appendChild(ta); ta.select(); try { document.execCommand('copy'); } catch (err) {} ta.remove(); done(); }
    });
  });

  // Auto-connect countdown on success page
  var auto = $('[data-autoconnect]');
  var cd = $('[data-countdown] b');
  if (auto && cd) {
    var n = 5;
    var timer = setInterval(function () {
      n -= 1;
      cd.textContent = n;
      if (n <= 0) { clearInterval(timer); window.location.href = auto.href; }
    }, 1000);
  }

  // Poll pending payments
  var poll = $('[data-poll]');
  if (poll) {
    var ref = poll.getAttribute('data-poll');
    var tries = 0;
    var check = function () {
      tries += 1;
      fetch('/api/order/' + encodeURIComponent(ref)).then(function (r) { return r.json(); }).then(function (d) {
        if (d.status !== 'pending') window.location.href = '/pay/callback?reference=' + encodeURIComponent(ref);
        else if (tries % 4 === 0) window.location.href = '/pay/callback?reference=' + encodeURIComponent(ref);
        else setTimeout(check, 4000);
      }).catch(function () { setTimeout(check, 6000); });
    };
    setTimeout(check, 3000);
  }

  // Live countdown of remaining time
  $$('[data-remaining]').forEach(function (el) {
    var end = Number(el.getAttribute('data-remaining'));
    if (!end) return;
    var tick = function () {
      var s = Math.max(0, Math.floor((end - Date.now()) / 1000));
      var d = Math.floor(s / 86400), h = Math.floor((s % 86400) / 3600), m = Math.floor((s % 3600) / 60);
      el.textContent = d ? d + 'd ' + h + 'h ' + m + 'm' : h ? h + 'h ' + m + 'm ' + (s % 60) + 's' : m + 'm ' + (s % 60) + 's';
    };
    tick();
    setInterval(tick, 1000);
  });
})();
