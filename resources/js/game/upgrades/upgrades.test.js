import { describe, expect, it } from 'vitest';
import { RunState } from '../state';
import { applyUpgrade, upgrades } from './index';
import { UpgradeRegistry } from './registry';

const balance = { attack_speed_upgrade: 0.25, turn_speed_upgrade: 0.5, heal_upgrade: 3 };

function context() {
    return {
        player: { fireRate: 2, turnSpeed: 100 },
        state: new RunState({ startingHealth: 5 }),
        balance,
    };
}

describe('the v1 pool', () => {
    it('registers every upgrade in the definitions folder', () => {
        expect(upgrades.all().map((upgrade) => upgrade.id)).toEqual(['attack-speed', 'heal', 'max-health', 'turn-speed']);
    });

    it('describes each upgrade using the balance', () => {
        expect(upgrades.all().map((upgrade) => upgrade.describe(balance))).toEqual([
            'Fire 25% faster',
            'Repair 3 lives',
            'One extra life',
            'Turn 50% faster',
        ]);
    });
});

describe('upgrades', () => {
    it('attack speed makes the ship fire faster', () => {
        const ctx = context();
        applyUpgrade(upgrades.get('attack-speed'), ctx);

        expect(ctx.player.fireRate).toBe(2.5);
    });

    it('turn speed makes the ship turn faster', () => {
        const ctx = context();
        applyUpgrade(upgrades.get('turn-speed'), ctx);

        expect(ctx.player.turnSpeed).toBe(150);
    });

    it('stacks when picked again', () => {
        const ctx = context();
        applyUpgrade(upgrades.get('attack-speed'), ctx);
        applyUpgrade(upgrades.get('attack-speed'), ctx);

        expect(ctx.player.fireRate).toBeCloseTo(3.125);
    });

    it('heal restores lives up to the max', () => {
        const ctx = context();
        ctx.state.takeDamage(4);
        applyUpgrade(upgrades.get('heal'), ctx);

        expect(ctx.state.health).toBe(4);

        applyUpgrade(upgrades.get('heal'), ctx);
        expect(ctx.state.health).toBe(5);
    });

    it('max health adds a life', () => {
        const ctx = context();
        applyUpgrade(upgrades.get('max-health'), ctx);

        expect(ctx.state).toMatchObject({ health: 6, maxHealth: 6 });
    });

    it('records each upgrade in the run stats', () => {
        const ctx = context();
        applyUpgrade(upgrades.get('heal'), ctx);
        applyUpgrade(upgrades.get('max-health'), ctx);

        expect(ctx.state.stats().upgrades).toEqual(['heal', 'max-health']);
    });
});

describe('UpgradeRegistry', () => {
    const upgrade = (id) => ({ id, name: id, describe: () => '', apply: () => {} });

    it('draws different upgrades for a vote', () => {
        const registry = new UpgradeRegistry();
        ['a', 'b', 'c', 'd', 'e'].forEach((id) => registry.register(upgrade(id)));

        for (let i = 0; i < 50; i++) {
            const ids = registry.draw(3).map((u) => u.id);
            expect(new Set(ids).size).toBe(3);
        }
    });

    it('offers the whole pool when asked for more than it has', () => {
        const registry = new UpgradeRegistry().register(upgrade('a')).register(upgrade('b'));

        expect(registry.draw(5)).toHaveLength(2);
    });

    it('gives every upgrade a chance', () => {
        const registry = new UpgradeRegistry();
        ['a', 'b', 'c', 'd'].forEach((id) => registry.register(upgrade(id)));

        const seen = new Set();
        for (let i = 0; i < 200; i++) seen.add(registry.draw(1)[0].id);

        expect(seen).toEqual(new Set(['a', 'b', 'c', 'd']));
    });

    it('rejects upgrades that are missing something', () => {
        const registry = new UpgradeRegistry();

        expect(() => registry.register({ name: 'x', describe: () => '', apply: () => {} })).toThrow('missing its id');
        expect(() => registry.register({ id: 'x', name: 'x', describe: () => '' })).toThrow('missing apply()');
    });

    it('rejects two upgrades with the same id', () => {
        const registry = new UpgradeRegistry().register(upgrade('a'));

        expect(() => registry.register(upgrade('a'))).toThrow('registered twice');
    });
});
