<?php

use App\Chat\VoteCommand;

it('reads the option number from !vote', function (string $message, ?int $choice) {
    expect(VoteCommand::parse($message))->toBe($choice);
})->with([
    'plain' => ['!vote 2', 2],
    'shouting' => ['!VOTE 3', 3],
    'extra words' => ['!vote 1 lets gooo', 1],
    'leading space' => ['  !vote 2', 2],
    'two digits' => ['!vote 10', 10],
    'no number' => ['!vote', null],
    'not a number' => ['!vote two', null],
    'too many digits' => ['!vote 123', null],
    'not at the start' => ['please !vote 2', null],
    'no space' => ['!vote2', null],
    'other command' => ['!votes 2', null],
    'chatter' => ['gg', null],
]);
