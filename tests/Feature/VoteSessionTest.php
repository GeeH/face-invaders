<?php

use App\Enums\VoteClosedBy;
use App\Events\Game\VoteClosed;
use App\Events\Game\VoteOpened;
use App\Events\Game\VoteTallyUpdated;
use App\Jobs\CloseVoteSession;
use App\Models\User;
use App\Models\VoteSession;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Event::fake([VoteOpened::class, VoteTallyUpdated::class, VoteClosed::class]);
    Queue::fake();

    $this->streamer = User::factory()->create(['voting_window_seconds' => 20]);
    $this->upgrades = [
        ['id' => 'heal', 'name' => 'Heal', 'description' => 'Repair 3 lives'],
        ['id' => 'attack-speed', 'name' => 'Attack speed', 'description' => 'Fire 25% faster'],
        ['id' => 'turn-speed', 'name' => 'Turn speed', 'description' => 'Turn 25% faster'],
    ];
    $this->openVote = fn () => VoteSession::open($this->streamer, 3, $this->upgrades);
});

it('opens a vote with numbered options for the streamer\'s voting window', function () {
    $this->freezeSecond();

    $session = ($this->openVote)();

    expect($session->wave)->toBe(3)
        ->and($session->options[1])->toBe(['number' => 2, 'id' => 'attack-speed', 'name' => 'Attack speed', 'description' => 'Fire 25% faster'])
        ->and($session->closes_at->equalTo(now()->addSeconds(20)))->toBeTrue()
        ->and($session->isClosed())->toBeFalse();

    Event::assertDispatched(VoteOpened::class);
    Queue::assertPushed(CloseVoteSession::class, fn ($job) => $job->session->is($session) && $job->delay->equalTo($session->closes_at));
});

it('replaces a vote left open from before without picking a winner', function () {
    $old = ($this->openVote)();
    $new = ($this->openVote)();

    expect($old->refresh()->closed_by)->toBe(VoteClosedBy::Replaced)
        ->and($old->winner)->toBeNull()
        ->and($new->isClosed())->toBeFalse();

    Event::assertNotDispatched(VoteClosed::class);
});

it('counts one vote per viewer, and a new vote replaces their old one', function () {
    $session = ($this->openVote)();

    $session->vote('viewer-a', 1);
    $session->vote('viewer-b', 1);
    $session->vote('viewer-a', 3);

    expect($session->tally())->toBe([1 => 1, 2 => 0, 3 => 1]);
    Event::assertDispatchedTimes(VoteTallyUpdated::class, 3);
});

it('ignores votes for options that do not exist', function (int $choice) {
    $session = ($this->openVote)();

    expect($session->vote('viewer-a', $choice))->toBeFalse()
        ->and($session->tally())->toBe([1 => 0, 2 => 0, 3 => 0]);
})->with([0, 4, -1]);

it('ignores votes once the vote has closed', function () {
    $session = ($this->openVote)();
    $session->close();

    expect($session->vote('viewer-a', 1))->toBeFalse()
        ->and($session->votes()->count())->toBe(0);
});

it('picks the option with the most votes', function () {
    $session = ($this->openVote)();
    $session->vote('viewer-a', 2);
    $session->vote('viewer-b', 2);
    $session->vote('viewer-c', 1);

    expect($session->close())->toBe('attack-speed')
        ->and($session->closed_by)->toBe(VoteClosedBy::Timer);

    Event::assertDispatched(VoteClosed::class, fn ($event) => $event->broadcastWith()['winner'] === 'attack-speed');
});

it('picks at random between the leaders on a tie', function () {
    $winners = collect(range(1, 30))->map(function () {
        $session = ($this->openVote)();
        $session->vote('viewer-a', 1);
        $session->vote('viewer-b', 3);

        return $session->close();
    });

    expect($winners->unique()->sort()->values()->all())->toBe(['heal', 'turn-speed']);
});

it('picks any option at random when nobody votes', function () {
    $winners = collect(range(1, 40))->map(fn () => ($this->openVote)()->close());

    expect($winners->unique()->sort()->values()->all())->toBe(['attack-speed', 'heal', 'turn-speed']);
});

it('lets an override pick the winner, and the timer then does nothing', function () {
    $session = ($this->openVote)();
    $session->vote('viewer-a', 1);

    expect($session->close(VoteClosedBy::Override, 'turn-speed'))->toBe('turn-speed');

    (new CloseVoteSession($session->fresh()))->handle();

    expect($session->refresh()->winner)->toBe('turn-speed')
        ->and($session->closed_by)->toBe(VoteClosedBy::Override);
    Event::assertDispatchedTimes(VoteClosed::class, 1);
});

it('rejects an override for an upgrade that is not on offer', function () {
    ($this->openVote)()->close(VoteClosedBy::Override, 'max-health');
})->throws(InvalidArgumentException::class);

it('closes the vote when the window runs out', function () {
    $session = ($this->openVote)();
    $session->vote('viewer-a', 3);

    (new CloseVoteSession($session))->handle();

    expect($session->refresh()->winner)->toBe('turn-speed')
        ->and($session->closed_by)->toBe(VoteClosedBy::Timer);
});

it('broadcasts each vote event on the streamer\'s game channel with its payload', function () {
    $session = ($this->openVote)();
    $session->vote('viewer-a', 2);
    $session->close();

    $opened = new VoteOpened($session);
    $tally = new VoteTallyUpdated($session);
    $closed = new VoteClosed($session);

    expect(collect([$opened, $tally, $closed])->map(fn ($event) => $event->broadcastOn()->name)->unique()->all())
        ->toBe([$this->streamer->gameChannel()])
        ->and($opened->broadcastAs())->toBe('vote.opened')
        ->and($opened->broadcastWith())->toMatchArray(['id' => $session->id, 'options' => $session->options])
        ->and($tally->broadcastWith())->toBe(['id' => $session->id, 'tally' => [1 => 0, 2 => 1, 3 => 0]])
        ->and($closed->broadcastWith())->toBe(['id' => $session->id, 'winner' => 'attack-speed', 'tally' => [1 => 0, 2 => 1, 3 => 0]]);
});

it('lets the game open a vote with its play token', function () {
    $this->postJson(route('play.votes', $this->streamer->play_token), ['wave' => 2, 'options' => $this->upgrades])
        ->assertCreated()
        ->assertJsonPath('options.0', ['number' => 1, 'id' => 'heal', 'name' => 'Heal', 'description' => 'Repair 3 lives'])
        ->assertJsonStructure(['id', 'options', 'closes_at']);

    expect($this->streamer->voteSessions()->sole()->wave)->toBe(2);
});

it('rejects a vote the game sends with bad options', function (array $options) {
    $this->postJson(route('play.votes', $this->streamer->play_token), ['wave' => 2, 'options' => $options])
        ->assertUnprocessable();
})->with([
    'none' => [[]],
    'missing a name' => [[['id' => 'heal', 'description' => 'x']]],
    'the same upgrade twice' => [[['id' => 'heal', 'name' => 'Heal', 'description' => 'x'], ['id' => 'heal', 'name' => 'Heal', 'description' => 'x']]],
    'more than 9' => [array_map(fn ($i) => ['id' => "u{$i}", 'name' => 'U', 'description' => 'x'], range(1, 10))],
]);

it('returns 404 when opening a vote with an unknown token', function () {
    $this->postJson(route('play.votes', 'not-a-real-token'), ['wave' => 1, 'options' => $this->upgrades])
        ->assertNotFound();
});
