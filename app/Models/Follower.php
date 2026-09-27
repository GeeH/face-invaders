<?php

namespace App\Models;

use Database\Factories\FollowerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A viewer who follows a streamer, and whose face can appear on that streamer's enemies.
 */
#[Fillable([
    'provider',
    'provider_user_id',
    'username',
    'display_name',
    'avatar_url',
    'followed_at',
    'last_active_at',
    'synced_at',
])]
class Follower extends Model
{
    /** @use HasFactory<FollowerFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'followed_at' => 'datetime',
            'last_active_at' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }

    /**
     * The streamer being followed.
     *
     * @return BelongsTo<User, $this>
     */
    public function streamer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
