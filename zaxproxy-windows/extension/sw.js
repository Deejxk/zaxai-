// Asks ZaxProxy Connect (on this computer only) which place its map is set to.
// Nothing is sent anywhere else, and nothing about the pages you visit is sent at all.
try { importScripts('mode.js'); } catch (e) {}
const SRC = self.ZAX_SRC || 'own';
const PORTS = [47831, 47832, 47833, 47834, 47835];
let port = 0, cache = null, cachedAt = 0;

async function ask(p) {
  const ctl = new AbortController(), timer = setTimeout(() => ctl.abort(), 700);
  try {
    const r = await fetch('http://127.0.0.1:' + p + '/gps?src=' + SRC, { signal: ctl.signal, cache: 'no-store' });
    const d = await r.json();
    return d && d.app === 'zaxproxy' ? d : null;
  } catch (e) { return null; } finally { clearTimeout(timer); }
}

async function state() {
  if (cache && Date.now() - cachedAt < 2000) return cache;
  let d = port ? await ask(port) : null;
  if (!d) { port = 0; for (const p of PORTS) { d = await ask(p); if (d) { port = p; break; } } }
  cache = d ? { app: true, on: !!d.on, lat: +d.lat, lng: +d.lng, label: String(d.label || '') } : { app: false, on: false };
  cachedAt = Date.now();
  chrome.action.setBadgeText({ text: cache.on ? 'ON' : '' });
  chrome.action.setBadgeBackgroundColor({ color: '#10b981' });
  return cache;
}

chrome.runtime.onMessage.addListener((msg, sender, reply) => {
  if (msg && msg.t === 'gps') { state().then(reply); return true; }
});
// A regular check keeps the badge right and lets the app show that the add-on is installed.
chrome.alarms.create('zax', { periodInMinutes: 0.5 });
chrome.alarms.onAlarm.addListener(() => { cachedAt = 0; state(); });
chrome.runtime.onInstalled.addListener(() => state());
chrome.runtime.onStartup.addListener(() => state());
state(); // report in as soon as the add-on is running
