import { describe, expect, it } from 'vitest';
import { asteroidShape, scramble } from './shapes';

// A repeatable stand-in for Math.random.
function seeded(seed = 1) {
    return () => {
        seed = (seed * 16807) % 2147483647;
        return (seed - 1) / 2147483646;
    };
}

describe('asteroidShape', () => {
    it('has one outline point per side, all inside the unit circle', () => {
        const { outline } = asteroidShape(seeded(), 10);

        expect(outline).toHaveLength(10);
        outline.forEach(([x, y]) => expect(Math.hypot(x, y)).toBeLessThanOrEqual(1));
    });

    it('joins the outline to a closed inner ring with facet lines', () => {
        const { facets } = asteroidShape(seeded(), 10);
        const [ring, ...spokes] = facets;

        expect(ring[0]).toBe(ring[ring.length - 1]);
        expect(spokes).toHaveLength(5);
        spokes.forEach((spoke) => expect(ring).toContain(spoke[1]));
    });
});

describe('scramble', () => {
    it('keeps the length and spaces', () => {
        const scrambled = scramble('ace pilot', 1, seeded());

        expect(scrambled).toHaveLength(9);
        expect(scrambled[3]).toBe(' ');
        expect(scrambled).not.toContain('a');
    });

    it('leaves text alone with no glitch', () => {
        expect(scramble('GeeH', 0)).toBe('GeeH');
    });
});
