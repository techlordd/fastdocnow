// Service Worker for Chat Application
const CACHE_NAME = 'DocNow-chat-v1';
const urlsToCache = [
    '/',
    '/css/app.css',
    '/js/app.js',
    '/images/default-avatar.png'
];

// Install event
self.addEventListener('install', function(event) {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(function(cache) {
                return cache.addAll(urlsToCache);
            })
    );
});

// Fetch event
self.addEventListener('fetch', function(event) {
    event.respondWith(
        caches.match(event.request)
            .then(function(response) {
                // Return cached version or fetch from network
                return response || fetch(event.request);
            }
        )
    );
});

// Push notification event
self.addEventListener('push', function(event) {
    if (event.data) {
        const data = event.data.json();
        const options = {
            body: data.body,
            icon: data.icon || '/images/app-icon.png',
            badge: '/images/badge-icon.png',
            tag: data.tag || 'chat-notification',
            renotify: true,
            requireInteraction: data.requireInteraction || false,
            data: data.data || {}
        };

        event.waitUntil(
            self.registration.showNotification(data.title, options)
        );
    }
});

// Notification click event
self.addEventListener('notificationclick', function(event) {
    event.notification.close();

    // Handle notification click
    event.waitUntil(
        clients.matchAll().then(function(clientList) {
            if (clientList.length > 0) {
                // Focus existing tab
                return clientList[0].focus();
            } else {
                // Open new tab
                return clients.openWindow('/chat');
            }
        })
    );
});

// Background sync for offline messages
self.addEventListener('sync', function(event) {
    if (event.tag === 'background-sync') {
        event.waitUntil(doBackgroundSync());
    }
});

function doBackgroundSync() {
    // Implement background sync logic for offline messages
    return Promise.resolve();
}
