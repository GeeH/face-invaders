<?php

use App\Jobs\SyncFollowers;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use SocialiteProviders\Manager\OAuth2\User as SocialiteUser;

function fakeTwitchUser(array $overrides = []): SocialiteUser
{
    $attributes = array_merge([
        'id' => '123456',
        'login' => 'geeh',
        'display_name' => 'GeeH',
        'profile_image_url' => 'https://static-cdn.jtvnw.net/avatar.png',
    ], $overrides);

    return (new SocialiteUser)
        ->setRaw($attributes)
        ->map([
            'id' => $attributes['id'],
            'nickname' => $attributes['display_name'],
            'name' => $attributes['display_name'],
            'avatar' => $attributes['profile_image_url'],
        ])
        ->setToken('access-token')
        ->setRefreshToken('refresh-token')
        ->setExpiresIn(14400)
        ->setApprovedScopes(['moderator:read:followers', 'channel:bot']);
}

function fakeTwitchCallback(SocialiteUser $user): void
{
    Socialite::shouldReceive('driver->user')->andReturn($user);
}

it('redirects to Twitch asking for the scopes we need', function () {
    $location = $this->get(route('login'))->assertRedirect()->headers->get('Location');

    expect($location)
        ->toStartWith('https://id.twitch.tv/oauth2/authorize')
        ->toContain('client_id=test-client-id')
        ->toContain('scope='.urlencode('moderator:read:followers user:read:chat'));
});

it('creates a streamer from their Twitch account and logs them in', function () {
    fakeTwitchCallback(fakeTwitchUser());

    $this->get(route('auth.twitch.callback'))->assertRedirect(route('dashboard'));

    $user = User::sole();
    expect($user)
        ->twitch_id->toBe('123456')
        ->twitch_login->toBe('geeh')
        ->display_name->toBe('GeeH')
        ->avatar_url->toBe('https://static-cdn.jtvnw.net/avatar.png')
        ->twitch_access_token->toBe('access-token')
        ->twitch_refresh_token->toBe('refresh-token')
        ->twitch_scopes->toBe(['moderator:read:followers', 'channel:bot'])
        ->and($user->twitch_token_expires_at->between(now()->addHours(4)->subMinute(), now()->addHours(4)))->toBeTrue();

    $this->assertAuthenticatedAs($user);
});

it('queues a follower sync when the streamer logs in', function () {
    Queue::fake();
    fakeTwitchCallback(fakeTwitchUser());

    $this->get(route('auth.twitch.callback'));

    Queue::assertPushed(SyncFollowers::class, fn (SyncFollowers $job) => $job->streamer->is(User::sole()));
});

it('updates an existing streamer instead of creating a duplicate', function () {
    User::factory()->create(['twitch_id' => '123456', 'display_name' => 'OldName']);

    fakeTwitchCallback(fakeTwitchUser(['display_name' => 'NewName']));

    $this->get(route('auth.twitch.callback'));

    expect(User::sole()->display_name)->toBe('NewName');
});

it('stores Twitch tokens encrypted', function () {
    fakeTwitchCallback(fakeTwitchUser());

    $this->get(route('auth.twitch.callback'));

    $row = DB::table('users')->sole();
    expect($row->twitch_access_token)->not->toContain('access-token')
        ->and($row->twitch_refresh_token)->not->toContain('refresh-token');
});

it('sends the streamer home when they cancel on Twitch', function () {
    $this->get(route('auth.twitch.callback', ['error' => 'access_denied']))
        ->assertRedirect(route('home'))
        ->assertSessionHas('error');

    $this->assertGuest();
});

it('sends the streamer home when the login state has expired', function () {
    Socialite::shouldReceive('driver->user')->andThrow(new InvalidStateException);

    $this->get(route('auth.twitch.callback'))
        ->assertRedirect(route('home'))
        ->assertSessionHas('error');

    $this->assertGuest();
});

it('requires login for the dashboard', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

it('shows the dashboard to a logged in streamer', function () {
    $user = User::factory()->create(['display_name' => 'GeeH']);

    $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('GeeH');
});

it('sends a logged in streamer to the dashboard instead of logging in again', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('login'))
        ->assertRedirect(route('dashboard'));
});

it('logs the streamer out', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('logout'))
        ->assertRedirect(route('home'));

    $this->assertGuest();
});
