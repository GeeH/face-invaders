<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'twitch' => [
        'client_id' => env('TWITCH_CLIENT_ID'),
        'client_secret' => env('TWITCH_CLIENT_SECRET'),
        'redirect' => env('TWITCH_REDIRECT_URI'),

        // Requested on login. Adding one means streamers log in again to grant it.
        'scopes' => [
            'moderator:read:followers', // read the follower list for enemy faces
            'user:read:chat', // read the streamer's own chat for !vote (#21); read-only, never posts
        ],

        // Overridable to point at the Twitch CLI's mock EventSub server when testing locally.
        'helix_url' => env('TWITCH_HELIX_URL', 'https://api.twitch.tv/helix'),
        'eventsub_websocket_url' => env('TWITCH_EVENTSUB_WEBSOCKET_URL', 'wss://eventsub.wss.twitch.tv/ws?keepalive_timeout_seconds=30'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
