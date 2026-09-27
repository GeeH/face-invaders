<?php

namespace App\Game;

/**
 * Global game balance. Read from config for now; the admin balance panel
 * (#27) will store these in the database instead.
 */
final readonly class GameSettings
{
    public function __construct(
        public int $startingHealth,
        public int $enemiesPerWave,
        public int $extraEnemiesPerWave,
        public int $enemyHealth,
        public int $bulletDamage,
        public int $enemySpeed,
        public int $extraEnemySpeedPerWave,
        public float $fireRate,
        public int $turnSpeed,
        public float $attackSpeedUpgrade,
        public float $turnSpeedUpgrade,
        public int $healUpgrade,
        public int $upgradeOptionsPerVote,
    ) {}

    public static function current(): self
    {
        $balance = config('game.balance');

        return new self(
            startingHealth: $balance['starting_health'],
            enemiesPerWave: $balance['enemies_per_wave'],
            extraEnemiesPerWave: $balance['extra_enemies_per_wave'],
            enemyHealth: $balance['enemy_health'],
            bulletDamage: $balance['bullet_damage'],
            enemySpeed: $balance['enemy_speed'],
            extraEnemySpeedPerWave: $balance['extra_enemy_speed_per_wave'],
            fireRate: $balance['fire_rate'],
            turnSpeed: $balance['turn_speed'],
            attackSpeedUpgrade: $balance['attack_speed_upgrade'],
            turnSpeedUpgrade: $balance['turn_speed_upgrade'],
            healUpgrade: $balance['heal_upgrade'],
            upgradeOptionsPerVote: $balance['upgrade_options_per_vote'],
        );
    }

    /**
     * @return array<string, int|float>
     */
    public function toArray(): array
    {
        return [
            'starting_health' => $this->startingHealth,
            'enemies_per_wave' => $this->enemiesPerWave,
            'extra_enemies_per_wave' => $this->extraEnemiesPerWave,
            'enemy_health' => $this->enemyHealth,
            'bullet_damage' => $this->bulletDamage,
            'enemy_speed' => $this->enemySpeed,
            'extra_enemy_speed_per_wave' => $this->extraEnemySpeedPerWave,
            'fire_rate' => $this->fireRate,
            'turn_speed' => $this->turnSpeed,
            'attack_speed_upgrade' => $this->attackSpeedUpgrade,
            'turn_speed_upgrade' => $this->turnSpeedUpgrade,
            'heal_upgrade' => $this->healUpgrade,
            'upgrade_options_per_vote' => $this->upgradeOptionsPerVote,
        ];
    }
}
