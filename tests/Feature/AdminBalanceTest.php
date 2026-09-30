<?php

use App\Game\Balance;
use App\Game\GameSettings;
use App\Models\BalanceOverride;
use App\Models\User;

/**
 * Every setting at its current value, as the form submits it.
 *
 * @return array<string, int|float>
 */
function balanceForm(array $changes = []): array
{
    return [...GameSettings::current()->toArray(), ...$changes];
}

it('keeps the balance panel to admins', function () {
    $this->get(route('admin.balance'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create())
        ->get(route('admin.balance'))
        ->assertForbidden();

    $this->actingAs(User::factory()->create())
        ->put(route('admin.balance.update'), balanceForm(['starting_health' => 9]))
        ->assertForbidden();

    expect(BalanceOverride::count())->toBe(0);
});

it('shows every setting with its current value and default', function () {
    GameSettings::save(['enemy_speed' => 260]);

    $response = $this->actingAs(User::factory()->admin()->create())->get(route('admin.balance'))->assertOk();

    foreach (Balance::cases() as $setting) {
        $response->assertSee($setting->spec()['label']);
    }

    $response->assertSee('name="enemy_speed"', escape: false)
        ->assertSee('value="260"', escape: false)
        ->assertSee('Default 200.')
        ->assertSee('changed');
});

it('links admins to the panel from every page', function () {
    $this->actingAs(User::factory()->admin()->create())->get(route('dashboard'))->assertSee(route('admin.balance'));
    $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertDontSee(route('admin.balance'));
});

it('saves changes, which the next run picks up', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.balance.update'), balanceForm(['starting_health' => 8, 'nova_interval_ms' => 3000]))
        ->assertRedirect(route('admin.balance'))
        ->assertSessionHas('status');

    $settings = GameSettings::current();

    expect($settings->get(Balance::StartingHealth))->toBe(8)
        ->and($settings->get(Balance::NovaIntervalMs))->toBe(3000)
        ->and(BalanceOverride::pluck('key')->sort()->values()->all())->toBe(['nova_interval_ms', 'starting_health']);
});

it('only stores settings that differ from their default', function () {
    GameSettings::save(['starting_health' => 8]);
    GameSettings::save(['starting_health' => 5]);

    expect(BalanceOverride::count())->toBe(0);
});

it('rejects values outside each setting\'s limits and saves nothing', function (string $key, mixed $value) {
    $this->actingAs(User::factory()->admin()->create())
        ->from(route('admin.balance'))
        ->put(route('admin.balance.update'), balanceForm([$key => $value, 'enemy_speed' => 300]))
        ->assertRedirect(route('admin.balance'))
        ->assertSessionHasErrors($key);

    expect(BalanceOverride::count())->toBe(0);
})->with([
    'no health' => ['starting_health', 0],
    'a fraction of a life' => ['starting_health', 2.5],
    'too many options' => ['upgrade_options_per_vote', 9],
    'not a number' => ['fire_rate', 'lots'],
    'missing' => ['enemy_health', ''],
]);

it('resets every setting to its default', function () {
    GameSettings::save(['starting_health' => 8, 'blade_spin' => 6]);

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('admin.balance.reset'))
        ->assertRedirect(route('admin.balance'));

    expect(BalanceOverride::count())->toBe(0)
        ->and(GameSettings::current()->get(Balance::StartingHealth))->toBe(5);
});

it('has a default in config for every setting', function () {
    foreach (Balance::cases() as $setting) {
        expect(config("game.balance.{$setting->value}"))->not->toBeNull("{$setting->value} has no default");
    }

    expect(array_keys(config('game.balance')))->toEqualCanonicalizing(array_column(Balance::cases(), 'value'));
});

it('grants and revokes admin from the command line', function () {
    $user = User::factory()->create(['twitch_login' => 'geeh', 'display_name' => 'GeeH']);

    $this->artisan('admin:grant', ['login' => 'geeh'])
        ->expectsOutput('GeeH can now edit the game balance.')
        ->assertSuccessful();
    expect($user->refresh()->is_admin)->toBeTrue();

    $this->artisan('admin:grant', ['login' => 'geeh', '--revoke' => true])->assertSuccessful();
    expect($user->refresh()->is_admin)->toBeFalse();

    $this->artisan('admin:grant', ['login' => 'nobody'])->assertFailed();
});
