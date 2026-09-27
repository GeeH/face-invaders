@php($user = auth()->user())
@php($status = $user->followers_sync_status)

<x-layout title="Dashboard">
    <h1 class="text-2xl font-bold tracking-tight">Dashboard</h1>
    <p class="mt-2 text-zinc-400">
        Welcome, {{ $user->display_name }}. Your game URL, bot controls and stats will appear here.
    </p>

    <section
        class="mt-8 rounded-lg border border-zinc-800 bg-zinc-900 p-6"
        aria-labelledby="followers-heading"
        data-follower-sync
        data-status-url="{{ route('followers.sync.show') }}"
        data-in-progress="{{ $status?->inProgress() ? 'true' : 'false' }}"
    >
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 id="followers-heading" class="text-lg font-semibold">Followers</h2>
                <p class="mt-1 text-zinc-400">
                    <span class="font-semibold text-zinc-100">{{ number_format($user->followers()->count()) }}</span>
                    followers ready to become enemies.
                    @if ($user->followers_synced_at)
                        Last synced <time datetime="{{ $user->followers_synced_at->toIso8601String() }}">{{ $user->followers_synced_at->diffForHumans() }}</time>.
                    @else
                        Not synced yet.
                    @endif
                </p>
            </div>

            <form method="POST" action="{{ route('followers.sync') }}">
                @csrf
                <button
                    type="submit"
                    @disabled($status?->inProgress())
                    class="inline-flex items-center gap-2 rounded-md bg-violet-600 px-4 py-2 text-sm font-semibold hover:bg-violet-500 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    @if ($status?->inProgress())
                        <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25" />
                            <path d="M4 12a8 8 0 0 1 8-8" stroke="currentColor" stroke-width="4" stroke-linecap="round" />
                        </svg>
                        Syncing…
                    @else
                        Sync now
                    @endif
                </button>
            </form>
        </div>

        @if ($status)
            <p
                role="status"
                @class([
                    'mt-4 text-sm',
                    'text-zinc-300' => $status->inProgress(),
                    'text-emerald-400' => $status === \App\Enums\FollowerSyncStatus::Succeeded,
                    'text-red-400' => in_array($status, [\App\Enums\FollowerSyncStatus::Failed, \App\Enums\FollowerSyncStatus::NeedsLogin]),
                ])
            >{{ $status->message() }}</p>
        @endif
    </section>
</x-layout>
