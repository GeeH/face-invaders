<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Balance
    |--------------------------------------------------------------------------
    |
    | Global game balance, sent to the game at the start of every run. These
    | are the defaults until the admin balance panel (#27) stores them in the
    | database. Speeds are in pixels per second on the 1920×1080 game area.
    |
    | Tune freely while developing: changes apply on the next run (after a
    | death, or a refresh of the game page), no rebuild needed.
    |
    */

    'balance' => [
        'starting_health' => 5,
        'enemies_per_wave' => 10,
        'extra_enemies_per_wave' => 2,
        'enemy_health' => 1,
        'bullet_damage' => 1,
        'enemy_speed' => 200,
        'extra_enemy_speed_per_wave' => 20,
        'fire_rate' => 1.5, // shots per second
        'turn_speed' => 120, // degrees per second the ship can turn; slower = more jeopardy
        'attack_speed_upgrade' => 0.25, // +25% fire rate per upgrade
        'turn_speed_upgrade' => 0.25, // +25% turn speed per upgrade
        'heal_upgrade' => 3,
        'upgrade_options_per_vote' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Faces Per Run
    |--------------------------------------------------------------------------
    |
    | How many faces the game gets at the start of a run. Enemies cycle through
    | them, so this only needs to be big enough to avoid obvious repeats.
    |
    */

    'faces_per_run' => 100,

];
