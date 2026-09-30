@php($user = auth()->user())
@php($status = $user->followers_sync_status)

<x-layout title="Dashboard">
    <h1 class="text-2xl font-bold tracking-tight">Dashboard</h1>
    <p class="mt-2 text-zinc-400">
        Welcome, {{ $user->display_name }}. Your game URL, bot controls and stats will appear here.
    </p>

    <section class="mt-8 rounded-lg border border-zinc-800 bg-zinc-900 p-6" aria-labelledby="game-url-heading">
        <h2 id="game-url-heading" class="text-lg font-semibold">Your game URL</h2>
        <p class="mt-1 text-zinc-400">
            Add this as a <strong class="text-zinc-200">Browser Source</strong> in OBS at 1920×1080. Keep it secret: anyone with it can load your game.
        </p>
        <div class="mt-4 flex flex-wrap items-center gap-3">
            <code class="min-w-0 flex-1 truncate rounded-md bg-zinc-950 px-3 py-2 text-sm text-zinc-300">{{ $user->playUrl() }}</code>
            <a href="{{ $user->playUrl() }}" target="_blank" rel="noopener" class="rounded-md border border-zinc-700 px-4 py-2 text-sm font-semibold hover:bg-zinc-800">Open</a>
        </div>
    </section>

    @php($vote = $user->openVote())
    <section
        class="mt-8 rounded-lg border border-zinc-800 bg-zinc-900 p-6"
        aria-labelledby="vote-heading"
        data-vote
        data-channel="{{ $user->gameChannel() }}"
        data-vote-id="{{ $vote?->id }}"
    >
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h2 id="vote-heading" class="text-lg font-semibold">Upgrade vote</h2>
            @if ($vote)
                <p class="text-sm text-zinc-400">
                    Wave {{ $vote->wave }} · closes in <span data-closes-at="{{ $vote->closes_at->toIso8601String() }}">{{ max(0, (int) now()->diffInSeconds($vote->closes_at, false)) }}s</span>
                </p>
            @endif
        </div>

        @if ($vote)
            <p class="mt-1 text-zinc-400">Chat is voting now. Pick one to end the vote straight away.</p>
            <ol class="mt-4 grid gap-3 sm:grid-cols-3">
                @foreach ($vote->options as $option)
                    @php($votes = $vote->tally()[$option['number']] ?? 0)
                    <li class="flex flex-col rounded-md border border-zinc-800 bg-zinc-950 p-4">
                        <p class="text-sm font-semibold text-violet-300">{{ $option['number'] }}. {{ $option['name'] }}</p>
                        <p class="mt-1 flex-1 text-sm text-zinc-400">{{ $option['description'] }}</p>
                        <p class="mt-3 text-sm tabular-nums text-zinc-300">
                            <span data-votes-for="{{ $option['number'] }}">{{ $votes }}</span> {{ Str::plural('vote', $votes) }}
                        </p>
                        <form method="POST" action="{{ route('vote.pick') }}" class="mt-3">
                            @csrf
                            <input type="hidden" name="number" value="{{ $option['number'] }}">
                            <button type="submit" class="w-full rounded-md bg-violet-600 px-3 py-2 text-sm font-semibold hover:bg-violet-500">
                                Pick {{ $option['name'] }}
                            </button>
                        </form>
                    </li>
                @endforeach
            </ol>
        @else
            <p class="mt-1 text-zinc-400">No vote is open. When a wave is cleared, the options appear here and you can pick one to end the vote early.</p>
        @endif

        <details class="mt-6 border-t border-zinc-800 pt-4">
            <summary class="cursor-pointer text-sm font-semibold">Pick from a Stream Deck</summary>
            <p class="mt-2 text-sm text-zinc-400">
                Each URL picks that option of whatever vote is open. On a Stream Deck, add a <strong class="text-zinc-200">Website</strong> action (under System), paste a URL, and tick <strong class="text-zinc-200">GET request in background</strong>. Plugins that send web requests can POST to them instead. Keep these secret, like your game URL.
            </p>
            <ul class="mt-3 space-y-2">
                @foreach (range(1, \App\Game\GameSettings::current()->get(\App\Game\Balance::UpgradeOptionsPerVote)) as $number)
                    <li class="flex items-center gap-3">
                        <span class="w-16 shrink-0 text-sm text-zinc-400">Option {{ $number }}</span>
                        <code class="min-w-0 flex-1 truncate rounded-md bg-zinc-950 px-3 py-2 text-xs text-zinc-300">{{ route('play.pick', [$user->play_token, $number]) }}</code>
                        <button type="button" data-copy="{{ route('play.pick', [$user->play_token, $number]) }}" class="rounded-md border border-zinc-700 px-3 py-1.5 text-xs font-semibold hover:bg-zinc-800">Copy</button>
                    </li>
                @endforeach
            </ul>
        </details>
    </section>

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
