/**
 * The target closest to a point, or null when there are none.
 *
 * @template {{x: number, y: number}} T
 * @param {{x: number, y: number}} from
 * @param {T[]} targets
 * @returns {T|null}
 */
export function nearest(from, targets) {
    let best = null;
    let bestDistance = Infinity;

    for (const target of targets) {
        const distance = (target.x - from.x) ** 2 + (target.y - from.y) ** 2;

        if (distance < bestDistance) {
            best = target;
            bestDistance = distance;
        }
    }

    return best;
}

/**
 * The rotation that points a sprite drawn facing up at a target.
 */
export function aimAt(from, target) {
    return Math.atan2(target.y - from.y, target.x - from.x) + Math.PI / 2;
}

/**
 * Milliseconds between shots for a fire rate in shots per second.
 */
export function shotInterval(fireRate) {
    return 1000 / fireRate;
}
