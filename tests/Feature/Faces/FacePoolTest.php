<?php

use App\Faces\FacePool;
use App\Models\Follower;
use App\Models\User;

beforeEach(function () {
    $this->streamer = User::factory()->create();
});

function pool(User $streamer, int $size): array
{
    return app(FacePool::class)->for($streamer, $size);
}

it('uses only followers when there are enough', function () {
    Follower::factory()->for($this->streamer, 'streamer')->count(10)->create();

    $faces = pool($this->streamer, 5);

    expect($faces)->toHaveCount(5)
        ->and(collect($faces)->pluck('providerUserId')->filter(fn ($id) => str_starts_with($id, 'stock-')))->toBeEmpty();
});

it('tops up with stock faces when there are not enough followers', function () {
    Follower::factory()->for($this->streamer, 'streamer')->count(3)->create();

    $faces = pool($this->streamer, 8);

    $ids = collect($faces)->pluck('providerUserId');
    expect($faces)->toHaveCount(8)
        ->and($ids->take(3)->every(fn ($id) => ! str_starts_with($id, 'stock-')))->toBeTrue()
        ->and($ids->skip(3)->every(fn ($id) => str_starts_with($id, 'stock-')))->toBeTrue();
});

it('uses only stock faces for a channel with no followers', function () {
    $faces = pool($this->streamer, 20);

    expect(collect($faces)->pluck('providerUserId')->unique())->toHaveCount(20);
});

it('goes round the stock faces again with unique ids when it needs more than twenty', function () {
    $faces = pool($this->streamer, 45);

    expect($faces)->toHaveCount(45)
        ->and(collect($faces)->pluck('providerUserId')->unique())->toHaveCount(45);
});

it('gives followers without an avatar a stock one but keeps their name', function () {
    Follower::factory()->for($this->streamer, 'streamer')->create([
        'display_name' => 'NoPic',
        'avatar_url' => null,
    ]);

    [$face] = pool($this->streamer, 1);

    expect($face->displayName)->toBe('NoPic')
        ->and($face->avatarUrl)->toStartWith(asset('avatars/stock/'));
});

it('only uses the streamer\'s own followers', function () {
    Follower::factory()->count(5)->create();

    $faces = pool($this->streamer, 3);

    expect(collect($faces)->every(fn ($face) => str_starts_with($face->providerUserId, 'stock-')))->toBeTrue();
});
