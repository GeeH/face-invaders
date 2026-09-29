import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

/**
 * Listen for live events (votes and test pings) on the streamer's Reverb
 * channel. Echo reconnects on its own, so the game carries on without them
 * if Reverb is down. See docs/broadcasting.md for the events.
 *
 * @param {string} channel
 * @param {Record<string, (payload: object) => void>} handlers keyed by event name, e.g. `ping`
 */
export function listen(channel, handlers) {
    const echo = new Echo({
        broadcaster: 'reverb',
        Pusher,
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    });

    const subscription = echo.channel(channel);
    // The leading dot means "exactly this name", matching the events' broadcastAs().
    Object.entries(handlers).forEach(([event, handler]) => subscription.listen(`.${event}`, handler));

    return echo;
}
