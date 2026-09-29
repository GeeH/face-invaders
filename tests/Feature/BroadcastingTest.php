<?php

use App\Events\Game\Ping;
use App\Models\User;
use Illuminate\Support\Facades\Event;

it('gives every streamer a game channel keyed by their play token', function () {
    $streamer = User::factory()->create();

    expect($streamer->gameChannel())->toBe("play.{$streamer->play_token}");
});

it('moves the game channel when the play token is regenerated', function () {
    $streamer = User::factory()->create();
    $old = $streamer->gameChannel();

    $streamer->regeneratePlayToken();

    expect($streamer->gameChannel())->not->toBe($old);
});

it('tells the game which channel to listen on', function () {
    $streamer = User::factory()->create();

    $this->get($streamer->playUrl())
        ->assertSee('data-channel="'.$streamer->gameChannel().'"', escape: false);
});

it('broadcasts a ping on the streamer\'s game channel with only the message', function () {
    $streamer = User::factory()->create();
    $ping = new Ping($streamer, 'Testing, testing');

    expect($ping->broadcastOn()->name)->toBe($streamer->gameChannel())
        ->and($ping->broadcastAs())->toBe('ping')
        ->and($ping->broadcastWith())->toBe(['message' => 'Testing, testing']);
});

it('pings a streamer\'s game from the command line', function () {
    Event::fake([Ping::class]);
    $streamer = User::factory()->create(['display_name' => 'GeeH']);

    $this->artisan('game:ping', ['user' => $streamer->id, 'message' => 'Hi chat'])
        ->expectsOutput('Sent "Hi chat" to GeeH\'s game.')
        ->assertSuccessful();

    Event::assertDispatched(Ping::class, fn (Ping $ping) => $ping->message === 'Hi chat'
        && $ping->broadcastOn()->name === $streamer->gameChannel());
});
