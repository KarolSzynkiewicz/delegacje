function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw = atob(base64);
    const output = new Uint8Array(raw.length);

    for (let i = 0; i < raw.length; i += 1) {
        output[i] = raw.charCodeAt(i);
    }

    return output;
}

export function registerPushWorker() {
    const key = document.querySelector('meta[name="vapid-public-key"]')?.content;

    if (!key || !('serviceWorker' in navigator)) {
        return;
    }

    navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(() => {});
}

window.notificationDevice = function notificationDevice(vapidPublicKey) {
    return {
        state: 'checking',
        busy: false,
        endpoint: null,
        vapidPublicKey: vapidPublicKey || '',

        async init() {
            if (!('serviceWorker' in navigator) || !('PushManager' in window) || !window.Notification) {
                this.state = 'unsupported';
                return;
            }

            if (!this.vapidPublicKey) {
                this.state = 'unconfigured';
                return;
            }

            if (Notification.permission === 'denied') {
                this.state = 'denied';
                return;
            }

            try {
                const registration = await navigator.serviceWorker.register('/sw.js', { scope: '/' });
                const existing = await registration.pushManager.getSubscription();
                this.endpoint = existing?.endpoint ?? null;
                this.state = existing ? 'on' : 'off';
            } catch (error) {
                this.state = 'off';
            }
        },

        async enable() {
            if (this.busy || !this.vapidPublicKey) {
                return;
            }

            this.busy = true;

            try {
                const permission = await Notification.requestPermission();
                if (permission !== 'granted') {
                    this.state = permission === 'denied' ? 'denied' : 'off';
                    return;
                }

                const registration = await navigator.serviceWorker.ready;
                const subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(this.vapidPublicKey),
                });
                const payload = subscription.toJSON();
                payload.contentEncoding = (PushManager.supportedContentEncodings || ['aes128gcm'])[0];
                await this.$wire.storePushSubscription(payload);
                this.endpoint = subscription.endpoint;
                this.state = 'on';
            } catch (error) {
                this.state = 'error';
            } finally {
                this.busy = false;
            }
        },

        async disable() {
            if (this.busy) {
                return;
            }

            this.busy = true;

            try {
                const registration = await navigator.serviceWorker.ready;
                const subscription = await registration.pushManager.getSubscription();
                const endpoint = subscription?.endpoint ?? this.endpoint;

                if (subscription) {
                    await subscription.unsubscribe();
                }

                if (endpoint) {
                    await this.$wire.deletePushSubscription(endpoint);
                }

                this.endpoint = null;
                this.state = 'off';
            } finally {
                this.busy = false;
            }
        },
    };
};
