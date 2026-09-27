<?php

namespace App\Faces;

use Illuminate\Support\Str;

/**
 * Bundled alien avatars with generic names, used when a channel doesn't have
 * enough followers for every enemy, or a follower has no avatar.
 */
class StockFaces
{
    /**
     * Avatar file number => the name shown under the enemy.
     */
    public const NAMES = [
        '01' => 'Lurker',
        '02' => 'Chat Goblin',
        '03' => 'Backseater',
        '04' => 'Emote Spammer',
        '05' => 'Clip Hunter',
        '06' => 'Night Owl',
        '07' => 'Pog Champ',
        '08' => 'Hype Train',
        '09' => 'First Timer',
        '10' => 'Mod Wannabe',
        '11' => 'Raid Rider',
        '12' => 'Sub Goblin',
        '13' => 'Copypasta',
        '14' => 'KEKW Enjoyer',
        '15' => 'Brb Viewer',
        '16' => 'Speedrunner',
        '17' => 'Snack Break',
        '18' => 'Tab Hoarder',
        '19' => 'Caps Lock',
        '20' => 'GG Sayer',
    ];

    /**
     * Every stock face.
     *
     * @return list<Face>
     */
    public function all(): array
    {
        return array_map($this->face(...), array_keys(self::NAMES));
    }

    /**
     * A stock avatar URL for a viewer who has none, picked from their ID so
     * the same viewer always gets the same alien.
     */
    public function avatarFor(string $seed): string
    {
        $numbers = array_keys(self::NAMES);

        return $this->avatarUrl($numbers[crc32($seed) % count($numbers)]);
    }

    private function face(string $number): Face
    {
        return new Face(
            providerUserId: "stock-{$number}",
            username: Str::slug(self::NAMES[$number]),
            displayName: self::NAMES[$number],
            avatarUrl: $this->avatarUrl($number),
            followedAt: null,
        );
    }

    private function avatarUrl(string $number): string
    {
        return asset("avatars/stock/{$number}.svg");
    }
}
