<?php

namespace App\Twitch;

use App\Models\User;
use GuzzleHttp\Exception\ClientException;
use Laravel\Socialite\Facades\Socialite;

/**
 * Hands out a valid Twitch access token for a streamer, refreshing it when it
 * is about to expire. Twitch access tokens only last a few hours, but background
 * jobs such as the follower sync need to call the API long after login.
 */
class TwitchTokens
{
    /**
     * Refresh tokens this close to expiry, so they don't expire mid-request.
     */
    private const REFRESH_MARGIN_SECONDS = 300;

    /**
     * @throws TwitchReauthorizationRequired when the streamer has revoked access
     */
    public function accessTokenFor(User $user): string
    {
        if ($user->twitch_access_token === null || $user->twitch_refresh_token === null) {
            throw new TwitchReauthorizationRequired($user);
        }

        if ($user->twitch_token_expires_at?->isAfter(now()->addSeconds(self::REFRESH_MARGIN_SECONDS))) {
            return $user->twitch_access_token;
        }

        return $this->refresh($user);
    }

    private function refresh(User $user): string
    {
        try {
            $token = Socialite::driver('twitch')->refreshToken($user->twitch_refresh_token);
        } catch (ClientException $e) {
            // Twitch rejects the refresh token once the streamer disconnects the app.
            $user->forceFill([
                'twitch_access_token' => null,
                'twitch_refresh_token' => null,
                'twitch_token_expires_at' => null,
            ])->save();

            throw new TwitchReauthorizationRequired($user, $e);
        }

        $user->forceFill([
            'twitch_access_token' => $token->token,
            'twitch_refresh_token' => $token->refreshToken,
            'twitch_token_expires_at' => now()->addSeconds($token->expiresIn),
        ])->save();

        return $token->token;
    }
}
