// Passes the page's question ("where should the GPS be?") to the add-on and the answer back.
window.addEventListener('message', function (e) {
  if (e.source !== window || !e.data || e.data.zaxgps !== 'ask') return;
  try {
    chrome.runtime.sendMessage({ t: 'gps' }, function (s) {
      if (chrome.runtime.lastError) s = null;
      window.postMessage({ zaxgps: 'state', state: s || { on: false } }, '*');
    });
  } catch (err) { window.postMessage({ zaxgps: 'state', state: { on: false } }, '*'); }
});
