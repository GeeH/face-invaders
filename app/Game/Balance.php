<?php

namespace App\Game;

/**
 * Every global balance setting: what the admin panel shows and accepts.
 * Defaults live in config/game.php; admins' changes are stored as
 * overrides (BalanceOverride) and apply on the next run.
 *
 * To add a setting: add a case here, its default in config/game.php, and
 * read it from the run's balance in the game.
 */
enum Balance: string
{
    // Ship
    case StartingHealth = 'starting_health';
    case FireRate = 'fire_rate';
    case TurnSpeed = 'turn_speed';
    case BulletDamage = 'bullet_damage';

    // Waves
    case EnemiesPerWave = 'enemies_per_wave';
    case ExtraEnemiesPerWave = 'extra_enemies_per_wave';
    case EnemyHealth = 'enemy_health';
    case EnemySpeed = 'enemy_speed';
    case ExtraEnemySpeedPerWave = 'extra_enemy_speed_per_wave';

    // Upgrades
    case UpgradeOptionsPerVote = 'upgrade_options_per_vote';
    case AttackSpeedUpgrade = 'attack_speed_upgrade';
    case TurnSpeedUpgrade = 'turn_speed_upgrade';
    case HealUpgrade = 'heal_upgrade';

    // Wild upgrades (#65)
    case MultishotSpread = 'multishot_spread';
    case BlastRadius = 'blast_radius';
    case BlastRadiusPerStack = 'blast_radius_per_stack';
    case ChainRange = 'chain_range';
    case BladeOrbit = 'blade_orbit';
    case BladeSpin = 'blade_spin';
    case DroneFireIntervalMs = 'drone_fire_interval_ms';
    case NovaIntervalMs = 'nova_interval_ms';
    case NovaMinIntervalMs = 'nova_min_interval_ms';
    case NovaRadius = 'nova_radius';
    case NovaKnockback = 'nova_knockback';

    /**
     * @return array{label: string, help: string, group: string, integer: bool, min: int|float, max: int|float}
     */
    public function spec(): array
    {
        [$group, $label, $help, $integer, $min, $max] = match ($this) {
            self::StartingHealth => ['Ship', 'Starting health', 'Lives at the start of a run', true, 1, 50],
            self::FireRate => ['Ship', 'Fire rate', 'Shots per second', false, 0.1, 20],
            self::TurnSpeed => ['Ship', 'Turn speed', 'Degrees per second the ship can turn. Slower means more jeopardy.', true, 10, 2000],
            self::BulletDamage => ['Ship', 'Bullet damage', 'Damage per hit. Blades, drones, blasts and lightning do the same; nova does double.', true, 1, 100],

            self::EnemiesPerWave => ['Waves', 'Enemies in wave 1', 'How many rocks the first wave sends', true, 1, 500],
            self::ExtraEnemiesPerWave => ['Waves', 'Extra enemies per wave', 'Added to each wave after the first', true, 0, 100],
            self::EnemyHealth => ['Waves', 'Enemy health', 'Hits a rock takes at 1 damage each', true, 1, 100],
            self::EnemySpeed => ['Waves', 'Enemy speed in wave 1', 'Pixels per second on the 1920×1080 screen', true, 10, 2000],
            self::ExtraEnemySpeedPerWave => ['Waves', 'Extra speed per wave', 'Pixels per second added each wave', true, 0, 500],

            self::UpgradeOptionsPerVote => ['Upgrades', 'Options per vote', 'Upgrades chat chooses between', true, 2, 5],
            self::AttackSpeedUpgrade => ['Upgrades', 'Attack speed upgrade', 'Fraction faster per pick (0.25 = 25%)', false, 0.01, 5],
            self::TurnSpeedUpgrade => ['Upgrades', 'Turn speed upgrade', 'Fraction faster per pick (0.25 = 25%)', false, 0.01, 5],
            self::HealUpgrade => ['Upgrades', 'Heal amount', 'Lives restored by Heal', true, 1, 50],

            self::MultishotSpread => ['Wild upgrades', 'Multishot spread', 'Radians between bolts in the fan', false, 0.01, 1],
            self::BlastRadius => ['Wild upgrades', 'Blast radius', 'Explosive rounds, first stack, in pixels', true, 10, 1000],
            self::BlastRadiusPerStack => ['Wild upgrades', 'Blast radius per stack', 'Pixels added by each extra stack', true, 0, 500],
            self::ChainRange => ['Wild upgrades', 'Chain lightning range', 'How far lightning can jump, in pixels', true, 10, 2000],
            self::BladeOrbit => ['Wild upgrades', 'Blade orbit', 'Distance of the blades from the ship, in pixels', true, 40, 600],
            self::BladeSpin => ['Wild upgrades', 'Blade spin', 'Radians per second', false, 0.1, 20],
            self::DroneFireIntervalMs => ['Wild upgrades', 'Drone fire interval', 'Milliseconds between each drone\'s shots', true, 50, 10000],
            self::NovaIntervalMs => ['Wild upgrades', 'Nova interval', 'Milliseconds between pulses at one stack; each stack takes a quarter off', true, 200, 60000],
            self::NovaMinIntervalMs => ['Wild upgrades', 'Nova fastest interval', 'Stacking never pulses faster than this, in milliseconds', true, 100, 60000],
            self::NovaRadius => ['Wild upgrades', 'Nova radius', 'Pixels at one stack; each stack adds 40', true, 50, 2000],
            self::NovaKnockback => ['Wild upgrades', 'Nova knockback', 'Pixels a pulse shoves rocks back', true, 0, 1000],
        };

        return compact('group', 'label', 'help', 'integer', 'min', 'max');
    }

    public function default(): int|float
    {
        return $this->cast(config("game.balance.{$this->value}"));
    }

    public function cast(mixed $value): int|float
    {
        return $this->spec()['integer'] ? (int) $value : (float) $value;
    }

    /**
     * @return list<string>
     */
    public function rules(): array
    {
        $spec = $this->spec();

        return ['required', $spec['integer'] ? 'integer' : 'numeric', "min:{$spec['min']}", "max:{$spec['max']}"];
    }

    /**
     * The settings in panel order, grouped under their headings.
     *
     * @return array<string, list<self>>
     */
    public static function grouped(): array
    {
        return collect(self::cases())->groupBy(fn (self $setting) => $setting->spec()['group'])->map->all()->all();
    }
}
