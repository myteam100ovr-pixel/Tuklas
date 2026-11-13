import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

const appKey = import.meta.env.VITE_REVERB_APP_KEY;

if (appKey) {
    const scheme = import.meta.env.VITE_REVERB_SCHEME
        ?? (window.location.protocol === 'https:' ? 'https' : 'http');
    const port = Number(import.meta.env.VITE_REVERB_PORT)
        || (scheme === 'https' ? 443 : 80);

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: appKey,
        wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname,
        wsPort: port,
        wssPort: port,
        forceTLS: scheme === 'https',
        enabledTransports: ['ws', 'wss'],
    });
}
