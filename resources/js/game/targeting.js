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
 * Turn from `current` towards `target` by at most `maxStep` radians, the
 * short way round. Angles can be in any range (aimAt's go past π), so the
 * difference is wrapped first; comparing them raw is what made the ship
 * snap instead of turning (#70).
 */
export function turnTowards(current, target, maxStep) {
    const difference = Math.atan2(Math.sin(target - current), Math.cos(target - current));

    if (Math.abs(difference) <= maxStep) {
        return current + difference;
    }

    return current + Math.sign(difference) * maxStep;
}

/**
 * Radians per millisecond for a turn speed in degrees per second.
 */
export function turnRate(degreesPerSecond) {
    return (degreesPerSecond * Math.PI) / 180 / 1000;
}

/**
 * Milliseconds between shots for a fire rate in shots per second.
 */
export function shotInterval(fireRate) {
    return 1000 / fireRate;
}
