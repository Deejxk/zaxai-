(function () {
  'use strict';
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var form = $('[data-wizard]');
  if (!form) return;
  var panes = $$('.wiz-pane', form);
  var steps = $$('[data-step-btn]');
  var idx = Number(form.getAttribute('data-start') || 0);
  var nextBtn = $('[data-next]'); var backBtn = $('[data-back]');
  var progress = $('[data-progress]'); var stepNo = $('[data-step-no]');

  function show(i) {
    idx = Math.max(0, Math.min(panes.length - 1, i));
    panes.forEach(function (p, j) { p.classList.toggle('on', j === idx); });
    steps.forEach(function (s, j) { s.classList.toggle('on', j === idx); s.classList.toggle('done', j < idx); });
    backBtn.style.visibility = idx === 0 ? 'hidden' : 'visible';
    nextBtn.textContent = idx === panes.length - 1 ? 'Launch my hotspot' : 'Continue';
    if (progress) progress.style.width = ((idx + 1) / panes.length) * 100 + '%';
    if (stepNo) stepNo.textContent = idx + 1;
    var first = $('input:not([type=hidden]):not([type=file]), select', panes[idx]);
    if (first && window.innerWidth > 820) first.focus({ preventScroll: true });
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
  function valid(i) {
    var ok = true;
    $$('input, select', panes[i]).forEach(function (el) { if (ok && !el.checkValidity()) { el.reportValidity(); ok = false; } });
    return ok;
  }
  nextBtn.addEventListener('click', function () {
    if (!valid(idx)) return;
    if (idx === panes.length - 1) { nextBtn.disabled = true; nextBtn.textContent = 'Creating your hotspot…'; form.submit(); }
    else show(idx + 1);
  });
  backBtn.addEventListener('click', function () { show(idx - 1); });
  steps.forEach(function (s, j) {
    s.addEventListener('click', function () { if (j <= idx || valid(idx)) show(j); });
  });
  form.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && e.target.tagName === 'INPUT') { e.preventDefault(); nextBtn.click(); }
  });

  // Live preview
  var nameInput = $('[name=business_name]'); var slugOut = $('[data-slug]');
  var pv = $('[data-preview]');
  function slugify(v) { return v.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 40) || 'your-hotspot'; }
  function paintName() {
    var v = nameInput.value.trim() || 'Your Hotspot';
    $$('[data-pv-name]').forEach(function (el) { el.textContent = v; });
    $$('[data-pv-initial]').forEach(function (el) { if (!el.querySelector('img')) el.textContent = v.charAt(0).toUpperCase(); });
    if (slugOut) slugOut.textContent = slugify(nameInput.value);
  }
  nameInput.addEventListener('input', paintName);
  document.addEventListener('zx:brand', function (e) {
    if (e.detail.color && pv) pv.style.setProperty('--d-accent', e.detail.color);
    if (e.detail.color) $$('[data-pv-btn]').forEach(function (el) { el.style.background = e.detail.color; });
    if (e.detail.color) $$('[data-pv-initial]').forEach(function (el) { el.style.background = e.detail.color; });
    if (e.detail.logo) $$('[data-pv-initial]').forEach(function (el) {
      var img = document.createElement('img'); img.src = e.detail.logo; img.alt = '';
      el.textContent = ''; el.appendChild(img);
    });
  });
  paintName();
  show(idx);
})();
