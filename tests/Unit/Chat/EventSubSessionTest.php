<?php

use App\Chat\EventSubSession;

function eventSubMessage(string $type, array $payload = [], array $metadata = []): array
{
    static $id = 0;

    return [
        'metadata' => ['message_id' => 'msg-'.++$id, 'message_type' => $type, ...$metadata],
        'payload' => $payload,
    ];
}

it('subscribes when Twitch welcomes a new connection', function () {
    $session = new EventSubSession;

    expect($session->receive(eventSubMessage('session_welcome', ['session' => ['id' => 'abc', 'keepalive_timeout_seconds' => 10]])))
        ->toBe([EventSubSession::SUBSCRIBE, 'abc'])
        ->and($session->id)->toBe('abc')
        ->and($session->keepaliveSeconds)->toBe(10);
});

it('does not subscribe again on a connection Twitch moved us to', function () {
    $session = new EventSubSession(resumed: true);

    expect($session->receive(eventSubMessage('session_welcome', ['session' => ['id' => 'abc']])))->toBeNull()
        ->and($session->id)->toBe('abc');
});

it('follows Twitch to a new connection', function () {
    expect((new EventSubSession)->receive(eventSubMessage('session_reconnect', ['session' => ['reconnect_url' => 'wss://elsewhere']])))
        ->toBe([EventSubSession::RECONNECT, 'wss://elsewhere']);
});

it('passes on chat messages and ignores other notifications', function () {
    $session = new EventSubSession;
    $event = ['chatter_user_id' => '1', 'message' => ['text' => '!vote 1']];

    expect($session->receive(eventSubMessage('notification', ['event' => $event], ['subscription_type' => 'channel.chat.message'])))
        ->toBe([EventSubSession::CHAT, $event])
        ->and($session->receive(eventSubMessage('notification', ['event' => []], ['subscription_type' => 'channel.follow'])))->toBeNull();
});

it('skips a message Twitch delivers twice', function () {
    $session = new EventSubSession;
    $message = eventSubMessage('notification', ['event' => ['chatter_user_id' => '1']], ['subscription_type' => 'channel.chat.message']);

    expect($session->receive($message))->not->toBeNull()
        ->and($session->receive($message))->toBeNull();
});

it('reports a revoked subscription', function () {
    expect((new EventSubSession)->receive(eventSubMessage('revocation', ['subscription' => ['status' => 'authorization_revoked']])))
        ->toBe([EventSubSession::REVOKED, 'authorization_revoked']);
});

it('does nothing on keepalives', function () {
    expect((new EventSubSession)->receive(eventSubMessage('session_keepalive')))->toBeNull();
});
