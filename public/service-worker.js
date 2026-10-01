const CACHE_NAME = 'sas-piscicola-v1.0.0';
const STATIC_ASSETS = [
    '/',
    '/manifest.json',
    '/offline.html',
    '/icons/icon-192x192.png',
    '/icons/icon-512x512.png',
    '/icons/icon.svg'
];

// Instalar Service Worker y pre-cachear archivos esenciales
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(STATIC_ASSETS).catch((err) => {
                console.warn('[PWA ServiceWorker] Pre-cache warning:', err);
            });
        }).then(() => self.skipWaiting())
    );
});

// Activar y purgar cachés antiguas
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
            );
        }).then(() => self.clients.claim())
    );
});

// Manejo inteligente de peticiones
self.addEventListener('fetch', (event) => {
    const req = event.request;

    // Solo interceptar peticiones GET
    if (req.method !== 'GET') {
        return;
    }

    const url = new URL(req.url);

    // Evitar cachear llamadas API de mutación o autenticación directa si no es necesario
    if (url.pathname.startsWith('/api/') || url.pathname === '/logout') {
        return;
    }

    // 1. Navegación (Páginas HTML): Estrategia Network-First con fallback a offline.html
    if (req.mode === 'navigate') {
        event.respondWith(
            fetch(req)
                .then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        const responseClone = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(req, responseClone);
                        });
                    }
                    return networkResponse;
                })
                .catch(async () => {
                    const cachedResponse = await caches.match(req);
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    const offlinePage = await caches.match('/offline.html');
                    return offlinePage || new Response('Offline', { status: 503, statusText: 'Offline' });
                })
        );
        return;
    }

    // 2. Recursos estáticos (Imágenes, Fuentes, CSS, JS, Íconos): Stale-While-Revalidate
    event.respondWith(
        caches.match(req).then((cachedResponse) => {
            const fetchPromise = fetch(req).then((networkResponse) => {
                if (networkResponse && networkResponse.status === 200) {
                    const responseClone = networkResponse.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(req, responseClone);
                    });
                }
                return networkResponse;
            }).catch(() => {
                // Fallback silencioso si la red falla y no hay caché
            });

            return cachedResponse || fetchPromise;
        })
    );
});
