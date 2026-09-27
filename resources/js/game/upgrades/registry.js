/**
 * The pool of upgrades chat can vote for between waves.
 *
 * An upgrade is a plain object:
 *
 *   {
 *     id: 'attack-speed',                        // unique, saved in run stats
 *     name: 'Attack speed',                      // shown on the vote card
 *     describe: (balance) => 'Fire 25% faster',  // shown under the name
 *     apply: ({ player, state, balance }) => {}, // change the run
 *   }
 *
 * Upgrades in ./definitions register themselves (see ./index.js), so adding
 * one never touches the core game loop.
 */
export class UpgradeRegistry {
    constructor() {
        this.upgrades = new Map();
    }

    register(upgrade) {
        for (const key of ['id', 'name']) {
            if (typeof upgrade?.[key] !== 'string' || upgrade[key] === '') {
                throw new Error(`Upgrade is missing its ${key}.`);
            }
        }

        for (const key of ['describe', 'apply']) {
            if (typeof upgrade[key] !== 'function') {
                throw new Error(`Upgrade "${upgrade.id}" is missing ${key}().`);
            }
        }

        if (this.upgrades.has(upgrade.id)) {
            throw new Error(`Upgrade "${upgrade.id}" is registered twice.`);
        }

        this.upgrades.set(upgrade.id, upgrade);

        return this;
    }

    get(id) {
        return this.upgrades.get(id);
    }

    all() {
        return [...this.upgrades.values()];
    }

    /**
     * A random selection of different upgrades for one vote.
     */
    draw(count, random = Math.random) {
        const pool = this.all();

        // Fisher–Yates shuffle, so every upgrade is equally likely.
        for (let i = pool.length - 1; i > 0; i--) {
            const j = Math.floor(random() * (i + 1));
            [pool[i], pool[j]] = [pool[j], pool[i]];
        }

        return pool.slice(0, count);
    }
}
