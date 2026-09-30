<?php

namespace App\Chat;

/**
 * The EventSub WebSocket protocol for one connection, without the socket:
 * feed it each message from Twitch and it says what to do next. Kept apart
 * from the networking so it can be tested.
 *
 * @see https://dev.twitch.tv/docs/eventsub/handling-websocket-events/
 */
class EventSubSession
{
    public const SUBSCRIBE = 'subscribe';

    public const RECONNECT = 'reconnect';

    public const CHAT = 'chat';

    public const REVOKED = 'revoked';

    /**
     * Twitch can deliver a message twice; remember this many ids to skip repeats.
     */
    private const REMEMBERED_IDS = 200;

    public ?string $id = null;

    /**
     * Seconds Twitch promises to send something within, or the connection is dead.
     */
    public int $keepaliveSeconds = 30;

    /** @var array<string, true> */
    private array $seen = [];

    /**
     * @param  bool  $resumed  true when Twitch moved us to this connection with a
     *                         reconnect message: subscriptions carry over, so don't subscribe again
     */
    public function __construct(private bool $resumed = false) {}

    /**
     * @param  array<string, mixed>  $message  a decoded message from Twitch
     * @return array{0: string, 1: mixed}|null what to do, or null for nothing
     */
    public function receive(array $message): ?array
    {
        $type = $message['metadata']['message_type'] ?? null;
        $messageId = $message['metadata']['message_id'] ?? null;

        if ($messageId !== null) {
            if (isset($this->seen[$messageId])) {
                return null;
            }

            $this->seen[$messageId] = true;
            if (count($this->seen) > self::REMEMBERED_IDS) {
                array_shift($this->seen);
            }
        }

        return match ($type) {
            'session_welcome' => $this->welcome($message['payload']['session']),
            'session_reconnect' => [self::RECONNECT, $message['payload']['session']['reconnect_url']],
            'notification' => ($message['metadata']['subscription_type'] ?? null) === 'channel.chat.message'
                ? [self::CHAT, $message['payload']['event']]
                : null,
            'revocation' => [self::REVOKED, $message['payload']['subscription']['status'] ?? 'revoked'],
            default => null, // session_keepalive, or anything new
        };
    }

    /**
     * @param  array{id: string, keepalive_timeout_seconds?: int}  $session
     * @return array{0: string, 1: string}|null
     */
    private function welcome(array $session): ?array
    {
        $this->id = $session['id'];
        $this->keepaliveSeconds = (int) ($session['keepalive_timeout_seconds'] ?? $this->keepaliveSeconds);

        return $this->resumed ? null : [self::SUBSCRIBE, $this->id];
    }
}
