<?php

namespace App\Chat;

use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use Ratchet\Client\Connector;
use React\EventLoop\Loop;

/**
 * The long-running chat worker (`php artisan chat:listen`). Every few
 * seconds it works out whose games are active and keeps exactly those
 * streamers' chats open, retrying dropped connections with a backoff.
 */
class ChatListener
{
    /**
     * A game counts as active if it started a run or opened a vote this recently.
     */
    public const ACTIVE_MINUTES = 10;

    private const CHECK_SECONDS = 5;

    private const RETRY_SECONDS = [5, 15, 30, 60, 300];

    /** @var array<int, ChatConnection> keyed by streamer id */
    private array $connections = [];

    /** @var array<int, array{at: float, failures: int}> */
    private array $retries = [];

    private Closure $log;

    public function __construct(private ChatSubscriptions $subscriptions, private ChatMessages $messages) {}

    /**
     * Streamers whose game is running and whose login lets us read their chat.
     *
     * @return Collection<int, User>
     */
    public static function activeStreamers(): Collection
    {
        return User::where('game_seen_at', '>=', now()->subMinutes(self::ACTIVE_MINUTES))
            ->get()
            ->filter(fn (User $streamer) => $streamer->canReadChat())
            ->values();
    }

    /**
     * @param  Closure(string): void  $log
     */
    public function run(Closure $log): void
    {
        $this->log = $log;
        $loop = Loop::get();
        $connector = new Connector($loop);

        $loop->addPeriodicTimer(self::CHECK_SECONDS, fn () => $this->check($connector));
        $this->check($connector);

        $loop->run();
    }

    private function check(Connector $connector): void
    {
        $active = static::activeStreamers()->keyBy('id');

        foreach ($this->connections as $id => $connection) {
            if (! $active->has($id)) {
                ($this->log)("{$connection->streamer->display_name}: game idle, leaving chat");
                $connection->close();
                unset($this->connections[$id]);
            } elseif ($connection->isStale()) {
                ($this->log)("{$connection->streamer->display_name}: Twitch went quiet, reconnecting");
                $connection->close();
                unset($this->connections[$id]);
            }
        }

        foreach ($active as $id => $streamer) {
            if (isset($this->connections[$id]) || ($this->retries[$id]['at'] ?? 0) > microtime(true)) {
                continue;
            }

            ($this->log)("{$streamer->display_name}: joining chat");
            $this->connections[$id] = new ChatConnection(
                $streamer,
                $connector,
                $this->subscriptions,
                $this->messages,
                $this->log,
                fn (ChatConnection $connection, bool $permanent) => $this->lost($connection, $permanent),
            );
            $this->connections[$id]->open();
        }
    }

    private function lost(ChatConnection $connection, bool $permanent): void
    {
        $id = $connection->streamer->id;
        unset($this->connections[$id]);

        // A connection that ran for a while before dropping starts the backoff again.
        $failures = $connection->uptime() > 120 ? 1 : ($this->retries[$id]['failures'] ?? 0) + 1;
        $backoff = self::RETRY_SECONDS;
        $wait = $permanent ? $backoff[array_key_last($backoff)] : $backoff[min($failures, count($backoff)) - 1];
        $this->retries[$id] = ['at' => microtime(true) + $wait, 'failures' => $failures];

        ($this->log)("{$connection->streamer->display_name}: trying again in {$wait}s");
    }
}
