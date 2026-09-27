<?php

namespace App\Enums;

enum FollowerSyncStatus: string
{
    case Queued = 'queued';
    case Running = 'running';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case NeedsLogin = 'needs_login';

    /**
     * Whether a sync is waiting or in progress.
     */
    public function inProgress(): bool
    {
        return $this === self::Queued || $this === self::Running;
    }

    /**
     * What to tell the streamer on their dashboard.
     */
    public function message(): string
    {
        return match ($this) {
            self::Queued => 'Sync queued…',
            self::Running => 'Syncing your followers…',
            self::Succeeded => 'Followers are up to date.',
            self::Failed => 'The last sync failed. Try again in a minute.',
            self::NeedsLogin => 'Twitch access has expired. Log out and back in to sync again.',
        };
    }
}
