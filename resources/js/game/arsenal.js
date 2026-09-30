// Stacking weapon upgrades (#65). The Arsenal holds how many of each the
// ship has; the maths lives here as plain functions so it can be tested
// without Phaser. TUNING holds fallbacks; tune() loads the admin balance
// panel's values (#27) at the start of each run.

export const TUNING = {
    // Radians between bolts in a multishot fan.
    spread: 0.14,
    // Explosive rounds: blast radius, growing with each stack.
    blastRadius: 110,
    blastRadiusPerStack: 35,
    // Chain lightning only jumps to rocks this close to the last one.
    chainRange: 320,
    // Orbiting blades.
    bladeOrbit: 150,
    bladeRadius: 26,
    bladeSpin: 3.2, // radians per second
    bladeHitCooldownMs: 300,
    // Drones.
    droneOrbit: 105,
    droneSpin: 1.1,
    droneShotMs: 700,
    // Nova pulse: fires every so often, more often with each stack.
    novaMs: 6000,
    novaMinMs: 1800,
    novaRadius: 380,
    novaRadiusPerStack: 40,
    novaKnockback: 90,
};

// Which run balance setting drives which TUNING value.
const FROM_BALANCE = {
    multishot_spread: 'spread',
    blast_radius: 'blastRadius',
    blast_radius_per_stack: 'blastRadiusPerStack',
    chain_range: 'chainRange',
    blade_orbit: 'bladeOrbit',
    blade_spin: 'bladeSpin',
    drone_fire_interval_ms: 'droneShotMs',
    nova_interval_ms: 'novaMs',
    nova_min_interval_ms: 'novaMinMs',
    nova_radius: 'novaRadius',
    nova_knockback: 'novaKnockback',
};

/**
 * Load the run's balance into TUNING. Settings the balance leaves out keep
 * their fallback.
 */
export function tune(balance, tuning = TUNING) {
    Object.entries(FROM_BALANCE).forEach(([key, name]) => {
        if (typeof balance?.[key] === 'number') {
            tuning[name] = balance[key];
        }
    });

    return tuning;
}

/**
 * How many of each stacking upgrade the ship has this run.
 */
export class Arsenal {
    constructor() {
        this.shots = 1;
        this.pierce = 0;
        this.bounces = 0;
        this.explosive = 0;
        this.chain = 0;
        this.blades = 0;
        this.drones = 0;
        this.nova = 0;
    }
}

/**
 * The rotation of each bolt in a fan of `shots`, centred on `rotation`.
 */
export function spreadAngles(rotation, shots, spread = TUNING.spread) {
    return Array.from({ length: shots }, (_, i) => rotation + (i - (shots - 1) / 2) * spread);
}

/**
 * Bounce a moving point off the inside of a rectangle. Returns the new
 * position and velocity, and whether it bounced.
 */
export function bounce({ x, y, vx, vy }, { width, height }) {
    let bounced = false;

    if ((x < 0 && vx < 0) || (x > width && vx > 0)) {
        vx = -vx;
        x = Math.min(Math.max(x, 0), width);
        bounced = true;
    }

    if ((y < 0 && vy < 0) || (y > height && vy > 0)) {
        vy = -vy;
        y = Math.min(Math.max(y, 0), height);
        bounced = true;
    }

    return { x, y, vx, vy, bounced };
}

/**
 * Where chain lightning goes from `from`: up to `count` jumps, each to the
 * nearest target not yet struck that's within range of the last one.
 *
 * @template {{x: number, y: number}} T
 * @param {T[]} targets
 * @param {Set<T>} struck already hit, never jumped to
 * @returns {T[]}
 */
export function chainTargets(from, targets, count, range = TUNING.chainRange, struck = new Set()) {
    const path = [];
    const hit = new Set(struck);
    let last = from;

    while (path.length < count) {
        let next = null;
        let best = range * range;

        for (const target of targets) {
            const distance = (target.x - last.x) ** 2 + (target.y - last.y) ** 2;

            if (!hit.has(target) && target !== from && distance <= best) {
                next = target;
                best = distance;
            }
        }

        if (!next) {
            break;
        }

        path.push(next);
        hit.add(next);
        last = next;
    }

    return path;
}

/**
 * Targets whose centre is within `radius` (plus their own size) of a point.
 *
 * @template {{x: number, y: number, radius?: number}} T
 */
export function within(point, targets, radius) {
    return targets.filter((target) => Math.hypot(target.x - point.x, target.y - point.y) <= radius + (target.radius ?? 0));
}

/**
 * Where `count` things sit, evenly spaced on a circle, turned by `angle`.
 */
export function orbit(centre, count, radius, angle) {
    return Array.from({ length: count }, (_, i) => {
        const a = angle + (i / count) * Math.PI * 2;

        return { x: centre.x + Math.cos(a) * radius, y: centre.y + Math.sin(a) * radius, angle: a };
    });
}

export const blastRadius = (level) => TUNING.blastRadius + (level - 1) * TUNING.blastRadiusPerStack;

export const novaRadius = (level) => TUNING.novaRadius + (level - 1) * TUNING.novaRadiusPerStack;

/**
 * Milliseconds between nova pulses: each stack takes a quarter off, down to a floor.
 */
export function novaInterval(level) {
    return Math.max(TUNING.novaMinMs, Math.round(TUNING.novaMs * 0.75 ** (level - 1)));
}

/**
 * Parse the dev-only ?give= param, e.g. "multishot*3,blades", into upgrade ids.
 */
export function parseGive(param) {
    return (param ?? '')
        .split(',')
        .map((part) => part.trim())
        .filter(Boolean)
        .flatMap((part) => {
            const [id, times] = part.split('*');

            return Array.from({ length: Math.min(20, Math.max(1, Number(times) || 1)) }, () => id);
        });
}
