import { describe, expect, it } from 'vitest';
import { aimAt, nearest, shotInterval, turnRate, turnTowards } from './targeting';

describe('nearest', () => {
    it('picks the closest target', () => {
        const near = { x: 110, y: 100 };
        const far = { x: 500, y: 500 };

        expect(nearest({ x: 100, y: 100 }, [far, near])).toBe(near);
    });

    it('returns null with no targets', () => {
        expect(nearest({ x: 0, y: 0 }, [])).toBeNull();
    });
});

describe('aimAt', () => {
    const centre = { x: 0, y: 0 };

    it('points straight up at a target above', () => {
        expect(aimAt(centre, { x: 0, y: -10 })).toBeCloseTo(0);
    });

    it('points right at a target to the right', () => {
        expect(aimAt(centre, { x: 10, y: 0 })).toBeCloseTo(Math.PI / 2);
    });

    it('points down at a target below', () => {
        expect(aimAt(centre, { x: 0, y: 10 })).toBeCloseTo(Math.PI);
    });
});

describe('turnRate', () => {
    it('turns degrees per second into radians per millisecond', () => {
        expect(turnRate(180)).toBeCloseTo(Math.PI / 1000);
        expect(turnRate(360) * 1000).toBeCloseTo(Math.PI * 2);
    });
});

describe('shotInterval', () => {
    it('turns shots per second into milliseconds between shots', () => {
        expect(shotInterval(2)).toBe(500);
        expect(shotInterval(4)).toBe(250);
    });
});

describe('turnTowards', () => {
    const step = 0.1;

    it('turns by at most one step towards the target', () => {
        expect(turnTowards(0, 1, step)).toBeCloseTo(0.1);
        expect(turnTowards(0, -1, step)).toBeCloseTo(-0.1);
    });

    it('lands exactly on a target within one step', () => {
        expect(turnTowards(0, 0.05, step)).toBeCloseTo(0.05);
    });

    it('takes the short way round across ±π', () => {
        // Just below π to just above -π is a small turn onwards, not most of a circle back.
        expect(turnTowards(3.0, -3.0, step)).toBeCloseTo(3.0 + step);
    });

    it('does not snap when the raw angles differ by more than a full turn (#70)', () => {
        // Ship pointing down (-π), rock to the left: aimAt gives 1.5π. The real turn is a quarter turn.
        const next = turnTowards(-Math.PI, aimAt({ x: 0, y: 0 }, { x: -100, y: 0 }), step);

        expect(Math.abs(next - -Math.PI)).toBeCloseTo(step);
    });

    it('never turns more than one step, whatever the angles', () => {
        for (let current = -Math.PI; current < Math.PI; current += 0.37) {
            for (let target = -Math.PI / 2; target <= (3 * Math.PI) / 2; target += 0.29) {
                const moved = turnTowards(current, target, step) - current;
                expect(Math.abs(moved)).toBeLessThanOrEqual(step + 1e-9);
            }
        }
    });
});
