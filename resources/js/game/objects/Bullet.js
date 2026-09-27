import Phaser from 'phaser';
import { BULLET_SPEED, HEIGHT, WIDTH } from '../config';
import { useCircleBody } from './circleBody';

/**
 * A pooled player shot. Bullets are recycled rather than created per shot:
 * fire() wakes a sleeping one up, kill() puts it back to sleep.
 */
export default class Bullet extends Phaser.Physics.Arcade.Image {
    constructor(scene, x, y) {
        super(scene, x, y, 'bullet');
    }

    fire(x, y, rotation) {
        this.enableBody(true, x, y, true, true);
        // Just the glowing tip, so a shot at any angle hits what it looks like it hits.
        useCircleBody(this, 0.6);
        this.setRotation(rotation);
        this.scene.physics.velocityFromRotation(rotation - Math.PI / 2, BULLET_SPEED, this.body.velocity);
    }

    kill() {
        this.disableBody(true, true);
    }

    update() {
        if (this.x < -50 || this.x > WIDTH + 50 || this.y < -50 || this.y > HEIGHT + 50) {
            this.kill();
        }
    }
}
