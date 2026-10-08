import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

/**
 * Real-time client (CHT-2, NTF-2).
 *
 * Aktif hanya bila VITE_PUSHER_APP_KEY terisi. Tanpa kredensial Pusher,
 * window.Echo tetap undefined dan komponen Livewire memakai polling
 * sebagai cadangan (NTF-E1). Status koneksi disiarkan lewat CustomEvent
 * `simokas:realtime` dengan detail { connected: boolean }.
 */
if (import.meta.env.VITE_PUSHER_APP_KEY) {
    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: import.meta.env.VITE_PUSHER_APP_KEY,
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'ap1',
        forceTLS: true,
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                'X-Requested-With': 'XMLHttpRequest',
            },
        },
    });

    const connection = window.Echo.connector.pusher.connection;

    const announce = (connected) => {
        window.dispatchEvent(new CustomEvent('simokas:realtime', { detail: { connected } }));
    };

    connection.bind('connected', () => announce(true));
    connection.bind('disconnected', () => announce(false));
    connection.bind('unavailable', () => announce(false));
    connection.bind('failed', () => announce(false));
}
