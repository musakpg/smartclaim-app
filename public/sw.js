// Comment: Service Worker Push Event Handler for Android & Desktop
self.addEventListener('push', function (event) {
    let title = 'SmartClaim Notification';
    let options = {
        body: 'You have a new update.',
        icon: '/favicon.ico',
        badge: '/favicon.ico',
        vibrate: [200, 100, 200],
        requireInteraction: true,
        data: {
            url: '/dashboard'
        }
    };

    if (event.data) {
        try {
            const data = event.data.json();
            title = data.title || title;
            options.body = data.body || options.body;
            if (data.icon) options.icon = data.icon;
            if (data.data && data.data.url) options.data.url = data.data.url;
        } catch (e) {
            // Fallback if data is raw text
            options.body = event.data.text();
        }
    }

    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();
    const targetUrl = (event.notification.data && event.notification.data.url) ? event.notification.data.url : '/dashboard';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(clientList => {
            for (let client of clientList) {
                if (client.url.includes(targetUrl) && 'focus' in client) {
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});