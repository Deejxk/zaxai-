/* ZaxProxy service worker: makes the panel installable and gives a friendly offline screen.
   Account pages are never cached; only static assets are. */
const VERSION = 'zp-__ZP_VERSION__';
const OFFLINE = '__ZP_OFFLINE__';
const ASSETS = '__ZP_ASSETS__';

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(VERSION).then((cache) => cache.addAll([OFFLINE, ASSETS + 'css/app.css?v=__ZP_VERSION__', ASSETS + 'icons/icon-192.png'])).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(keys.filter((k) => k.startsWith('zp-') && k !== VERSION).map((k) => caches.delete(k)))).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') return;
  const url = new URL(req.url);

  if (req.mode === 'navigate') {
    event.respondWith(fetch(req).catch(() => caches.match(OFFLINE)));
    return;
  }
  if (url.origin === self.location.origin && url.href.startsWith(ASSETS)) {
    event.respondWith(
      caches.match(req).then((hit) => {
        const fresh = fetch(req).then((res) => {
          if (res.ok) { const copy = res.clone(); caches.open(VERSION).then((c) => c.put(req, copy)); }
          return res;
        }).catch(() => hit);
        return hit || fresh;
      })
    );
  }
});

/* Phone alerts: show the message, ring, and open the right page when tapped. */
self.addEventListener('push', (event) => {
  let data = {};
  try { data = event.data ? event.data.json() : {}; } catch (e) { data = {}; }
  const title = data.title || 'New notification';
  const url = new URL(data.url || '', self.registration.scope).href;
  event.waitUntil(Promise.all([
    self.registration.showNotification(title, {
      body: data.body || '',
      icon: ASSETS + 'icons/icon-192.png',
      badge: ASSETS + 'icons/badge-96.png',
      tag: 'zp-' + (data.id || Date.now()),
      renotify: true,
      vibrate: [220, 110, 220, 110, 320],
      timestamp: Date.now(),
      data: { url }
    }),
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((list) => list.forEach((c) => c.postMessage({ zp: 'push' })))
  ]));
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const url = (event.notification.data && event.notification.data.url) || self.registration.scope;
  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((list) => {
      for (const c of list) {
        if (c.url.startsWith(self.registration.scope) && 'focus' in c) { return c.focus().then((w) => ('navigate' in w ? w.navigate(url) : w)).catch(() => self.clients.openWindow(url)); }
      }
      return self.clients.openWindow(url);
    })
  );
});
