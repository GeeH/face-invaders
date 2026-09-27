<?php

use App\Faces\Face;
use App\Faces\FakeFaceProvider;
use App\Models\User;
use Carbon\CarbonImmutable;

it('generates the requested number of faces', function () {
    $faces = (new FakeFaceProvider(count: 3))->fetchFaces(new User);

    expect($faces)->toHaveCount(3)->each->toBeInstanceOf(Face::class)
        ->and(array_column($faces, 'providerUserId'))->toBe(['fake-1', 'fake-2', 'fake-3']);
});

it('generates faces with unique ids and follow dates', function () {
    $faces = (new FakeFaceProvider)->fetchFaces(new User);

    expect($faces)->toHaveCount(25)
        ->and(array_unique(array_column($faces, 'providerUserId')))->toHaveCount(25)
        ->and($faces[0]->followedAt)->toBeInstanceOf(CarbonImmutable::class);
});

it('returns the faces it was given', function () {
    $face = new Face('42', 'geeh', 'GeeH', 'https://example.com/geeh.png', CarbonImmutable::parse('2020-01-01'));

    expect((new FakeFaceProvider([$face]))->fetchFaces(new User))->toBe([$face]);
});

it('names itself fake', function () {
    expect((new FakeFaceProvider)->name())->toBe('fake');
});
