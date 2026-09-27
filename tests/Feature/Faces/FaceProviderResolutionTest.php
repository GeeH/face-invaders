<?php

use App\Faces\FaceProvider;
use App\Faces\FakeFaceProvider;

it('resolves the configured face provider', function () {
    config(['faces.provider' => 'fake']);

    expect(app(FaceProvider::class))->toBeInstanceOf(FakeFaceProvider::class);
});

it('rejects an unknown face provider', function () {
    config(['faces.provider' => 'myspace']);

    app(FaceProvider::class);
})->throws(InvalidArgumentException::class, 'Unknown face provider [myspace].');

it('lets tests swap in their own provider', function () {
    $fake = new FakeFaceProvider(count: 1);
    app()->instance(FaceProvider::class, $fake);

    expect(app(FaceProvider::class))->toBe($fake);
});
