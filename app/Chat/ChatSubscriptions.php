<?php

namespace App\Chat;

use App\Models\User;
use App\Twitch\Helix;

/**
 * Asks Twitch to send a streamer's chat messages to an EventSub WebSocket
 * session, reading as the streamer themselves (only `user:read:chat`).
 */
class ChatSubscriptions
{
    public function __construct(private Helix $helix) {}

    public function subscribe(User $streamer, string $sessionId): void
    {
        $this->helix->as($streamer)->post('eventsub/subscriptions', [
            'type' => 'channel.chat.message',
            'version' => '1',
            'condition' => [
                'broadcaster_user_id' => $streamer->twitch_id,
                'user_id' => $streamer->twitch_id,
            ],
            'transport' => ['method' => 'websocket', 'session_id' => $sessionId],
        ]);
    }
}
