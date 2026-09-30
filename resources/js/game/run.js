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

/**
 * Open chat's vote on the drawn upgrades. Laravel runs the vote and
 * broadcasts the tally and winner over Reverb.
 *
 * @param {{wave: number, options: Array<{id: string, name: string, description: string}>}} vote
 * @returns {Promise<{id: number, options: Array<{number: number, id: string, name: string, description: string}>, closes_at: string}>}
 */
export async function openVote(url, vote) {
    const response = await fetch(url, {
        method: 'POST',
        headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify(vote),
    });

    if (!response.ok) {
        throw new Error(`Could not open the vote: ${response.status}`);
    }

    return response.json();
}

/**
 * The streamer picked with the number keys: close the server's vote with the
 * same upgrade, so the recorded winner matches what was applied.
 */
export async function pickVote(url, id) {
    const response = await fetch(url, {
        method: 'POST',
        headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ id }),
    });

    if (!response.ok) {
        throw new Error(`Could not pick the upgrade: ${response.status}`);
    }

    return response.json();
}
