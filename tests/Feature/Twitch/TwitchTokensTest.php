<?php

use App\Models\User;
use App\Twitch\TwitchReauthorizationRequired;
use App\Twitch\TwitchTokens;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\Token;

it('returns the current token while it is still valid', function () {
    $user = User::factory()->create(['twitch_access_token' => 'still-good']);

    Socialite::shouldReceive('driver')->never();

    expect(app(TwitchTokens::class)->accessTokenFor($user))->toBe('still-good');
});

it('refreshes and stores a token that has expired', function () {
    $user = User::factory()->withExpiredToken()->create(['twitch_refresh_token' => 'old-refresh']);

    Socialite::shouldReceive('driver->refreshToken')
        ->with('old-refresh')
        ->andReturn(new Token('new-access', 'new-refresh', 14400, []));

    expect(app(TwitchTokens::class)->accessTokenFor($user))->toBe('new-access');

    $user->refresh();
    expect($user)
        ->twitch_access_token->toBe('new-access')
        ->twitch_refresh_token->toBe('new-refresh')
        ->and($user->twitch_token_expires_at->isFuture())->toBeTrue();
});

it('refreshes a token that is about to expire', function () {
    $user = User::factory()->create(['twitch_token_expires_at' => now()->addMinute()]);

    Socialite::shouldReceive('driver->refreshToken')
        ->andReturn(new Token('new-access', 'new-refresh', 14400, []));

    expect(app(TwitchTokens::class)->accessTokenFor($user))->toBe('new-access');
});

it('clears the tokens and asks for a new login when access has been revoked', function () {
    $user = User::factory()->withExpiredToken()->create();

    Socialite::shouldReceive('driver->refreshToken')->andThrow(
        new ClientException('Invalid refresh token', new Request('POST', 'https://id.twitch.tv/oauth2/token'), new Response(400)),
    );

    expect(fn () => app(TwitchTokens::class)->accessTokenFor($user))
        ->toThrow(TwitchReauthorizationRequired::class);

    expect($user->refresh())
        ->twitch_access_token->toBeNull()
        ->twitch_refresh_token->toBeNull();
});

it('asks for a new login when there are no tokens', function () {
    $user = User::factory()->create(['twitch_access_token' => null, 'twitch_refresh_token' => null]);

    expect(fn () => app(TwitchTokens::class)->accessTokenFor($user))
        ->toThrow(TwitchReauthorizationRequired::class);
});
