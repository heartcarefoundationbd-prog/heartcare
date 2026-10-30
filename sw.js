/* HeartCare service worker (only registered when "PWA" is switched on in Site settings).
   Strategy:
   - static assets (CSS, JS, fonts, images, icons): stale-while-revalidate
   - pages (HTML): network first; a small offline page is shown ONLY when the network fails
   - NEVER cached: /admin/, non-GET requests, uploaded documents / downloads, other origins,
     anything that sets cookies or returns a non-200 response.
   Offline support is limited to that fallback page plus assets already visited. */
const VERSION = 'hc-v1';
const STATIC = VERSION + '-static';
const SCOPE = self.registration.scope;           // e.g. https://example.org/heartcare/
const OFFLINE = new URL('offline.html', SCOPE).href;

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(STATIC).then((c) => c.add(OFFLINE)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((k) => !k.startsWith(VERSION)).map((k) => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

function neverCache(url) {
  const p = url.pathname;
  return /\/admin(\/|$)/.test(p)
      || /\/documents\/file\//.test(p)
      || /\/assets\/uploads\/docs\//.test(p)
      || /\.(pdf|docx?|xlsx?|csv)$/i.test(p);
}
function isStatic(url) {
  return /\/(assets|public)\/.+\.(css|js|woff2?|png|jpe?g|webp|svg|ico)$/i.test(url.pathname);
}

self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') return;
  const url = new URL(req.url);
  if (url.origin !== self.location.origin || !url.href.startsWith(SCOPE) || neverCache(url)) return;

  if (req.mode === 'navigate') {
    event.respondWith(fetch(req).catch(() => caches.match(OFFLINE)));
    return;
  }
  if (isStatic(url)) {
    event.respondWith(
      caches.open(STATIC).then((cache) =>
        cache.match(req).then((hit) => {
          const net = fetch(req).then((res) => {
            if (res.ok && res.type === 'basic') cache.put(req, res.clone());
            return res;
          }).catch(() => hit);
          return hit || net;
        })
      )
    );
  }
});
