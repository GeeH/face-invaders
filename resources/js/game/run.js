/**
 * Fetch everything needed for a new run: balance, the streamer's settings and
 * a fresh pool of faces. Called at the start of every run so balance changes
 * apply without reloading the browser source.
 *
 * @returns {Promise<{streamer: {name: string, voting_window_seconds: number}, balance: Record<string, number>, faces: Array<{id: string, name: string, avatar: string, fallback: string}>}>}
 */
export async function fetchRun(url) {
    const response = await fetch(url, { headers: { Accept: 'application/json' } });

    if (!response.ok) {
        throw new Error(`Could not start a run: ${response.status}`);
    }

    return response.json();
}
