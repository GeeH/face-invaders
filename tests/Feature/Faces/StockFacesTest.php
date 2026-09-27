<?php

use App\Faces\Face;
use App\Faces\StockFaces;

it('has twenty stock faces with names and avatars', function () {
    $faces = app(StockFaces::class)->all();

    expect($faces)->toHaveCount(20)->each->toBeInstanceOf(Face::class)
        ->and($faces[0])
        ->providerUserId->toBe('stock-01')
        ->username->toBe('lurker')
        ->displayName->toBe('Lurker')
        ->avatarUrl->toBe(asset('avatars/stock/01.svg'))
        ->followedAt->toBeNull();
});

it('ships an avatar file for every stock face', function () {
    foreach (array_keys(StockFaces::NAMES) as $number) {
        expect(public_path("avatars/stock/{$number}.svg"))->toBeFile();
    }
});

it('gives the same viewer the same stock avatar every time', function () {
    $stock = app(StockFaces::class);

    expect($stock->avatarFor('12345'))
        ->toBe($stock->avatarFor('12345'))
        ->toStartWith(asset('avatars/stock/'));
});
