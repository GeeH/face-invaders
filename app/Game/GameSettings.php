<?php

namespace App\Game;

use App\Models\BalanceOverride;

/**
 * Global game balance for a run: each setting's default from config, unless
 * an admin has changed it in the balance panel (#27). Read at the start of
 * every run, so changes apply without a redeploy or reloading OBS.
 */
final readonly class GameSettings
{
    /**
     * @param  array<string, int|float>  $values  keyed by Balance value
     */
    private function __construct(private array $values) {}

    public static function current(): self
    {
        $overrides = BalanceOverride::pluck('value', 'key');

        return new self(collect(Balance::cases())->mapWithKeys(fn (Balance $setting) => [
            $setting->value => $overrides->has($setting->value) ? $setting->cast($overrides[$setting->value]) : $setting->default(),
        ])->all());
    }

    /**
     * Save the admin's values. Only settings that differ from their default
     * are stored, so a changed default in config still reaches the rest.
     *
     * @param  array<string, int|float|string>  $values  keyed by Balance value
     */
    public static function save(array $values): void
    {
        foreach (Balance::cases() as $setting) {
            if (! array_key_exists($setting->value, $values)) {
                continue;
            }

            $value = $setting->cast($values[$setting->value]);

            if ($value == $setting->default()) {
                BalanceOverride::whereKey($setting->value)->delete();
            } else {
                BalanceOverride::updateOrCreate(['key' => $setting->value], ['value' => $value]);
            }
        }
    }

    /**
     * Put every setting back to its default.
     */
    public static function reset(): void
    {
        BalanceOverride::query()->delete();
    }

    public function get(Balance $setting): int|float
    {
        return $this->values[$setting->value];
    }

    public function isChanged(Balance $setting): bool
    {
        return $this->get($setting) != $setting->default();
    }

    /**
     * @return array<string, int|float>
     */
    public function toArray(): array
    {
        return $this->values;
    }
}
