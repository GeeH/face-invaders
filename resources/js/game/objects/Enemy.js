import Phaser from 'phaser';
import { BASE_RADIUS, HEIGHT, WIDTH } from '../config';
import { useCircleBody } from './circleBody';

/**
 * A stand-in enemy that flies from a screen edge straight at the base.
 * #14 replaces its sprite with one that carries a follower's face and name.
 */
export default class Enemy extends Phaser.Physics.Arcade.Image {
    constructor(scene, x, y) {
        super(scene, x, y, 'enemy');
    }

    /**
     * Start at a random point just off a screen edge, heading for the centre.
     */
    spawn({ health, speed }) {
        const { x, y } = randomEdgePoint();

        this.enableBody(true, x, y, true, true);
        useCircleBody(this);
        this.health = health;
        this.setRotation(Math.atan2(HEIGHT / 2 - y, WIDTH / 2 - x) - Math.PI / 2);
        this.scene.physics.moveTo(this, WIDTH / 2, HEIGHT / 2, speed);
    }

    /**
     * Take damage; returns true if this killed the enemy.
     */
    hit(damage) {
        this.health -= damage;

        if (this.health > 0) {
            return false;
        }

        this.kill();
        return true;
    }

    kill() {
        this.disableBody(true, true);
    }

    update() {
        if (Phaser.Math.Distance.Between(this.x, this.y, WIDTH / 2, HEIGHT / 2) < BASE_RADIUS) {
            this.kill();
            this.scene.events.emit('enemy-reached-base', this);
        }
    }
}

function randomEdgePoint() {
    const margin = 60;
    const edge = Phaser.Math.Between(0, 3);

    if (edge === 0) return { x: Phaser.Math.Between(0, WIDTH), y: -margin };
    if (edge === 1) return { x: WIDTH + margin, y: Phaser.Math.Between(0, HEIGHT) };
    if (edge === 2) return { x: Phaser.Math.Between(0, WIDTH), y: HEIGHT + margin };

    return { x: -margin, y: Phaser.Math.Between(0, HEIGHT) };
}
