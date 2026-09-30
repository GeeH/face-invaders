<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Balance
    |--------------------------------------------------------------------------
    |
    | Defaults for the global game balance, sent to the game at the start of
    | every run. Admins can change any of them in the balance panel
    | (/admin/balance, #27); changed values are stored in the database and
    | override these. App\Game\Balance lists each setting with its limits.
    | Speeds are in pixels per second on the 1920×1080 game area.
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

        // Wild upgrades (#65)
        'multishot_spread' => 0.14, // radians between bolts
        'blast_radius' => 110,
        'blast_radius_per_stack' => 35,
        'chain_range' => 320,
        'blade_orbit' => 150,
        'blade_spin' => 3.2, // radians per second
        'drone_fire_interval_ms' => 700,
        'nova_interval_ms' => 6000,
        'nova_min_interval_ms' => 1800,
        'nova_radius' => 380,
        'nova_knockback' => 90,
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
