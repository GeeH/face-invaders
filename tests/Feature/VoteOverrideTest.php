<?php

use App\Enums\VoteClosedBy;
use App\Events\Game\VoteClosed;
use App\Events\Game\VoteOpened;
use App\Events\Game\VoteTallyUpdated;
use App\Models\User;
use App\Models\VoteSession;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Event::fake([VoteOpened::class, VoteTallyUpdated::class, VoteClosed::class]);
    Queue::fake();

    $this->streamer = User::factory()->create();
    $this->openVote = fn (?User $streamer = null) => VoteSession::open($streamer ?? $this->streamer, 2, [
        ['id' => 'heal', 'name' => 'Heal', 'description' => 'Repair 3 lives'],
        ['id' => 'nova', 'name' => 'Nova pulse', 'description' => 'Blasts a shockwave, often'],
        ['id' => 'drone', 'name' => 'Drone wingman', 'description' => '+1 drone fires for you'],
    ]);
});

it('lets a Stream Deck pick option N of the open vote, by GET or POST', function (string $method) {
    $session = ($this->openVote)();

    $this->json($method, route('play.pick', [$this->streamer->play_token, 2]))
        ->assertOk()
        ->assertExactJson(['winner' => 'nova', 'name' => 'Nova pulse']);

    expect($session->refresh()->winner)->toBe('nova')
        ->and($session->closed_by)->toBe(VoteClosedBy::Override);
    Event::assertDispatched(VoteClosed::class);
})->with(['GET', 'POST']);

it('tells the Stream Deck when no vote is open', function () {
    $this->getJson(route('play.pick', [$this->streamer->play_token, 1]))
        ->assertConflict()
        ->assertJson(['message' => 'No vote is open right now.']);
});

it('rejects a Stream Deck pick for an option the vote does not have', function () {
    ($this->openVote)();

    $this->getJson(route('play.pick', [$this->streamer->play_token, 5]))->assertUnprocessable();

    expect($this->streamer->openVote())->not->toBeNull();
});

it('keeps the first winner if the vote already closed', function () {
    $session = ($this->openVote)();
    $session->pick(1);

    $this->getJson(route('play.pick', [$this->streamer->play_token, 3]))->assertConflict();

    expect($session->refresh()->winner)->toBe('heal');
});

it('returns 404 for a Stream Deck URL with an unknown token', function () {
    $this->getJson(route('play.pick', ['not-a-real-token', 1]))->assertNotFound();
});

it('shows the open vote with live counts and pick buttons on the dashboard', function () {
    $session = ($this->openVote)();
    $session->vote('viewer-a', 2);
    $session->vote('viewer-b', 2);

    $this->actingAs($this->streamer)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Chat is voting now')
        ->assertSee('Pick Nova pulse')
        ->assertSee('data-votes-for="2">2</span> votes', escape: false)
        ->assertSee('data-channel="'.$this->streamer->gameChannel().'"', escape: false);
});

it('shows no pick buttons when no vote is open', function () {
    $this->actingAs($this->streamer)->get(route('dashboard'))
        ->assertSee('No vote is open')
        ->assertDontSee('Pick Heal');
});

it('shows a Stream Deck URL for each option', function () {
    $response = $this->actingAs($this->streamer)->get(route('dashboard'))->assertSee('Pick from a Stream Deck');

    foreach ([1, 2, 3] as $number) {
        $response->assertSee(route('play.pick', [$this->streamer->play_token, $number]));
    }
});

it('lets the streamer pick the winner from the dashboard', function () {
    $session = ($this->openVote)();

    $this->actingAs($this->streamer)
        ->from(route('dashboard'))
        ->post(route('vote.pick'), ['number' => 3])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('status', 'Picked Drone wingman.');

    expect($session->refresh()->winner)->toBe('drone')
        ->and($session->closed_by)->toBe(VoteClosedBy::Override);
});

it('only picks in the streamer\'s own vote', function () {
    $theirs = ($this->openVote)(User::factory()->create());

    $this->actingAs($this->streamer)
        ->from(route('dashboard'))
        ->post(route('vote.pick'), ['number' => 1])
        ->assertSessionHas('error', 'That vote has already closed.');

    expect($theirs->refresh()->isClosed())->toBeFalse();
});

it('keeps dashboard picks to logged-in streamers', function () {
    ($this->openVote)();

    $this->post(route('vote.pick'), ['number' => 1])->assertRedirect(route('login'));
});
