import { describe, expect, it } from 'vitest';
import { POINTS_PER_KILL, RunState, WAVE_CLEAR_BONUS } from './state';

const newRun = () => new RunState({ startingHealth: 5, now: 0 });

describe('RunState', () => {
    it('starts at full health with nothing scored', () => {
        const run = newRun();

        expect(run).toMatchObject({ health: 5, maxHealth: 5, score: 0, kills: 0, wave: 0 });
        expect(run.dead).toBe(false);
    });

    it('loses health and reports the fatal blow once', () => {
        const run = newRun();

        expect([1, 2, 3, 4].map(() => run.takeDamage())).toEqual([false, false, false, false]);
        expect(run.health).toBe(1);
        expect(run.takeDamage()).toBe(true);
        expect(run.dead).toBe(true);
        expect(run.takeDamage()).toBe(false);
        expect(run.health).toBe(0);
    });

    it('scores kills more in later waves', () => {
        const run = newRun();

        run.startWave(1);
        run.recordKill();
        run.startWave(3);
        run.recordKill();

        expect(run.kills).toBe(2);
        expect(run.score).toBe(POINTS_PER_KILL * 1 + POINTS_PER_KILL * 3);
    });

    it('gives a bonus for clearing a wave', () => {
        const run = newRun();

        run.startWave(2);
        run.waveCleared();

        expect(run.score).toBe(WAVE_CLEAR_BONUS * 2);
    });

    it('heals up to max health only', () => {
        const run = newRun();

        run.takeDamage(4);
        run.heal(3);
        expect(run.health).toBe(4);

        run.heal(3);
        expect(run.health).toBe(5);
    });

    it('raises max health and current health together', () => {
        const run = newRun();

        run.takeDamage(2);
        run.addMaxHealth(1);

        expect(run).toMatchObject({ health: 4, maxHealth: 6 });
    });

    it('reports stats for saving', () => {
        const run = newRun();

        run.startWave(4);
        run.recordKill();
        run.recordUpgrade('heal');
        run.end(95_400);

        expect(run.stats()).toEqual({
            score: POINTS_PER_KILL * 4,
            wave_reached: 4,
            kills: 1,
            duration_seconds: 95,
            upgrades: ['heal'],
        });
    });
});
