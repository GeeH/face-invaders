import Phaser from 'phaser';
import { bounce } from '../arsenal';
import { BULLET_SPEED, HEIGHT, WIDTH } from '../config';
import { useCircleBody } from './circleBody';

const SCREEN = { width: WIDTH, height: HEIGHT };

/**
 * A pooled player shot. Bullets are recycled rather than created per shot:
 * fire() wakes a sleeping one up, kill() puts it back to sleep.
 *
 * Piercing bolts pass through a few rocks before dying, and ricochet bolts
 * bounce off the screen edges a few times (#65).
 */
export default class Bullet extends Phaser.Physics.Arcade.Image {
    constructor(scene, x, y) {
        super(scene, x, y, 'neon-bolt');
        this.struck = new Set();
    }

    fire(x, y, rotation, { pierce = 0, bounces = 0 } = {}) {
        this.enableBody(true, x, y, true, true);
        // Just the glowing tip, so a shot at any angle hits what it looks like it hits.
        useCircleBody(this, 0.5);
        this.setRotation(rotation);
        this.scene.physics.velocityFromRotation(rotation - Math.PI / 2, BULLET_SPEED, this.body.velocity);
        this.pierceLeft = pierce;
        this.bouncesLeft = bounces;
        this.struck.clear();
    }

    /**
     * Hit an enemy. Returns false if this bolt already went through it;
     * otherwise the bolt spends a pierce or dies.
     */
    strike(enemy) {
        if (this.struck.has(enemy)) {
            return false;
        }

        this.struck.add(enemy);

        if (this.pierceLeft > 0) {
            this.pierceLeft--;
        } else {
            this.kill();
        }

        return true;
    }

    kill() {
        this.disableBody(true, true);
    }

    update() {
        if (!this.active) {
            return;
        }

        if (this.bouncesLeft > 0) {
            const { velocity } = this.body;
            const next = bounce({ x: this.x, y: this.y, vx: velocity.x, vy: velocity.y }, SCREEN);

            if (next.bounced) {
                this.bouncesLeft--;
                this.setPosition(next.x, next.y);
                velocity.set(next.vx, next.vy);
                this.setRotation(Math.atan2(next.vy, next.vx) + Math.PI / 2);
                // A bounced bolt can hit the same rock again on the way back.
                this.struck.clear();
            }

            return;
        }

        if (this.x < -50 || this.x > WIDTH + 50 || this.y < -50 || this.y > HEIGHT + 50) {
            this.kill();
        }
    }
}
