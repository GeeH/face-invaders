// Plain-JavaScript geometry and glitch helpers for the neon art, kept free
// of Phaser so they can be tested.

/**
 * A lumpy, crystalline asteroid in unit coordinates: an outline plus facet
 * lines joining it to a small ring of inner points.
 *
 * @param {() => number} random 0–1
 */
export function asteroidShape(random, sides) {
    const outline = Array.from({ length: sides }, (_, i) => {
        const angle = (i / sides) * Math.PI * 2 + (random() - 0.5) * 0.4;
        const radius = 0.72 + random() * 0.28;

        return [Math.cos(angle) * radius, Math.sin(angle) * radius];
    });

    const innerCount = 3 + Math.floor(random() * 2);
    const inner = Array.from({ length: innerCount }, (_, i) => {
        const angle = (i / innerCount) * Math.PI * 2 + random();
        const radius = 0.25 + random() * 0.2;

        return [Math.cos(angle) * radius, Math.sin(angle) * radius];
    });

    const closest = (point) => inner.reduce((best, p) => (dist(p, point) < dist(best, point) ? p : best));
    const facets = [
        [...inner, inner[0]],
        ...outline.filter((_, i) => i % 2 === 0).map((point) => [point, closest(point)]),
    ];

    return { outline, facets };
}

const dist = ([ax, ay], [bx, by]) => (ax - bx) ** 2 + (ay - by) ** 2;

/**
 * Glyphs swapped into a name while its asteroid glitches.
 */
const GLITCH_GLYPHS = '#%&@$!?/\\<>*=+01';

export function scramble(text, amount = 0.3, random = Math.random) {
    return [...text].map((char) => (char !== ' ' && random() < amount ? GLITCH_GLYPHS[Math.floor(random() * GLITCH_GLYPHS.length)] : char)).join('');
}
