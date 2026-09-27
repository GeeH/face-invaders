<?php

use App\Models\User;

it('gives every streamer a game URL', function () {
    [$a, $b] = User::factory()->count(2)->create();

    expect($a->play_token)->toHaveLength(40)
        ->and($a->play_token)->not->toBe($b->play_token)
        ->and($a->playUrl())->toBe(url("/play/{$a->play_token}"));
});

it('serves the game at the streamer\'s URL without logging in', function () {
    $streamer = User::factory()->create(['display_name' => 'GeeH']);

    $this->get($streamer->playUrl())
        ->assertOk()
        ->assertSee('data-streamer="GeeH"', escape: false)
        ->assertSee('noindex', escape: false);

    $this->assertGuest();
});

it('returns 404 for an unknown game URL', function () {
    $this->get(route('play', 'not-a-real-token'))->assertNotFound();
});

it('stops the old URL working when the token is regenerated', function () {
    $streamer = User::factory()->create();
    $oldUrl = $streamer->playUrl();

    $streamer->regeneratePlayToken();

    $this->get($oldUrl)->assertNotFound();
    $this->get($streamer->playUrl())->assertOk();
});

it('never exposes the token when a streamer is serialised', function () {
    expect(User::factory()->create()->toArray())->not->toHaveKey('play_token');
});

it('shows the game URL on the dashboard', function () {
    $streamer = User::factory()->create();

    $this->actingAs($streamer)
        ->get(route('dashboard'))
        ->assertSee('Your game URL')
        ->assertSee($streamer->playUrl());
});
