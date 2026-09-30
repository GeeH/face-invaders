<?php

namespace App\Chat;

use App\Models\User;

/**
 * What Face Invaders does with a message in a streamer's chat: counts
 * `!vote N` toward their open vote, and notes that the chatter was active
 * (for the active-viewer queue, #29). It never replies.
 */
class ChatMessages
{
    /**
     * Don't write last_active_at more often than this for one chatter.
     */
    private const ACTIVITY_RESOLUTION_SECONDS = 60;

    /**
     * @param  array{chatter_user_id: string, message: array{text: string}}  $event  a channel.chat.message event
     * @return bool whether the message was counted as a vote
     */
    public function handle(User $streamer, array $event): bool
    {
        $chatter = $event['chatter_user_id'];

        $streamer->followers()
            ->where('provider_user_id', $chatter)
            ->where(fn ($query) => $query->whereNull('last_active_at')
                ->orWhere('last_active_at', '<', now()->subSeconds(self::ACTIVITY_RESOLUTION_SECONDS)))
            ->update(['last_active_at' => now()]);

        $choice = VoteCommand::parse($event['message']['text'] ?? '');

        if ($choice === null) {
            return false;
        }

        return (bool) $streamer->voteSessions()->open()->latest('id')->first()?->vote($chatter, $choice);
    }
}
