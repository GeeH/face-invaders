<?php

namespace App\Chat;

use App\Models\User;
use Closure;
use Ratchet\Client\Connector;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\MessageInterface;
use Throwable;

/**
 * One streamer's chat, over an EventSub WebSocket. Handles Twitch moving us
 * to a new connection (session_reconnect) itself; if the connection drops or
 * goes quiet, it reports itself down and the ChatListener opens a fresh one.
 */
class ChatConnection
{
    private ?WebSocket $socket = null;

    private ?EventSubSession $session = null;

    private float $lastHeardAt;

    private bool $closed = false;

    private float $openedAt;

    /**
     * @param  Closure(string): void  $log
     * @param  Closure(ChatConnection, bool): void  $onDown  called once when the connection is lost; true if it shouldn't be retried
     */
    public function __construct(
        public readonly User $streamer,
        private Connector $connector,
        private ChatSubscriptions $subscriptions,
        private ChatMessages $messages,
        private Closure $log,
        private Closure $onDown,
    ) {
        $this->lastHeardAt = $this->openedAt = microtime(true);
    }

    /**
     * Seconds since this connection was opened.
     */
    public function uptime(): float
    {
        return microtime(true) - $this->openedAt;
    }

    public function open(?string $url = null, bool $resumed = false): void
    {
        $session = new EventSubSession($resumed);

        ($this->connector)($url ?? config('services.twitch.eventsub_websocket_url'))->then(
            function (WebSocket $socket) use ($session, $resumed) {
                if ($this->closed) {
                    $socket->close();

                    return;
                }

                $socket->on('message', fn (MessageInterface $message) => $this->receive($socket, $session, (string) $message));
                $socket->on('close', function () use ($socket) {
                    // Only the current socket matters; a replaced one closing is expected.
                    if ($socket === $this->socket) {
                        $this->down('connection closed');
                    }
                });

                if (! $resumed) {
                    $this->socket = $socket;
                    $this->session = $session;
                }
            },
            fn (Throwable $e) => $this->down("couldn't connect: {$e->getMessage()}"),
        );
    }

    /**
     * Twitch sends at least a keepalive every few seconds; silence means the connection is dead.
     */
    public function isStale(): bool
    {
        $allowed = ($this->session?->keepaliveSeconds ?? 30) + 10;

        return ! $this->closed && microtime(true) - $this->lastHeardAt > $allowed;
    }

    public function close(): void
    {
        $this->closed = true;
        $socket = $this->socket;
        $this->socket = null;
        $socket?->close();
    }

    private function receive(WebSocket $socket, EventSubSession $session, string $payload): void
    {
        $this->lastHeardAt = microtime(true);
        $message = json_decode($payload, true);

        if (! is_array($message)) {
            return;
        }

        [$action, $detail] = $session->receive($message) ?? [null, null];

        match ($action) {
            EventSubSession::SUBSCRIBE => $this->subscribe($detail),
            EventSubSession::RECONNECT => $this->open($detail, resumed: true),
            EventSubSession::CHAT => $this->chat($detail),
            EventSubSession::REVOKED => $this->down("Twitch revoked the chat subscription ({$detail})", permanent: true),
            default => null,
        };

        // After a reconnect, the new connection's welcome means the old one can go.
        if ($socket !== $this->socket && $session->id !== null && $action === null && ($message['metadata']['message_type'] ?? null) === 'session_welcome') {
            $old = $this->socket;
            $this->socket = $socket;
            $this->session = $session;
            $old?->close();
            ($this->log)("{$this->streamer->display_name}: moved to a new Twitch connection");
        }
    }

    private function subscribe(string $sessionId): void
    {
        try {
            $this->subscriptions->subscribe($this->streamer, $sessionId);
            ($this->log)("{$this->streamer->display_name}: listening to chat");
        } catch (Throwable $e) {
            $this->down("couldn't subscribe to chat: {$e->getMessage()}");
        }
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function chat(array $event): void
    {
        try {
            if ($this->messages->handle($this->streamer, $event)) {
                ($this->log)("{$this->streamer->display_name}: vote from {$event['chatter_user_login']}: {$event['message']['text']}");
            }
        } catch (Throwable $e) {
            ($this->log)("{$this->streamer->display_name}: couldn't handle a chat message: {$e->getMessage()}");
        }
    }

    private function down(string $reason, bool $permanent = false): void
    {
        if ($this->closed) {
            return;
        }

        ($this->log)("{$this->streamer->display_name}: {$reason}");
        $this->close();
        ($this->onDown)($this, $permanent);
    }
}
