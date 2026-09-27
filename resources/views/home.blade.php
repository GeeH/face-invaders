<x-layout>
    <section class="py-16 text-center">
        <h1 class="text-4xl font-bold tracking-tight sm:text-5xl">Face Invaders</h1>
        <p class="mx-auto mt-4 max-w-xl text-lg text-zinc-400">
            A stream overlay game your chat plays for you. Your followers' faces fly in as enemies,
            and chat votes on upgrades between waves.
        </p>

        <div class="mt-10">
            @auth
                <a href="{{ route('dashboard') }}" class="inline-flex items-center rounded-md bg-violet-600 px-5 py-3 font-semibold hover:bg-violet-500">
                    Go to your dashboard
                </a>
            @else
                <a href="{{ route('login') }}" class="inline-flex items-center rounded-md bg-violet-600 px-5 py-3 font-semibold hover:bg-violet-500">
                    Log in with Twitch
                </a>
            @endauth
        </div>
    </section>
</x-layout>
