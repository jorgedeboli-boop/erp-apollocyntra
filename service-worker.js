/**
 * Service Worker para ERP Apollo Cyntra
 * Precache selectivo de recursos estáticos del login/app shell.
 */

const CACHE_NAME = 'erp-apollo-cyntra-v1';

const PRECACHE_URLS = [
  // iOS App Icons
  '/assets/img/icons/app/ios/180.png',
  '/assets/img/icons/app/ios/167.png',
  '/assets/img/icons/app/ios/152.png',
  '/assets/img/icons/app/ios/120.png',

  // Android Icons
  '/assets/img/icons/app/android/android-launchericon-192-192.png',
  '/assets/img/icons/app/android/android-launchericon-512-512.png',

  // Fuentes Inter
  '/fonts/inter-v19-latin-300.woff2',
  '/fonts/inter-v19-latin-300italic.woff2',
  '/fonts/inter-v19-latin-500.woff2',
  '/fonts/inter-v19-latin-500italic.woff2',
  '/fonts/inter-v19-latin-600.woff2',
  '/fonts/inter-v19-latin-600italic.woff2',
  '/fonts/inter-v19-latin-700.woff2',
  '/fonts/inter-v19-latin-700italic.woff2',
  '/fonts/inter-v19-latin-italic.woff2',
  '/fonts/inter-v19-latin-regular.woff2',

  // Estilos
  '/assets/css/fonts.css',
  '/assets/vendor/fonts/iconify-icons.css',
  '/assets/vendor/libs/node-waves/node-waves.css',
  '/assets/vendor/css/core.css',
  '/assets/css/demo.css',
  '/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css',
  '/assets/vendor/libs/spinkit/spinkit.css',
  '/assets/vendor/libs/@form-validation/form-validation.css',

  // Scripts iniciales
  '/assets/vendor/js/helpers.js',
  '/parts/universal/theme-persistence.js',
  '/assets/js/config.js',

  // Dependencias JS
  '/assets/vendor/libs/jquery/jquery.js',
  '/assets/vendor/libs/popper/popper.js',
  '/assets/vendor/js/bootstrap.js',
  '/assets/vendor/libs/node-waves/node-waves.js',
  '/assets/vendor/libs/@algolia/autocomplete-js.js',
  '/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js',
  '/assets/vendor/libs/hammer/hammer.js',
  '/assets/vendor/libs/i18n/i18n.js',
  '/assets/vendor/js/menu.js',
  '/assets/vendor/libs/moment/moment.js',
  '/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
  '/assets/vendor/libs/select2/select2.js',
  '/assets/vendor/libs/flatpickr/flatpickr.js',
  '/parts/universal/select2-espanol.js',
  '/assets/vendor/libs/@form-validation/popular.js',
  '/assets/vendor/libs/@form-validation/bootstrap5.js',
  '/assets/vendor/libs/@form-validation/auto-focus.js',
  '/assets/vendor/libs/apex-charts/apexcharts.js',
  '/assets/vendor/libs/cleave-zen/cleave-zen.js',
  '/assets/vendor/libs/sweetalert2/sweetalert2.js',
  '/assets/vendor/libs/tagify/tagify.js',
  '/assets/vendor/libs/bootstrap-select/bootstrap-select.js',
  '/assets/vendor/libs/typeahead-js/typeahead.js',
  '/assets/vendor/libs/bloodhound/bloodhound.js',
  '/assets/js/main.js',
];

const PRECACHE_PATHS = new Set(PRECACHE_URLS);

function getRequestPath(url) {
  return new URL(url, self.location.origin).pathname;
}

function isPrecachePath(pathname) {
  return PRECACHE_PATHS.has(pathname);
}

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return Promise.allSettled(
        PRECACHE_URLS.map((url) =>
          cache.add(url).catch((error) => {
            console.warn('[Service Worker] No se pudo cachear:', url, error);
          })
        )
      );
    }).then(() => {
      console.log('[Service Worker] Precache completado');
      return self.skipWaiting();
    })
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames
          .filter((cacheName) => cacheName !== CACHE_NAME)
          .map((cacheName) => {
            console.log('[Service Worker] Limpiando caché antigua:', cacheName);
            return caches.delete(cacheName);
          })
      );
    }).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') {
    return;
  }

  const pathname = getRequestPath(event.request.url);
  if (!isPrecachePath(pathname)) {
    return;
  }

  event.respondWith(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.match(pathname).then((cachedResponse) => {
        if (cachedResponse) {
          return cachedResponse;
        }

        return fetch(event.request).then((networkResponse) => {
          if (networkResponse && networkResponse.status === 200) {
            cache.put(pathname, networkResponse.clone());
          }
          return networkResponse;
        });
      });
    })
  );
});

self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});
