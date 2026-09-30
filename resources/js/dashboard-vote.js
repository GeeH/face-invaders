import { listen } from './game/live';

/**
 * Keep the dashboard's vote panel live: counts update as chat votes, and the
 * panel reloads when a vote opens or closes so the buttons match.
 */
const panel = document.querySelector('[data-vote]');

if (panel) {
    const voteId = Number(panel.dataset.voteId) || null;
    const reload = () => window.location.reload();

    listen(panel.dataset.channel, {
        'vote.opened': reload,
        'vote.closed': ({ id }) => id === voteId && reload(),
        'vote.tally': ({ id, tally }) => {
            if (id !== voteId) {
                return;
            }

            Object.entries(tally).forEach(([number, votes]) => {
                const count = panel.querySelector(`[data-votes-for="${number}"]`);

                if (count) {
                    count.textContent = votes;
                    count.nextSibling.textContent = votes === 1 ? ' vote' : ' votes';
                }
            });
        },
    });

    const countdown = panel.querySelector('[data-closes-at]');

    if (countdown) {
        const closesAt = Date.parse(countdown.dataset.closesAt);
        const tick = () => {
            countdown.textContent = `${Math.max(0, Math.ceil((closesAt - Date.now()) / 1000))}s`;
        };
        tick();
        setInterval(tick, 500);
    }
}

// Copy buttons, e.g. for the Stream Deck URLs.
document.querySelectorAll('[data-copy]').forEach((button) => {
    button.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(button.dataset.copy);
            button.textContent = 'Copied';
            setTimeout(() => (button.textContent = 'Copy'), 1500);
        } catch {
            // Clipboard blocked: the URL is still there to select by hand.
        }
    });
});
