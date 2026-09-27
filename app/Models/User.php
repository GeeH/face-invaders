<?php

namespace App\Models;

use App\Enums\FollowerSyncStatus;
use App\Jobs\SyncFollowers;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable([
    'twitch_id',
    'twitch_login',
    'display_name',
    'avatar_url',
    'twitch_access_token',
    'twitch_refresh_token',
    'twitch_token_expires_at',
    'twitch_scopes',
    'followers_sync_status',
    'followers_synced_at',
    'voting_window_seconds',
])]
#[Hidden(['twitch_access_token', 'twitch_refresh_token', 'play_token', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Give every new streamer a game URL.
     */
    protected static function booted(): void
    {
        static::creating(function (User $user) {
            $user->play_token ??= static::newPlayToken();
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'twitch_access_token' => 'encrypted',
            'twitch_refresh_token' => 'encrypted',
            'twitch_token_expires_at' => 'datetime',
            'twitch_scopes' => 'array',
            'followers_sync_status' => FollowerSyncStatus::class,
            'followers_synced_at' => 'datetime',
            'voting_window_seconds' => 'integer',
        ];
    }

    /**
     * Viewers who follow this streamer.
     *
     * @return HasMany<Follower, $this>
     */
    public function followers(): HasMany
    {
        return $this->hasMany(Follower::class);
    }

    /**
     * The streamer's personal game URL, pasted into OBS as a browser source.
     */
    public function playUrl(): string
    {
        return route('play', $this->play_token);
    }

    /**
     * Replace the game URL, e.g. if it leaked on stream. The old URL stops working.
     */
    public function regeneratePlayToken(): void
    {
        $this->forceFill(['play_token' => static::newPlayToken()])->save();
    }

    private static function newPlayToken(): string
    {
        return Str::random(40);
    }

    /**
     * Queue a sync of this streamer's followers and show it as queued on their dashboard.
     */
    public function syncFollowers(): void
    {
        $this->forceFill(['followers_sync_status' => FollowerSyncStatus::Queued])->save();

        SyncFollowers::dispatch($this);
    }
}
