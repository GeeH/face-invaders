<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        {{-- The URL is a secret: keep it out of search engines and referrers. --}}
        <meta name="robots" content="noindex, nofollow">
        <meta name="referrer" content="no-referrer">
        <title>{{ config('app.name') }}</title>
        <style>
            html, body, #game { margin: 0; width: 100%; height: 100%; overflow: hidden; background: transparent; }
        </style>
        @vite('resources/js/game/main.js')
    </head>
    <body>
        <div id="game" data-streamer="{{ $streamer->display_name }}" data-run-url="{{ route('play.run', $streamer->play_token) }}"></div>
    </body>
</html>
