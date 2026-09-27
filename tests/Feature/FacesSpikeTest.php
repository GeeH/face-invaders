<?php

use App\Models\Follower;
use App\Models\User;

it('shows the streamer\'s faces to Phaser, topped up with stock ones', function () {
    $streamer = User::factory()->create();
    Follower::factory()->for($streamer, 'streamer')->create(['display_name' => 'RealFollower']);

    $response = $this->actingAs($streamer)->get(route('spike.faces'))->assertOk();

    $faces = $response->viewData('faces');
    expect($faces)->toHaveCount(40)
        ->and($faces[0]['name'])->toBe('RealFollower')
        ->and($faces[1]['avatar'])->toContain('/avatars/stock/');
});

it('requires login', function () {
    $this->get(route('spike.faces'))->assertRedirect(route('login'));
});
