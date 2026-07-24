const CACHE = 'hfl-pwa-v1';
const PRECACHE = ['/connect-app', '/manifest.json', '/images/pwa-icon.svg'];

self.addEventListener('install', ev => {
    ev.waitUntil(
        caches.open(CACHE).then(c => c.addAll(PRECACHE).catch(() => {}))
    );
    self.skipWaiting();
});

self.addEventListener('activate', ev => {
    ev.waitUntil(
        caches.keys()
            .then(keys => Promise.all(
                keys.filter(k => k !== CACHE).map(k => caches.delete(k))
            ))
            .then(() => clients.claim())
    );
});

// Network-first; fall back to cache for the app shell only.
self.addEventListener('fetch', ev => {
    if (ev.request.method !== 'GET') return;
    ev.respondWith(
        fetch(ev.request)
            .then(resp => {
                if (resp.ok) {
                    const clone = resp.clone();
                    caches.open(CACHE).then(c => c.put(ev.request, clone));
                }
                return resp;
            })
            .catch(() => caches.match(ev.request))
    );
});
