<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Faces spike · {{ config('app.name') }}</title>
        <style>html, body, #game { margin: 0; height: 100%; background: #09090b; }</style>
        <script id="faces" type="application/json">@json($faces)</script>
        @vite('resources/js/spike/faces.js')
    </head>
    <body>
        <div id="game"></div>
    </body>
</html>
