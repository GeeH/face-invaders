<x-layout title="Dashboard">
    <h1 class="text-2xl font-bold tracking-tight">Dashboard</h1>
    <p class="mt-2 text-zinc-400">
        Welcome, {{ auth()->user()->display_name }}. Your game URL, bot controls and stats will appear here.
    </p>
</x-layout>
