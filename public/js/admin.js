(function () {
  'use strict';
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

  // Mobile sidebar
  var side = $('[data-side]');
  var menu = $('[data-menu]');
  if (side && menu) {
    menu.addEventListener('click', function (e) { e.stopPropagation(); side.classList.toggle('open'); });
    document.addEventListener('click', function (e) { if (!side.contains(e.target)) side.classList.remove('open'); });
  }

  // Chart tooltips
  var tip = $('[data-tooltip]');
  $$('[data-tip]').forEach(function (g) {
    var show = function (e) {
      var r = g.getBoundingClientRect();
      tip.textContent = g.getAttribute('data-tip');
      tip.hidden = false;
      tip.style.left = (r.left + r.width / 2) + 'px';
      tip.style.top = (e && e.clientY ? Math.min(e.clientY, r.bottom) : r.top + r.height / 2) + 'px';
    };
    g.addEventListener('mousemove', show);
    g.addEventListener('touchstart', function (e) { show(e.touches[0]); }, { passive: true });
    g.addEventListener('mouseleave', function () { tip.hidden = true; });
  });

  // Confirmations
  $$('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) { if (!window.confirm(f.getAttribute('data-confirm'))) e.preventDefault(); });
  });

  // Copy code blocks
  $$('[data-copy-target]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var el = $(btn.getAttribute('data-copy-target'));
      var text = el ? el.textContent : '';
      var label = btn.innerHTML;
      var done = function () { btn.textContent = 'Copied!'; setTimeout(function () { btn.innerHTML = label; }, 1600); };
      if (navigator.clipboard) navigator.clipboard.writeText(text).then(done, done);
    });
  });
})();
