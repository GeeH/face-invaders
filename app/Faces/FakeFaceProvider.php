<?php

namespace App\Faces;

use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * Serves made-up faces, for tests and for local development without a real
 * Twitch channel. Select it with FACE_PROVIDER=fake.
 */
class FakeFaceProvider implements FaceProvider
{
    /**
     * @param  list<Face>|null  $faces  the faces to return, or null for generated ones
     */
    public function __construct(
        private ?array $faces = null,
        private int $count = 25,
    ) {}

    public function name(): string
    {
        return 'fake';
    }

    /**
     * @return list<Face>
     */
    public function fetchFaces(User $streamer): array
    {
        return $this->faces ??= $this->generate();
    }

    /**
     * @return list<Face>
     */
    private function generate(): array
    {
        $faces = [];

        for ($i = 1; $i <= $this->count; $i++) {
            $faces[] = new Face(
                providerUserId: "fake-{$i}",
                username: "viewer{$i}",
                displayName: "Viewer{$i}",
                avatarUrl: null,
                followedAt: CarbonImmutable::now()->subDays($i),
            );
        }

        return $faces;
    }
}
