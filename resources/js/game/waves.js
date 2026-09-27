// Time between enemies within a wave.
export const SPAWN_INTERVAL_MS = 800;

/**
 * How many enemies wave N has. Waves grow by the balance's per-wave increase;
 * set it to 0 for flat waves.
 */
export function waveSize(balance, wave) {
    return Math.max(1, balance.enemies_per_wave + (wave - 1) * balance.extra_enemies_per_wave);
}

/**
 * How fast wave N's enemies fly, in pixels per second.
 */
export function waveSpeed(balance, wave) {
    return balance.enemy_speed + (wave - 1) * balance.extra_enemy_speed_per_wave;
}

/**
 * Runs waves: spawns each wave's enemies over time, then reports the wave
 * cleared once every enemy has spawned and the last one is gone. Nothing
 * spawns between waves, which is when chat votes on an upgrade.
 *
 * Plain JavaScript driven by update(), so it can be tested without Phaser.
 */
export class Waves {
    /**
     * @param {Record<string, number>} balance
     * @param {{spawn: (enemy: {health: number, speed: number}) => boolean, onStart?: (wave: number, size: number) => void, onCleared?: (wave: number) => void}} callbacks
     *   spawn returns false if it couldn't spawn (e.g. the pool is full); it is retried next interval.
     */
    constructor(balance, { spawn, onStart = () => {}, onCleared = () => {} }, spawnInterval = SPAWN_INTERVAL_MS) {
        this.balance = balance;
        this.spawn = spawn;
        this.onStart = onStart;
        this.onCleared = onCleared;
        this.spawnInterval = spawnInterval;
        this.wave = 0;
        this.state = 'idle';
    }

    start(wave = 1) {
        this.wave = wave;
        this.toSpawn = waveSize(this.balance, wave);
        // Spawn the first enemy straight away.
        this.sinceSpawn = this.spawnInterval;
        this.state = 'spawning';
        this.onStart(wave, this.toSpawn);
    }

    next() {
        this.start(this.wave + 1);
    }

    /**
     * @param {number} delta milliseconds since the last update
     * @param {number} activeEnemies enemies still alive on screen
     */
    update(delta, activeEnemies) {
        if (this.state === 'spawning') {
            this.sinceSpawn += delta;

            while (this.toSpawn > 0 && this.sinceSpawn >= this.spawnInterval) {
                this.sinceSpawn -= this.spawnInterval;

                if (!this.spawn({ health: this.balance.enemy_health, speed: waveSpeed(this.balance, this.wave) })) {
                    break;
                }

                this.toSpawn--;
                activeEnemies++;
            }

            if (this.toSpawn === 0) {
                this.state = 'clearing';
            }
        }

        if (this.state === 'clearing' && activeEnemies === 0) {
            this.state = 'cleared';
            this.onCleared(this.wave);
        }
    }
}
