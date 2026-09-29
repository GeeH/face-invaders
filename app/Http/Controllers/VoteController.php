<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\VoteSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The game opens chat's upgrade vote when a wave is cleared. The result
 * comes back over Reverb (docs/broadcasting.md).
 */
class VoteController extends Controller
{
    public function store(Request $request, string $token): JsonResponse
    {
        $streamer = User::where('play_token', $token)->firstOrFail();

        $validated = $request->validate([
            'wave' => ['required', 'integer', 'min:1'],
            // Chat types `!vote N`, so keep N to a single digit.
            'options' => ['required', 'array', 'min:1', 'max:9'],
            'options.*.id' => ['required', 'string', 'max:64', 'distinct'],
            'options.*.name' => ['required', 'string', 'max:64'],
            'options.*.description' => ['required', 'string', 'max:255'],
        ]);

        $session = VoteSession::open($streamer, $validated['wave'], $validated['options']);

        return response()->json([
            'id' => $session->id,
            'options' => $session->options,
            'closes_at' => $session->closes_at->toIso8601String(),
        ], 201);
    }
}
