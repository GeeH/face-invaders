<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'twitch_id',
    'twitch_login',
    'display_name',
    'avatar_url',
    'twitch_access_token',
    'twitch_refresh_token',
    'twitch_token_expires_at',
    'twitch_scopes',
])]
#[Hidden(['twitch_access_token', 'twitch_refresh_token', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
        ];
    }
}
