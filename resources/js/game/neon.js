import Phaser from 'phaser';
import { asteroidShape } from './shapes';

// The neon look (#58): everything is glowing vector lines, baked once into
// textures at boot with canvas shadows, so there's no per-frame glow cost.

export const CYAN = '#00e5ff';
export const GREEN = '#39ff14';
export const MAGENTA = '#ff2bd6';
export const ORANGE = '#ff9a1f';
export const PURPLE = '#b026ff';
export const YELLOW = '#ffe600';
export const RED = '#ff3860';

// Asteroids come in every colour except the player's cyan.
export const ASTEROID_COLOURS = [MAGENTA, GREEN, ORANGE, PURPLE, YELLOW, RED];
const ASTEROID_SHAPES = 6;
const ASTEROID_RADIUS = 64;
const ASTEROID_PAD = 28;
export const ASTEROID_TEXTURE_SIZE = (ASTEROID_RADIUS + ASTEROID_PAD) * 2;
export const ASTEROIDS = ASTEROID_COLOURS.flatMap((colour, c) =>
    Array.from({ length: ASTEROID_SHAPES }, (_, s) => ({ key: `asteroid-${c}-${s}`, colour })),
);

export const UI_FONT = '"Orbitron", system-ui, sans-serif';
export const DISPLAY_FONT = '"Monoton", "Orbitron", system-ui, sans-serif';

/**
 * Wait for the Google fonts, so no text is baked with a fallback font. Gives
 * up after a few seconds rather than keep OBS on a blank screen when offline.
 */
export function loadFonts() {
    const fonts = ['40px Monoton', '500 40px Orbitron', '700 40px Orbitron', '900 40px Orbitron'].map((font) => document.fonts.load(font));

    return Promise.race([Promise.all(fonts), new Promise((resolve) => setTimeout(resolve, 3000))]).catch(() => {});
}

export const toInt = (colour) => Phaser.Display.Color.HexStringToColor(colour).color;

/**
 * Text style for glowing neon: a hot pale fill with a coloured halo.
 */
export function neonStyle(colour, size, { font = UI_FONT, weight = '900', align = 'center' } = {}) {
    const blur = Math.max(12, size * 0.35);

    return {
        fontFamily: font,
        fontSize: `${size}px`,
        fontStyle: weight,
        color: mix(colour, '#ffffff', 0.45),
        stroke: colour,
        strokeThickness: Math.max(2, size * 0.06),
        align,
        padding: { x: blur, y: blur },
        shadow: { offsetX: 0, offsetY: 0, color: colour, blur, stroke: true, fill: true },
    };
}

/**
 * Bake every neon texture the game uses.
 */
export function bakeNeonTextures(scene) {
    bake(scene, 'neon-ship', 150, 150, (ctx) => ship(ctx, 75, 75, 1, CYAN));
    bake(scene, 'neon-life', 60, 60, (ctx) => ship(ctx, 30, 30, 0.42, CYAN, 3));
    bake(scene, 'neon-flame', 80, 60, (ctx) => {
        [22, 58].forEach((x) => glow(ctx, CYAN, 3, () => polyline(ctx, [[x - 8, 12], [x, 46], [x + 8, 12]])));
    });
    bake(scene, 'neon-bolt', 30, 84, (ctx) => glow(ctx, GREEN, 5, () => polyline(ctx, [[15, 14], [15, 70]])));
    bake(scene, 'neon-shard', 24, 48, (ctx) => glow(ctx, '#ffffff', 4, () => polyline(ctx, [[12, 12], [12, 36]])));
    bake(scene, 'neon-ring', 160, 160, (ctx) => glow(ctx, '#ffffff', 5, () => ctx.arc(80, 80, 56, 0, Math.PI * 2)));

    const random = new Phaser.Math.RandomDataGenerator(['face-invaders']);
    ASTEROIDS.forEach(({ key, colour }, i) => {
        const shape = asteroidShape(() => random.frac(), 9 + (i % 4));
        const c = ASTEROID_TEXTURE_SIZE / 2;

        bake(scene, key, ASTEROID_TEXTURE_SIZE, ASTEROID_TEXTURE_SIZE, (ctx) => {
            const at = ([x, y]) => [c + x * ASTEROID_RADIUS, c + y * ASTEROID_RADIUS];

            glow(ctx, colour, 3, () => shape.facets.forEach((line) => polyline(ctx, line.map(at))), 0.8);
            glow(ctx, colour, 5, () => polyline(ctx, shape.outline.map(at), true));
        });
    });
}

/**
 * Overlapping glowing outlines drawn with a filled square, used for cards.
 */
export function neonRect(graphics, width, height, colour, fillAlpha = 0.82) {
    const c = toInt(colour);

    graphics.clear();
    graphics.fillStyle(0x07020f, fillAlpha).fillRoundedRect(-width / 2, -height / 2, width, height, 18);
    [[22, 0.06], [14, 0.12], [8, 0.3], [4, 1]].forEach(([line, alpha]) => {
        graphics.lineStyle(line, c, alpha).strokeRoundedRect(-width / 2, -height / 2, width, height, 18);
    });
    graphics.lineStyle(1.5, 0xffffff, 0.9).strokeRoundedRect(-width / 2, -height / 2, width, height, 18);

    return graphics;
}

/**
 * A burst of neon: an expanding ring and shards flying out.
 */
export function explode(scene, x, y, colour, size = 1, depth = 20) {
    const tint = toInt(colour);
    const ring = scene.add.image(x, y, 'neon-ring').setTint(tint).setDepth(depth).setScale(0.2 * size);

    scene.tweens.add({ targets: ring, scale: 1.6 * size, alpha: 0, duration: 380, ease: 'Cubic.out', onComplete: () => ring.destroy() });

    const shards = Math.round(10 * Math.sqrt(size));

    for (let i = 0; i < shards; i++) {
        const angle = (i / shards) * Math.PI * 2 + Math.random() * 0.5;
        const distance = (80 + Math.random() * 140) * size;
        const shard = scene.add
            .image(x, y, 'neon-shard')
            .setTint(Math.random() < 0.25 ? 0xffffff : tint)
            .setDepth(depth)
            .setRotation(angle + Math.PI / 2)
            .setScale(0.6 + Math.random() * 0.8);

        scene.tweens.add({
            targets: shard,
            x: x + Math.cos(angle) * distance,
            y: y + Math.sin(angle) * distance,
            angle: shard.angle + Phaser.Math.Between(-360, 360),
            alpha: 0,
            duration: 450 + Math.random() * 350,
            ease: 'Cubic.out',
            onComplete: () => shard.destroy(),
        });
    }
}

/**
 * Over-the-top banner text: each letter slams in, glitches while it holds,
 * then the whole thing blows apart. Lines after the first are smaller.
 */
export function boom(scene, message, { colour = MAGENTA, size = 110, holdMs = 1200, y, depth = 30, font = DISPLAY_FONT } = {}) {
    const lines = message.split('\n');
    const centreY = y ?? scene.scale.height / 2 - 200;
    const cx = scene.scale.width / 2;
    const sizes = lines.map((_, i) => (i === 0 ? size : Math.round(size * 0.42)));
    const height = sizes.reduce((sum, s) => sum + s * 1.15, 0);
    const letters = [];

    let top = centreY - height / 2;
    lines.forEach((line, i) => {
        const style = i === 0 ? neonStyle(colour, sizes[i], { font }) : neonStyle(CYAN, sizes[i]);
        const chars = [...line].map((char) => scene.add.text(0, 0, char, style).setOrigin(0.5).setDepth(depth));
        const blur = style.padding.x;
        const width = chars.reduce((sum, t) => sum + t.width - blur * 2, 0);

        let x = cx - width / 2;
        chars.forEach((t) => {
            const w = t.width - blur * 2;
            t.home = { x: x + w / 2, y: top + (sizes[i] * 1.15) / 2 };
            x += w;
        });

        letters.push(...chars);
        top += sizes[i] * 1.15;
    });

    letters.forEach((t, i) => {
        t.setPosition(t.home.x + Phaser.Math.Between(-300, 300), t.home.y + Phaser.Math.Between(-250, 250))
            .setScale(3)
            .setAngle(Phaser.Math.Between(-120, 120))
            .setAlpha(0);

        scene.tweens.add({
            targets: t,
            x: t.home.x,
            y: t.home.y,
            scale: 1,
            angle: 0,
            alpha: 1,
            delay: i * 22,
            duration: 380,
            ease: 'Back.out',
        });
    });

    const landedAt = letters.length * 22 + 380;

    scene.time.delayedCall(landedAt, () => {
        explode(scene, cx, centreY, colour, 2.2, depth - 1);
        scene.cameras.main.shake(180, 0.004);
    });

    // Glitch: letters twitch and flicker while the banner holds.
    const glitch = scene.time.addEvent({
        delay: 70,
        loop: true,
        startAt: 0,
        callback: () => {
            letters.forEach((t) => {
                const twitch = Math.random() < 0.08;
                t.x = t.home.x + (twitch ? Phaser.Math.Between(-10, 10) : 0);
                t.setAlpha(Math.random() < 0.04 ? 0.3 : 1);
            });
        },
    });

    scene.time.delayedCall(landedAt + holdMs, () => {
        glitch.remove();
        explode(scene, cx, centreY, colour, 3, depth - 1);

        letters.forEach((t) => {
            const angle = Math.atan2(t.home.y - centreY, t.home.x - cx) + (Math.random() - 0.5);
            const distance = 400 + Math.random() * 700;

            scene.tweens.add({
                targets: t,
                x: t.x + Math.cos(angle) * distance,
                y: t.y + Math.sin(angle) * distance,
                angle: Phaser.Math.Between(-540, 540),
                scale: 2.5,
                alpha: 0,
                duration: 650,
                ease: 'Cubic.out',
                onComplete: () => t.destroy(),
            });
        });
    });
}

function bake(scene, key, width, height, draw) {
    if (scene.textures.exists(key)) {
        return;
    }

    const texture = scene.textures.createCanvas(key, width, height);
    const ctx = texture.getContext();
    ctx.lineJoin = 'round';
    ctx.lineCap = 'round';
    draw(ctx);
    texture.refresh();
}

/**
 * Stroke a path three times: a wide soft halo, a coloured tube, and a thin white-hot core.
 */
function glow(ctx, colour, width, path, strength = 1) {
    const passes = [
        [width * 2.4, colour, 26, 0.35 * strength],
        [width * 1.3, colour, 12, 0.9 * strength],
        [width * 0.45, '#ffffff', 4, 0.95 * strength],
    ];

    passes.forEach(([lineWidth, stroke, blur, alpha]) => {
        ctx.save();
        ctx.globalAlpha = alpha;
        ctx.shadowColor = colour;
        ctx.shadowBlur = blur;
        ctx.strokeStyle = stroke;
        ctx.lineWidth = lineWidth;
        ctx.beginPath();
        path();
        ctx.stroke();
        ctx.restore();
    });
}

function polyline(ctx, points, closed = false) {
    points.forEach(([x, y], i) => (i === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y)));

    if (closed) {
        ctx.closePath();
    }
}

/**
 * The player's arrowhead ship, nose up, centred on (x, y).
 */
function ship(ctx, x, y, scale, colour, width = 4) {
    const at = ([px, py]) => [x + px * scale, y + py * scale];
    const hull = [[0, -52], [40, 40], [14, 24], [0, 34], [-14, 24], [-40, 40]];
    const cockpit = [[0, -20], [13, 14], [-13, 14]];

    glow(ctx, colour, width, () => {
        polyline(ctx, hull.map(at), true);
        polyline(ctx, cockpit.map(at), true);
    });
}

/**
 * Blend two hex colours; amount 0 is a, 1 is b.
 */
export function mix(a, b, amount) {
    const ca = Phaser.Display.Color.HexStringToColor(a);
    const cb = Phaser.Display.Color.HexStringToColor(b);
    const channel = (from, to) => Math.round(from + (to - from) * amount);

    return Phaser.Display.Color.RGBToString(channel(ca.red, cb.red), channel(ca.green, cb.green), channel(ca.blue, cb.blue), 255, '#');
}
