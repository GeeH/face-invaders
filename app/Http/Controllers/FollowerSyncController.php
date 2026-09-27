<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Lets a streamer sync their followers on demand from the dashboard.
 */
class FollowerSyncController extends Controller
{
    /**
     * The current sync status, polled by the dashboard while a sync is running.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'status' => $user->followers_sync_status?->value,
            'in_progress' => (bool) $user->followers_sync_status?->inProgress(),
            'message' => $user->followers_sync_status?->message(),
            'synced_at' => $user->followers_synced_at?->toIso8601String(),
            'follower_count' => $user->followers()->count(),
        ]);
    }

    /**
     * Queue a sync now.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->followers_sync_status?->inProgress()) {
            $user->syncFollowers();
        }

        return to_route('dashboard');
    }
}
