<?php

use App\Models\Follower;
use App\Models\User;

beforeEach(function () {
    $this->streamer = User::factory()->create(['display_name' => 'GeeH', 'voting_window_seconds' => 45]);
});

it('returns the streamer, balance and faces for a run', function () {
    $this->getJson(route('play.run', $this->streamer->play_token))
        ->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertJsonPath('streamer', ['name' => 'GeeH', 'voting_window_seconds' => 45])
        ->assertJsonPath('balance', [
            'starting_health' => 5,
            'enemies_per_wave' => 10,
            'extra_enemies_per_wave' => 2,
            'enemy_health' => 1,
            'bullet_damage' => 1,
            'enemy_speed' => 200,
            'extra_enemy_speed_per_wave' => 20,
            'fire_rate' => 1.5,
            'turn_speed' => 120,
            'attack_speed_upgrade' => 0.25,
            'turn_speed_upgrade' => 0.25,
            'heal_upgrade' => 3,
            'upgrade_options_per_vote' => 3,
        ])
        ->assertJsonCount(100, 'faces')
        ->assertJsonStructure(['faces' => [['id', 'name', 'avatar', 'fallback']]]);
});

it('puts followers first, then stock faces', function () {
    Follower::factory()->for($this->streamer, 'streamer')->count(2)->create();

    $faces = $this->getJson(route('play.run', $this->streamer->play_token))->json('faces');

    expect(collect($faces)->take(2)->pluck('id')->all())
        ->toEqualCanonicalizing($this->streamer->followers()->pluck('provider_user_id')->all())
        ->and($faces[2]['id'])->toStartWith('stock-')
        ->and($faces[0]['fallback'])->toContain('/avatars/stock/');
});

it('reads balance at the start of every run', function () {
    config(['game.balance.starting_health' => 9]);

    $this->getJson(route('play.run', $this->streamer->play_token))
        ->assertJsonPath('balance.starting_health', 9);
});

it('sends as many faces as configured', function () {
    config(['game.faces_per_run' => 7]);

    $this->getJson(route('play.run', $this->streamer->play_token))->assertJsonCount(7, 'faces');
});

it('returns 404 for an unknown token', function () {
    $this->getJson(route('play.run', 'not-a-real-token'))->assertNotFound();
});

it('limits how often a run can be started', function () {
    $url = route('play.run', $this->streamer->play_token);

    for ($i = 0; $i < 30; $i++) {
        $this->getJson($url)->assertOk();
    }

    $this->getJson($url)->assertTooManyRequests();
});

it('gives new streamers a 30 second voting window', function () {
    expect(User::factory()->create()->fresh()->voting_window_seconds)->toBe(30);
});

it('tells the game page where to load runs from', function () {
    $this->get($this->streamer->playUrl())
        ->assertSee('data-run-url="'.route('play.run', $this->streamer->play_token).'"', escape: false);
});
