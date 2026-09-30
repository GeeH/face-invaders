import './dashboard-vote';

// While a follower sync is running, poll its status and reload the dashboard once it finishes.
const followerSync = document.querySelector('[data-follower-sync]');

if (followerSync?.dataset.inProgress === 'true') {
    const poll = async () => {
        try {
            const response = await fetch(followerSync.dataset.statusUrl, {
                headers: { Accept: 'application/json' },
            });
            const { in_progress: inProgress } = await response.json();

            if (!inProgress) {
                window.location.reload();
                return;
            }
        } catch {
            // Network blip: keep polling.
        }

        setTimeout(poll, 2000);
    };

    setTimeout(poll, 2000);
}
