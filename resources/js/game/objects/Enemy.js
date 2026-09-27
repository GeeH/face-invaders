import Phaser from 'phaser';
import { SAUCERS } from '../assets';
import { BASE_RADIUS, HEIGHT, WIDTH } from '../config';
import { faceKey } from '../faces';
import { useCircleBody } from './circleBody';

const SAUCER_SCALE = 1.3;
// The porthole takes up about half the saucer.
const FACE_SIZE = 91 * SAUCER_SCALE * 0.52;

/**
 * A saucer carrying a follower's face in its porthole and their name
 * underneath. It flies from a screen edge straight at the player.
 */
export default class Enemy extends Phaser.Physics.Arcade.Image {
    constructor(scene, x, y) {
        super(scene, x, y, SAUCERS[0]);

        this.setScale(SAUCER_SCALE);
        this.face = scene.add.image(x, y, '__DEFAULT').setVisible(false);
        this.label = scene.add
            .text(x, y, '', {
                fontFamily: 'system-ui, sans-serif',
                fontSize: '22px',
                fontStyle: 'bold',
                color: '#ffffff',
                stroke: '#000000',
                strokeThickness: 5,
            })
            .setOrigin(0.5, 0)
            .setVisible(false);
    }

    /**
     * Start at a random point just off a screen edge, heading for the centre.
     */
    spawn({ face, health, speed }) {
        const { x, y } = randomEdgePoint();

        this.enableBody(true, x, y, true, true);
        this.setTexture(Phaser.Utils.Array.GetRandom(SAUCERS));
        useCircleBody(this);
        this.health = health;
        this.scene.physics.moveTo(this, WIDTH / 2, HEIGHT / 2, speed);

        const hasFace = face && this.scene.textures.exists(faceKey(face));
        this.face.setTexture(hasFace ? faceKey(face) : '__DEFAULT').setDisplaySize(FACE_SIZE, FACE_SIZE).setVisible(hasFace);
        this.label.setText(face?.name ?? '').setVisible(true);
        this.follow();
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
        this.face.setVisible(false);
        this.label.setVisible(false);
    }

    update() {
        if (!this.active) {
            return;
        }

        this.follow();

        if (Phaser.Math.Distance.Between(this.x, this.y, WIDTH / 2, HEIGHT / 2) < BASE_RADIUS) {
            this.kill();
            this.scene.events.emit('enemy-reached-base', this);
        }
    }

    /**
     * Keep the face and name attached to the saucer.
     */
    follow() {
        this.face.setPosition(this.x, this.y).setDepth(this.depth + 1);
        this.label.setPosition(this.x, this.y + this.displayHeight / 2 + 2).setDepth(this.depth + 1);
    }
}

function randomEdgePoint() {
    const margin = 80;
    const edge = Phaser.Math.Between(0, 3);

    if (edge === 0) return { x: Phaser.Math.Between(0, WIDTH), y: -margin };
    if (edge === 1) return { x: WIDTH + margin, y: Phaser.Math.Between(0, HEIGHT) };
    if (edge === 2) return { x: Phaser.Math.Between(0, WIDTH), y: HEIGHT + margin };

    return { x: -margin, y: Phaser.Math.Between(0, HEIGHT) };
}
