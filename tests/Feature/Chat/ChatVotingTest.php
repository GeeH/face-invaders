<?php

use App\Chat\ChatListener;
use App\Chat\ChatMessages;
use App\Chat\ChatSubscriptions;
use App\Events\Game\VoteOpened;
use App\Events\Game\VoteTallyUpdated;
use App\Models\Follower;
use App\Models\User;
use App\Models\VoteSession;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Event::fake([VoteOpened::class, VoteTallyUpdated::class]);
    Queue::fake();

    $this->streamer = User::factory()->create(['twitch_scopes' => ['moderator:read:followers', 'user:read:chat']]);
    $this->chat = fn (string $chatter, string $text) => app(ChatMessages::class)->handle($this->streamer, [
        'chatter_user_id' => $chatter,
        'chatter_user_login' => $chatter,
        'message' => ['text' => $text],
    ]);
});

function openVoteFor(User $streamer): VoteSession
{
    return VoteSession::open($streamer, 1, [
        ['id' => 'heal', 'name' => 'Heal', 'description' => 'x'],
        ['id' => 'nova', 'name' => 'Nova pulse', 'description' => 'x'],
    ]);
}

it('asks for read-only chat access at login instead of the bot scope', function () {
    expect(config('services.twitch.scopes'))->toContain('user:read:chat')->not->toContain('channel:bot');
});

it('counts !vote in chat toward the open vote', function () {
    $session = openVoteFor($this->streamer);

    expect(($this->chat)('111', '!vote 2'))->toBeTrue()
        ->and(($this->chat)('222', '!VOTE 2 pls'))->toBeTrue()
        ->and(($this->chat)('333', 'gg'))->toBeFalse()
        ->and($session->tally())->toBe([1 => 0, 2 => 2]);
});

it('ignores votes when nothing is open', function () {
    expect(($this->chat)('111', '!vote 1'))->toBeFalse();
});

it('ignores votes for options that are not on offer', function () {
    openVoteFor($this->streamer);

    expect(($this->chat)('111', '!vote 7'))->toBeFalse();
});

it('only counts votes in the streamer\'s own vote', function () {
    $other = User::factory()->create();
    $theirs = openVoteFor($other);
    openVoteFor($this->streamer);

    ($this->chat)('111', '!vote 1');

    expect($theirs->votes()->count())->toBe(0);
});

it('marks chatting followers as active, at most once a minute', function () {
    $this->freezeSecond();
    $follower = Follower::factory()->for($this->streamer, 'streamer')->create(['provider_user_id' => '111', 'last_active_at' => null]);

    ($this->chat)('111', 'hello');
    expect($follower->refresh()->last_active_at->equalTo(now()))->toBeTrue();

    $this->travel(30)->seconds();
    ($this->chat)('111', 'hello again');
    expect($follower->refresh()->last_active_at->equalTo(now()->subSeconds(30)))->toBeTrue();
});

it('subscribes to the streamer\'s chat as the streamer', function () {
    Http::fake(['api.twitch.tv/*' => Http::response(['data' => []], 202)]);
    $this->streamer->forceFill(['twitch_id' => '4242', 'twitch_token_expires_at' => now()->addHour()])->save();

    app(ChatSubscriptions::class)->subscribe($this->streamer, 'session-1');

    Http::assertSent(fn (Request $request) => $request->url() === 'https://api.twitch.tv/helix/eventsub/subscriptions'
        && $request['type'] === 'channel.chat.message'
        && $request['condition'] === ['broadcaster_user_id' => '4242', 'user_id' => '4242']
        && $request['transport'] === ['method' => 'websocket', 'session_id' => 'session-1']);
});

it('notes the game is running when a run starts or a vote opens', function () {
    $this->freezeSecond();

    $this->getJson(route('play.run', $this->streamer->play_token))->assertOk();
    expect($this->streamer->refresh()->game_seen_at->equalTo(now()))->toBeTrue();

    $this->travel(5)->minutes();
    openVoteFor($this->streamer);
    expect($this->streamer->refresh()->game_seen_at->equalTo(now()))->toBeTrue();
});

it('listens to chat only for recently active games it can read', function () {
    $this->streamer->markGameSeen();
    $idle = User::factory()->create(['twitch_scopes' => ['user:read:chat'], 'game_seen_at' => now()->subMinutes(ChatListener::ACTIVE_MINUTES + 1)]);
    $oldLogin = User::factory()->create(['twitch_scopes' => ['moderator:read:followers', 'channel:bot'], 'game_seen_at' => now()]);
    $revoked = User::factory()->create(['twitch_scopes' => ['user:read:chat'], 'game_seen_at' => now(), 'twitch_refresh_token' => null]);

    expect(ChatListener::activeStreamers()->pluck('id')->all())->toBe([$this->streamer->id]);
});
