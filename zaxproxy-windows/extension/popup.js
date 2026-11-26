chrome.runtime.sendMessage({ t: 'gps' }, function (s) {
  var t = document.getElementById('t'), d = document.getElementById('d'), box = document.getElementById('s');
  if (!s || !s.app) { t.textContent = 'ZaxProxy Connect is not open'; d.textContent = 'Websites see your real location.'; return; }
  if (!s.on) { t.textContent = 'GPS is off'; d.textContent = 'Websites see your real location.'; return; }
  box.className = 's on'; t.textContent = 'GPS is on'; d.textContent = (s.label || '') + ' (' + s.lat.toFixed(4) + ', ' + s.lng.toFixed(4) + ')';
});
