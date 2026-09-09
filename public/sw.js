/* ==========================================================================
   Laravel DCMS Service Worker - Web Push Notification (public/sw.js)
   ========================================================================== */

self.addEventListener('install', (event) => {
  console.log('[DCMS Service Worker] Installed.');
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  console.log('[DCMS Service Worker] Activated.');
  event.waitUntil(clients.claim());
});

/**
 * Fetch handler.
 *
 * Aplikasi ini butuh data real-time (Livewire), jadi kita TIDAK melakukan
 * caching agresif. Handler ini sengaja "pass-through" (network only) —
 * kehadirannya diperlukan agar browser menganggap aplikasi installable (PWA).
 */
self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') {
    return;
  }

  event.respondWith(
    fetch(event.request).catch(() => {
      // Fallback minimal saat benar-benar offline: kembalikan halaman start.
      if (event.request.mode === 'navigate') {
        return caches.match('/admin');
      }
      return Response.error();
    })
  );
});

/**
 * 1. Push Event Listener
 */
self.addEventListener('push', (event) => {
  console.log('[DCMS Service Worker] Push Event received.');

  let payload = {
    title: 'Notifikasi Sistem DCMS',
    body: 'Anda menerima pesan notifikasi baru.',
    icon: '/images/logo.png',
    badge: '/images/logo.png',
    url: '/admin'
  };

  if (event.data) {
    try {
      payload = event.data.json();
    } catch (e) {
      payload.body = event.data.text();
    }
  }

  const title = payload.title || 'Notifikasi Sistem DCMS';
  const options = {
    body: payload.body || 'Pesan notifikasi baru.',
    icon: payload.icon || '/images/logo.png',
    badge: payload.badge || '/images/logo.png',
    vibrate: [100, 50, 100],
    data: {
      url: payload.url || '/admin',
      timestamp: payload.timestamp || Date.now()
    },
    actions: [
      { action: 'open', title: 'Buka Aplikasi' },
      { action: 'close', title: 'Tutup' }
    ]
  };

  event.waitUntil(
    self.registration.showNotification(title, options)
  );
});

/**
 * 2. Notification Click Event Listener
 */
self.addEventListener('notificationclick', (event) => {
  const notification = event.notification;
  const action = event.action;
  const targetUrl = (notification.data && notification.data.url) ? notification.data.url : '/admin';

  notification.close();

  if (action === 'close') {
    return;
  }

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
      for (let i = 0; i < windowClients.length; i++) {
        const client = windowClients[i];
        if (client.url === targetUrl && 'focus' in client) {
          return client.focus();
        }
      }
      if (clients.openWindow) {
        return clients.openWindow(targetUrl);
      }
    })
  );
});
