import { describe, expect, it, vi } from 'vitest';
import { Waves, waveSize, waveSpeed } from './waves';

const balance = {
    enemies_per_wave: 3,
    extra_enemies_per_wave: 2,
    enemy_health: 1,
    enemy_speed: 100,
    extra_enemy_speed_per_wave: 10,
};

function run(overrides = {}) {
    const spawned = [];
    const events = [];
    const waves = new Waves(
        { ...balance, ...overrides },
        {
            spawn: (enemy) => spawned.push(enemy) > 0,
            onStart: (wave, size) => events.push(['start', wave, size]),
            onCleared: (wave) => events.push(['cleared', wave]),
        },
        100,
    );

    return { waves, spawned, events };
}

describe('ramp', () => {
    it('grows each wave by the extra enemies per wave', () => {
        expect([1, 2, 3].map((wave) => waveSize(balance, wave))).toEqual([3, 5, 7]);
    });

    it('speeds each wave up by the extra speed per wave', () => {
        expect([1, 2, 3].map((wave) => waveSpeed(balance, wave))).toEqual([100, 110, 120]);
    });

    it('stays flat when the increases are zero', () => {
        const flat = { ...balance, extra_enemies_per_wave: 0, extra_enemy_speed_per_wave: 0 };

        expect([waveSize(flat, 1), waveSize(flat, 9)]).toEqual([3, 3]);
        expect([waveSpeed(flat, 1), waveSpeed(flat, 9)]).toEqual([100, 100]);
    });

    it('always has at least one enemy', () => {
        expect(waveSize({ ...balance, enemies_per_wave: 0, extra_enemies_per_wave: 0 }, 1)).toBe(1);
    });
});

describe('Waves', () => {
    it('spawns the first enemy straight away, then one per interval', () => {
        const { waves, spawned } = run();

        waves.start();
        waves.update(0, 0);
        expect(spawned).toHaveLength(1);

        waves.update(99, 1);
        expect(spawned).toHaveLength(1);

        waves.update(1, 1);
        expect(spawned).toHaveLength(2);
    });

    it('spawns exactly the wave size with the wave\'s health and speed', () => {
        const { waves, spawned } = run();

        waves.start(2);
        for (let i = 0; i < 20; i++) waves.update(100, 1);

        expect(spawned).toHaveLength(5);
        expect(spawned[0]).toEqual({ health: 1, speed: 110 });
    });

    it('clears the wave only once everything has spawned and the last enemy is gone', () => {
        const { waves, events } = run();

        waves.start();
        for (let i = 0; i < 5; i++) waves.update(100, 1);
        expect(events).toEqual([['start', 1, 3]]);

        waves.update(16, 0);
        expect(events).toEqual([['start', 1, 3], ['cleared', 1]]);
        expect(waves.state).toBe('cleared');
    });

    it('does not clear while enemies are still spawning, even if none are alive', () => {
        const { waves, events } = run();

        waves.start();
        waves.update(0, 0);
        waves.update(10, 0);

        expect(events.some(([name]) => name === 'cleared')).toBe(false);
    });

    it('spawns nothing between waves', () => {
        const { waves, spawned } = run({ enemies_per_wave: 1, extra_enemies_per_wave: 0 });

        waves.start();
        waves.update(0, 0);
        waves.update(10, 0);
        for (let i = 0; i < 10; i++) waves.update(1000, 0);

        expect(spawned).toHaveLength(1);
    });

    it('starts the next, bigger wave', () => {
        const { waves, events } = run();

        waves.start();
        waves.next();

        expect(events.at(-1)).toEqual(['start', 2, 5]);
    });

    it('retries a spawn that failed because the pool was full', () => {
        const spawn = vi.fn().mockReturnValueOnce(false).mockReturnValue(true);
        const waves = new Waves(balance, { spawn }, 100);

        waves.start();
        waves.update(0, 0);
        waves.update(100, 0);
        waves.update(100, 1);
        waves.update(100, 2);

        expect(spawn).toHaveBeenCalledTimes(4);
        expect(waves.toSpawn).toBe(0);
    });
});
