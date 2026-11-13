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

    const dispatchWorkspaceSync = (change) => {
        const actorId = Number(change?.actor_id);
        const currentUserId = Number(document.body.dataset.workspaceUser);
        const lastMutationAt = Number(sessionStorage.getItem('tuklas:last-mutation')) || 0;
        if (actorId === currentUserId && Date.now() - lastMutationAt < 4000) return;
        window.dispatchEvent(new CustomEvent('tuklas:workspace-sync', {
            detail: { resource: change?.resource },
        }));
    };

    window.Echo.private('workspace').listen('.workspace.changed', dispatchWorkspaceSync);
    const userId = Number(document.body.dataset.workspaceUser);
    if (userId) {
        window.Echo.private(`users.${userId}`).listen('.notifications.changed', () => {
            window.dispatchEvent(new CustomEvent('tuklas:notifications-sync'));
        });
    }
    window.Echo.connector.pusher.connection.bind('state_change', ({ current, previous }) => {
        if (current === 'connected' && previous !== 'connected') {
            window.dispatchEvent(new CustomEvent('tuklas:workspace-reconnected'));
        }
    });
}
