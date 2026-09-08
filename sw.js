const CACHE = 'podocare-app-v2';
const SHELL = ['offline.html', 'assets/css/style.css', 'assets/js/app.js', 'assets/js/pwa.js'];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE).then((cache) => cache.addAll(SHELL)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(keys.filter((key) => key !== CACHE).map((key) => caches.delete(key)))).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);
    const isAsset = url.pathname.includes('/assets/') || url.pathname.includes('icon.php');

    if (isAsset) {
        event.respondWith(
            fetch(request).then((response) => {
                const copy = response.clone();
                caches.open(CACHE).then((cache) => cache.put(request, copy));
                return response;
            }).catch(() => caches.match(request))
        );
        return;
    }

    event.respondWith(fetch(request).catch(() => caches.match(request).then((cached) => cached || caches.match('offline.html'))));
});
