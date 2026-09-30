<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        {{-- The URL is a secret: keep it out of search engines and referrers. --}}
        <meta name="robots" content="noindex, nofollow">
        <meta name="referrer" content="no-referrer">
        <title>{{ config('app.name') }}</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700;900&display=block">
        <style>
            html, body, #game { margin: 0; width: 100%; height: 100%; overflow: hidden; background: #000; }
            /* Faint CRT scanlines over the game. */
            body::after {
                content: ''; position: fixed; inset: 0; pointer-events: none;
                background: repeating-linear-gradient(to bottom, rgba(0, 0, 0, 0.25) 0 1px, transparent 1px 3px);
            }
        </style>
        @vite('resources/js/game/main.js')
    </head>
    <body>
        <div id="game" data-streamer="{{ $streamer->display_name }}" data-run-url="{{ route('play.run', $streamer->play_token) }}" data-channel="{{ $streamer->gameChannel() }}" data-vote-url="{{ route('play.votes', $streamer->play_token) }}"></div>
    </body>
</html>
