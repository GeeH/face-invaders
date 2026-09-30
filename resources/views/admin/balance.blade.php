<x-layout title="Game balance">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Game balance</h1>
            <p class="mt-2 text-zinc-400">
                Global settings for every streamer's game. Changes apply at the start of the next run: after a death, or a refresh of the game.
            </p>
        </div>

        <form method="POST" action="{{ route('admin.balance.reset') }}">
            @csrf
            @method('DELETE')
            <button type="submit" class="rounded-md border border-zinc-700 px-4 py-2 text-sm font-semibold hover:bg-zinc-800">Reset all to defaults</button>
        </form>
    </div>

    @if ($errors->any())
        <p role="alert" class="mt-6 rounded-md border border-red-900 bg-red-950 px-4 py-3 text-sm text-red-200">
            Nothing was saved. Fix the {{ Str::plural('setting', $errors->count()) }} marked below.
        </p>
    @endif

    <form method="POST" action="{{ route('admin.balance.update') }}" class="mt-8 space-y-8">
        @csrf
        @method('PUT')

        @foreach (\App\Game\Balance::grouped() as $group => $group_settings)
            <section class="rounded-lg border border-zinc-800 bg-zinc-900 p-6" aria-labelledby="group-{{ Str::slug($group) }}">
                <h2 id="group-{{ Str::slug($group) }}" class="text-lg font-semibold">{{ $group }}</h2>

                <div class="mt-4 grid gap-x-6 gap-y-5 sm:grid-cols-2">
                    @foreach ($group_settings as $setting)
                        @php($spec = $setting->spec())
                        @php($changed = $settings->isChanged($setting))
                        <div>
                            <label for="{{ $setting->value }}" class="flex items-center gap-2 text-sm font-semibold">
                                {{ $spec['label'] }}
                                @if ($changed)
                                    <span class="rounded bg-violet-500/20 px-1.5 py-0.5 text-xs font-medium text-violet-300">changed</span>
                                @endif
                            </label>
                            <input
                                type="number"
                                id="{{ $setting->value }}"
                                name="{{ $setting->value }}"
                                value="{{ old($setting->value, $settings->get($setting)) }}"
                                min="{{ $spec['min'] }}"
                                max="{{ $spec['max'] }}"
                                step="{{ $spec['integer'] ? 1 : 'any' }}"
                                required
                                aria-describedby="{{ $setting->value }}-help"
                                @class([
                                    'mt-1 w-full rounded-md border bg-zinc-950 px-3 py-2 text-sm tabular-nums',
                                    'border-red-700' => $errors->has($setting->value),
                                    'border-zinc-700' => ! $errors->has($setting->value),
                                ])
                            >
                            <p id="{{ $setting->value }}-help" class="mt-1 text-xs text-zinc-400">
                                {{ rtrim($spec['help'], '.') }}. Default {{ $setting->default() }}.
                            </p>
                            @error($setting->value)
                                <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach

        <div class="flex justify-end">
            <button type="submit" class="rounded-md bg-violet-600 px-5 py-2 text-sm font-semibold hover:bg-violet-500">Save balance</button>
        </div>
    </form>
</x-layout>
