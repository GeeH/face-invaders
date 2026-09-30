import { describe, expect, it } from 'vitest';
import { TUNING, blastRadius, bounce, chainTargets, novaInterval, orbit, parseGive, spreadAngles, within } from './arsenal';

describe('spreadAngles', () => {
    it('fires a single bolt straight ahead', () => {
        expect(spreadAngles(1, 1)).toEqual([1]);
    });

    it('fans bolts evenly around the aim', () => {
        expect(spreadAngles(0, 3, 0.1)).toEqual([-0.1, 0, 0.1]);
        expect(spreadAngles(0, 2, 0.1)).toEqual([-0.05, 0.05]);
    });
});

describe('bounce', () => {
    const screen = { width: 100, height: 50 };

    it('leaves a point inside the screen alone', () => {
        expect(bounce({ x: 50, y: 20, vx: 5, vy: -5 }, screen)).toEqual({ x: 50, y: 20, vx: 5, vy: -5, bounced: false });
    });

    it('reflects off the side it crossed and pulls the point back in', () => {
        expect(bounce({ x: 104, y: 20, vx: 5, vy: -5 }, screen)).toEqual({ x: 100, y: 20, vx: -5, vy: -5, bounced: true });
        expect(bounce({ x: 20, y: -3, vx: 5, vy: -5 }, screen)).toEqual({ x: 20, y: 0, vx: 5, vy: 5, bounced: true });
    });

    it('reflects off both sides in a corner', () => {
        expect(bounce({ x: -1, y: 51, vx: -5, vy: 5 }, screen)).toMatchObject({ vx: 5, vy: -5, bounced: true });
    });

    it('does not bounce a point already heading back in', () => {
        expect(bounce({ x: 104, y: 20, vx: -5, vy: 0 }, screen).bounced).toBe(false);
    });
});

describe('chainTargets', () => {
    const a = { x: 100, y: 0 };
    const b = { x: 200, y: 0 };
    const c = { x: 250, y: 0 };
    const far = { x: 5000, y: 0 };
    const from = { x: 0, y: 0 };

    it('jumps to the nearest target each time', () => {
        expect(chainTargets(from, [c, b, a, far], 3, 150)).toEqual([a, b, c]);
    });

    it('stops when the next target is out of range', () => {
        expect(chainTargets(from, [a, far], 5, 150)).toEqual([a]);
    });

    it('never jumps back to the start or to anything already struck', () => {
        expect(chainTargets(a, [a, b, c], 5, 150, new Set([b]))).toEqual([c]);
    });
});

describe('within', () => {
    it('includes targets whose edge is inside the radius', () => {
        const near = { x: 50, y: 0, radius: 0 };
        const edge = { x: 70, y: 0, radius: 25 };
        const out = { x: 70, y: 0, radius: 5 };

        expect(within({ x: 0, y: 0 }, [near, edge, out], 60)).toEqual([near, edge]);
    });
});

describe('orbit', () => {
    it('spaces things evenly around a circle', () => {
        const [first, second] = orbit({ x: 10, y: 10 }, 2, 5, 0);

        expect(first.x).toBeCloseTo(15);
        expect(first.y).toBeCloseTo(10);
        expect(second.x).toBeCloseTo(5);
        expect(second.y).toBeCloseTo(10);
    });
});

describe('stacking', () => {
    it('grows the blast with each explosive stack', () => {
        expect(blastRadius(2) - blastRadius(1)).toBe(TUNING.blastRadiusPerStack);
    });

    it('pulses the nova more often with each stack, down to a floor', () => {
        expect(novaInterval(1)).toBe(TUNING.novaMs);
        expect(novaInterval(2)).toBeLessThan(novaInterval(1));
        expect(novaInterval(50)).toBe(TUNING.novaMinMs);
    });
});

describe('parseGive', () => {
    it('reads upgrade ids with optional counts', () => {
        expect(parseGive('multishot*3, blades')).toEqual(['multishot', 'multishot', 'multishot', 'blades']);
    });

    it('is empty without the param and caps silly counts', () => {
        expect(parseGive(null)).toEqual([]);
        expect(parseGive('nova*999')).toHaveLength(20);
    });
});
