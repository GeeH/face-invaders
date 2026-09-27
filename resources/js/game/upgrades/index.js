import { UpgradeRegistry } from './registry';

// Every file in ./definitions is an upgrade. Add a file and it's in the pool.
const definitions = import.meta.glob('./definitions/*.js', { eager: true, import: 'default' });

export const upgrades = new UpgradeRegistry();

Object.keys(definitions)
    .sort()
    .forEach((path) => upgrades.register(definitions[path]));

/**
 * Apply an upgrade to the running game and remember it for the run's stats.
 */
export function applyUpgrade(upgrade, context) {
    upgrade.apply(context);
    context.state.recordUpgrade(upgrade.id);
}
