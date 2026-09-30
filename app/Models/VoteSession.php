<?php

namespace App\Models;

use App\Enums\VoteClosedBy;
use App\Events\Game\VoteClosed;
use App\Events\Game\VoteOpened;
use App\Events\Game\VoteTallyUpdated;
use App\Jobs\CloseVoteSession;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use InvalidArgumentException;

/**
 * Chat's vote on an upgrade between waves. The game opens it with the drawn
 * options; viewers vote with `!vote N`; it closes when the streamer's voting
 * window runs out (or on a dashboard override) and picks the winner.
 *
 * Each viewer has one vote. Voting again changes it, so chat can switch as
 * the tally moves.
 */
#[Fillable(['wave', 'options', 'closes_at'])]
class VoteSession extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'wave' => 'integer',
            'options' => 'array',
            'closes_at' => 'datetime',
            'closed_at' => 'datetime',
            'closed_by' => VoteClosedBy::class,
        ];
    }

    /**
     * Open a vote for a streamer, replacing any vote still open from before
     * (e.g. if the game restarted mid-vote), and schedule it to close.
     *
     * @param  list<array{id: string, name: string, description: string}>  $options
     */
    public static function open(User $streamer, int $wave, array $options): self
    {
        $streamer->voteSessions()->open()->each(fn (VoteSession $old) => $old->close(VoteClosedBy::Replaced));
        $streamer->markGameSeen();

        $session = $streamer->voteSessions()->create([
            'wave' => $wave,
            'options' => array_map(
                fn (array $option, int $i) => ['number' => $i + 1, ...Arr::only($option, ['id', 'name', 'description'])],
                $options,
                array_keys($options),
            ),
            'closes_at' => now()->addSeconds($streamer->voting_window_seconds),
        ]);

        CloseVoteSession::dispatch($session)->delay($session->closes_at);
        VoteOpened::dispatch($session);

        return $session;
    }

    /**
     * Record a viewer's vote for option N. Votes for options that don't
     * exist, or after the vote has closed, are ignored.
     */
    public function vote(string $voterId, int $choice): bool
    {
        if ($this->isClosed() || $this->option($choice) === null) {
            return false;
        }

        $this->votes()->updateOrCreate(['voter_id' => $voterId], ['choice' => $choice]);
        VoteTallyUpdated::dispatch($this);

        return true;
    }

    /**
     * Votes per option number, including options nobody voted for.
     *
     * @return array<int, int>
     */
    public function tally(): array
    {
        $counts = $this->votes()->selectRaw('choice, count(*) as votes')->groupBy('choice')->pluck('votes', 'choice');

        return collect($this->options)
            ->mapWithKeys(fn (array $option) => [$option['number'] => (int) ($counts[$option['number']] ?? 0)])
            ->all();
    }

    /**
     * Close the vote and pick the winner: the option with the most votes, a
     * random one of the leaders on a tie, or any option at random if nobody
     * voted. An override picks the winner outright. A replaced vote has no
     * winner. Returns the winning upgrade's id; closing twice does nothing.
     */
    public function close(VoteClosedBy $by = VoteClosedBy::Timer, ?string $override = null): ?string
    {
        if ($by === VoteClosedBy::Override && ! collect($this->options)->contains('id', $override)) {
            throw new InvalidArgumentException("[{$override}] isn't one of this vote's options.");
        }

        $winner = match ($by) {
            VoteClosedBy::Override => $override,
            VoteClosedBy::Replaced => null,
            VoteClosedBy::Timer => $this->pickWinner(),
        };

        // Only the first close wins, whether it's the timer or an override.
        $closed = static::whereKey($this->id)->whereNull('closed_at')
            ->update(['closed_at' => now(), 'closed_by' => $by, 'winner' => $winner]);

        if ($closed === 0) {
            return $this->refresh()->winner;
        }

        $this->refresh();

        if ($winner !== null) {
            VoteClosed::dispatch($this);
        }

        return $winner;
    }

    /**
     * The streamer picks option N themselves (dashboard or Stream Deck),
     * closing the vote with that winner. Returns the winning upgrade's id.
     */
    public function pick(int $number): ?string
    {
        $option = $this->option($number) ?? throw new InvalidArgumentException("This vote has no option {$number}.");

        return $this->close(VoteClosedBy::Override, $option['id']);
    }

    public function isClosed(): bool
    {
        return $this->closed_at !== null;
    }

    /**
     * @return array{number: int, id: string, name: string, description: string}|null
     */
    public function option(int $number): ?array
    {
        return collect($this->options)->firstWhere('number', $number);
    }

    private function pickWinner(): string
    {
        $tally = $this->tally();
        $leaders = array_keys($tally, max($tally), true);

        return $this->option(Arr::random($leaders))['id'];
    }

    /**
     * @param  Builder<VoteSession>  $query
     */
    public function scopeOpen($query): void
    {
        $query->whereNull('closed_at');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function streamer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany<Vote, $this>
     */
    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }
}
