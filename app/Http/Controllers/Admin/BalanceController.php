<?php

namespace App\Http\Controllers\Admin;

use App\Game\Balance;
use App\Game\GameSettings;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The global game balance panel. Changes apply at the start of the next run.
 */
class BalanceController extends Controller
{
    public function edit(): View
    {
        return view('admin.balance', ['settings' => GameSettings::current()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $values = $request->validate(
            collect(Balance::cases())->mapWithKeys(fn (Balance $setting) => [$setting->value => $setting->rules()])->all(),
            attributes: collect(Balance::cases())->mapWithKeys(fn (Balance $setting) => [$setting->value => $setting->spec()['label']])->all(),
        );

        GameSettings::save($values);

        return to_route('admin.balance')->with('status', 'Balance saved. It applies from the next run.');
    }

    public function destroy(): RedirectResponse
    {
        GameSettings::reset();

        return to_route('admin.balance')->with('status', 'Every setting is back to its default.');
    }
}
