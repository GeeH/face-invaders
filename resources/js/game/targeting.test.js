import { describe, expect, it } from 'vitest';
import { aimAt, nearest, shotInterval } from './targeting';

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

describe('shotInterval', () => {
    it('turns shots per second into milliseconds between shots', () => {
        expect(shotInterval(2)).toBe(500);
        expect(shotInterval(4)).toBe(250);
    });
});
