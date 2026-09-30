<?php

namespace App\Jobs;

use App\Enums\FollowerSyncStatus;
use App\Faces\Face;
use App\Faces\FaceProvider;
use App\Models\Follower;
use App\Models\User;
use App\Twitch\TwitchReauthorizationRequired;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\Attributes\UniqueFor;
use Illuminate\Support\LazyCollection;
use Throwable;

/**
 * Copies a streamer's followers from their face provider into the database,
 * so the game never has to call Twitch while it loads.
 */
#[Tries(3)]
#[Backoff(60, 300)]
#[Timeout(600)]
#[UniqueFor(900)]
#[DeleteWhenMissingModels]
class SyncFollowers implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    private const CHUNK_SIZE = 500;

    public function __construct(public User $streamer) {}

    /**
     * Only one sync per streamer at a time.
     */
    public function uniqueId(): string
    {
        return (string) $this->streamer->id;
    }

    public function handle(FaceProvider $provider): void
    {
        $startedAt = now();
        $this->setStatus(FollowerSyncStatus::Running);

        try {
            LazyCollection::make(fn () => yield from $provider->fetchFaces($this->streamer))
                ->chunk(self::CHUNK_SIZE)
                ->each(fn (LazyCollection $faces) => $this->store($provider->name(), $faces, $startedAt));
        } catch (TwitchReauthorizationRequired) {
            // Retrying won't help until the streamer logs in again, which syncs them anyway.
            $this->setStatus(FollowerSyncStatus::NeedsLogin);
            $this->fail('The streamer needs to log in to Twitch again.');

            return;
        }

        // Only reached when every page was fetched, so a failed sync never removes anyone.
        $this->streamer->followers()
            ->where('provider', $provider->name())
            ->where('synced_at', '<', $startedAt)
            ->delete();

        $this->streamer->forceFill([
            'followers_sync_status' => FollowerSyncStatus::Succeeded,
            'followers_synced_at' => $startedAt,
        ])->save();
    }

    /**
     * Called once every retry has been used up.
     */
    public function failed(?Throwable $e): void
    {
        if ($this->streamer->fresh()?->followers_sync_status !== FollowerSyncStatus::NeedsLogin) {
            $this->setStatus(FollowerSyncStatus::Failed);
        }
    }

    private function setStatus(FollowerSyncStatus $status): void
    {
        $this->streamer->forceFill(['followers_sync_status' => $status])->save();
    }

    /**
     * @param  LazyCollection<int, Face>  $faces
     */
    private function store(string $provider, LazyCollection $faces, DateTimeInterface $syncedAt): void
    {
        $rows = $faces->map(fn (Face $face) => [
            'user_id' => $this->streamer->id,
            'provider' => $provider,
            'provider_user_id' => $face->providerUserId,
            'username' => $face->username,
            'display_name' => $face->displayName,
            'avatar_url' => $face->avatarUrl,
            'followed_at' => $face->followedAt,
            'synced_at' => $syncedAt,
        ])->values()->all();

        // last_active_at is left alone: the chat listener owns it, not the sync.
        Follower::upsert(
            $rows,
            uniqueBy: ['user_id', 'provider', 'provider_user_id'],
            update: ['username', 'display_name', 'avatar_url', 'followed_at', 'synced_at'],
        );
    }
}
