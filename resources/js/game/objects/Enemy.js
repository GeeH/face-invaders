import Phaser from 'phaser';
import { BASE_RADIUS, HEIGHT, WIDTH } from '../config';
import { ASTEROIDS, ASTEROID_TEXTURE_SIZE, neonStyle } from '../neon';
import { scramble } from '../shapes';
import { useCircleBody } from './circleBody';

// The asteroid's rocky outline fills about 70% of its (glow-padded) texture.
const BODY_SCALE = 0.34;
// Names can spill past the rock like a sign across it, up to a point.
const LABEL_WIDTH = ASTEROID_TEXTURE_SIZE * 1.1;

/**
 * A glitchy neon asteroid with a follower's name across it. It drifts from a
 * screen edge straight at the player, tumbling as it goes, and every so often
 * glitches: colour-split ghosts, a twitch, and a scrambled name.
 */
export default class Enemy extends Phaser.Physics.Arcade.Image {
    constructor(scene, x, y) {
        super(scene, x, y, ASTEROIDS[0].key);

        this.ghosts = [0xff00ff, 0x00ffff].map((tint) =>
            scene.add.image(x, y, ASTEROIDS[0].key).setTint(tint).setTintMode(Phaser.TintModes.FILL).setAlpha(0.55).setVisible(false),
        );
        this.label = scene.add.text(x, y, '', nameStyle('#ffffff')).setOrigin(0.5).setVisible(false);
    }

    /**
     * Start at a random point just off a screen edge, heading for the centre.
     */
    spawn({ face, health, speed }) {
        const { x, y } = randomEdgePoint();
        const asteroid = Phaser.Utils.Array.GetRandom(ASTEROIDS);

        this.enableBody(true, x, y, true, true);
        this.setTexture(asteroid.key).setScale(Phaser.Math.FloatBetween(1.1, 1.5)).setAlpha(1);
        useCircleBody(this, BODY_SCALE);
        this.setAngularVelocity(Phaser.Math.Between(-50, 50));
        this.colour = asteroid.colour;
        this.face = face;
        this.health = health;
        this.scene.physics.moveTo(this, WIDTH / 2, HEIGHT / 2, speed);

        this.ghosts.forEach((ghost) => ghost.setTexture(asteroid.key).setScale(this.scale));

        this.displayName = face?.name ?? '???';
        this.label.setStyle(nameStyle(asteroid.colour)).setText(this.displayName).setScale(1).setVisible(true);
        this.label.setScale(Math.min(1, LABEL_WIDTH / (this.label.width - this.label.padding.left * 2)));

        this.glitchMs = 0;
        this.nextGlitchMs = Phaser.Math.Between(400, 2500);
        this.follow();
    }

    /**
     * How far the rock reaches from its centre, for area attacks.
     */
    get radius() {
        return this.body?.halfWidth ?? 0;
    }

    /**
     * Take damage; returns true if this killed the enemy.
     */
    hit(damage) {
        this.health -= damage;

        if (this.health > 0) {
            this.setTintMode(Phaser.TintModes.FILL).setTint(0xffffff);
            this.scene.time.delayedCall(60, () => this.clearTint().setTintMode(Phaser.TintModes.MULTIPLY));
            return false;
        }

        this.kill();
        return true;
    }

    kill() {
        this.disableBody(true, true);
        this.label.setVisible(false);
        this.ghosts.forEach((ghost) => ghost.setVisible(false));
    }

    update(time, delta) {
        if (!this.active) {
            return;
        }

        this.glitch(delta);
        this.follow();

        if (Phaser.Math.Distance.Between(this.x, this.y, WIDTH / 2, HEIGHT / 2) < BASE_RADIUS) {
            this.kill();
            this.scene.events.emit('enemy-reached-base', this);
        }
    }

    glitch(delta) {
        if (this.glitchMs > 0) {
            this.glitchMs -= delta;

            if (this.glitchMs <= 0) {
                this.ghosts.forEach((ghost) => ghost.setVisible(false));
                this.label.setText(this.displayName);
                this.setAlpha(1);
            }

            return;
        }

        this.nextGlitchMs -= delta;

        if (this.nextGlitchMs <= 0) {
            this.glitchMs = Phaser.Math.Between(80, 220);
            this.nextGlitchMs = Phaser.Math.Between(700, 3500);
            this.split = Phaser.Math.Between(5, 14);
            this.ghosts.forEach((ghost) => ghost.setVisible(true));
            this.label.setText(scramble(this.displayName, 0.4));
            this.setAlpha(Math.random() < 0.5 ? 0.6 : 1);
        }
    }

    /**
     * Keep the name and glitch ghosts on the asteroid.
     */
    follow() {
        const jitter = this.glitchMs > 0 ? Phaser.Math.Between(-4, 4) : 0;

        this.label.setPosition(this.x + jitter, this.y).setDepth(this.depth + 2);
        this.ghosts.forEach((ghost, i) => {
            ghost
                .setPosition(this.x + (i === 0 ? -this.split : this.split), this.y + jitter)
                .setRotation(this.rotation)
                .setDepth(this.depth - 1);
        });
    }
}

/**
 * White-hot with a dark outline, so the name reads over the asteroid's facets.
 */
function nameStyle(colour) {
    return { ...neonStyle(colour, 32), color: '#ffffff', stroke: '#0a0014', strokeThickness: 7 };
}

function randomEdgePoint() {
    const margin = 120;
    const edge = Phaser.Math.Between(0, 3);

    if (edge === 0) return { x: Phaser.Math.Between(0, WIDTH), y: -margin };
    if (edge === 1) return { x: WIDTH + margin, y: Phaser.Math.Between(0, HEIGHT) };
    if (edge === 2) return { x: Phaser.Math.Between(0, WIDTH), y: HEIGHT + margin };

    return { x: -margin, y: Phaser.Math.Between(0, HEIGHT) };
}
