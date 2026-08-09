/**
 * Service worker mínimo para PWA instalable (Chrome, Edge, etc.).
 * Las peticiones van a red; no cachea HTML/PHP con tokens para evitar datos obsoletos.
 */
/* global self, clients */

self.addEventListener('install', function (event) {
  self.skipWaiting();
});

self.addEventListener('activate', function (event) {
  event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', function (event) {
  event.respondWith(fetch(event.request));
});
