<?php

namespace App\Twitch;

use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Builds HTTP requests to the Twitch Helix API on behalf of a streamer.
 */
class Helix
{
    public const BASE_URL = 'https://api.twitch.tv/helix';

    /**
     * Retry rate-limited requests up to this many times before giving up.
     */
    private const RATE_LIMIT_RETRIES = 3;

    public function __construct(private TwitchTokens $tokens) {}

    /**
     * A request authorised with the streamer's token. When Twitch rate limits
     * us (429), it waits until the limit resets and tries again.
     */
    public function as(User $user): PendingRequest
    {
        return Http::baseUrl(config('services.twitch.helix_url', self::BASE_URL))
            ->withToken($this->tokens->accessTokenFor($user))
            ->withHeaders(['Client-Id' => config('services.twitch.client_id')])
            ->acceptJson()
            ->retry(
                self::RATE_LIMIT_RETRIES + 1,
                fn (int $attempt, Throwable $e) => $this->millisecondsUntilReset($e),
                fn (Throwable $e) => $e instanceof RequestException && $e->response->status() === 429,
            )
            ->throw();
    }

    /**
     * Twitch sends the Unix time the rate limit bucket refills in Ratelimit-Reset.
     */
    private function millisecondsUntilReset(Throwable $e): int
    {
        $reset = $e instanceof RequestException ? (int) $e->response->header('Ratelimit-Reset') : 0;

        return $reset > 0 ? max(0, $reset - now()->timestamp) * 1000 + 250 : 1000;
    }
}
