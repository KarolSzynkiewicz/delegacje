/* ChronoLogic Web Push. Nie przechwytuje fetch — Livewire zostaje bez cache. */

self.addEventListener('push', (event) => {
    let payload = {};

    try {
        payload = event.data ? event.data.json() : {};
    } catch (error) {
        payload = { body: event.data ? event.data.text() : '' };
    }

    const title = payload.title || 'ChronoLogic';
    const options = {
        body: payload.body || '',
        icon: payload.icon || '/favicon.svg',
        data: payload.data || {},
    };

    if (payload.tag) {
        options.tag = payload.tag;
    }

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const target = event.notification.data && event.notification.data.url;
    if (!target) {
        return;
    }

    event.waitUntil(clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
        for (const client of windowClients) {
            if (client.url === target && 'focus' in client) {
                return client.focus();
            }
        }

        if (clients.openWindow) {
            return clients.openWindow(target);
        }
    }));
});
