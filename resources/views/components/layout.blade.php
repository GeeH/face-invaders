<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ isset($title) ? "$title · " : '' }}{{ config('app.name') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-zinc-950 text-zinc-100 antialiased">
        <header class="mx-auto flex max-w-4xl items-center justify-between px-4 py-6">
            <a href="{{ route('home') }}" class="text-lg font-semibold tracking-tight">{{ config('app.name') }}</a>

            @auth
                <div class="flex items-center gap-3">
                    @if (auth()->user()->avatar_url)
                        <img src="{{ auth()->user()->avatar_url }}" alt="" class="size-8 rounded-full">
                    @endif
                    <span class="text-sm text-zinc-300">{{ auth()->user()->display_name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm text-zinc-400 underline-offset-4 hover:text-zinc-100 hover:underline">Log out</button>
                    </form>
                </div>
            @endauth
        </header>

        <main class="mx-auto max-w-4xl px-4 pb-16">
            @if (session('error'))
                <p role="alert" class="mb-6 rounded-md border border-red-900 bg-red-950 px-4 py-3 text-sm text-red-200">{{ session('error') }}</p>
            @endif

            {{ $slot }}
        </main>
    </body>
</html>
