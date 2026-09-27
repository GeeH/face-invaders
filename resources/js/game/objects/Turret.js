import Phaser from 'phaser';
import { TURRET_TURN_SPEED } from '../config';
import { aimAt, nearest, shotInterval } from '../targeting';

// Only fire once the barrel is roughly on target.
const AIM_TOLERANCE = 0.2;

/**
 * The fixed central base with a gun that turns toward the nearest enemy and
 * fires automatically.
 */
export default class Turret {
    constructor(scene, x, y, { fireRate, bullets }) {
        this.scene = scene;
        this.x = x;
        this.y = y;
        this.bullets = bullets;
        this.fireRate = fireRate;
        this.nextShotAt = 0;

        this.base = scene.add.image(x, y, 'base').setScale(1.6).setDepth(10);
        // The barrel pivots near its bottom so it swivels around the base's centre.
        this.gun = scene.add.image(x, y, 'turret').setOrigin(0.5, 0.8).setScale(1.8).setDepth(11);
    }

    update(time, delta, enemies) {
        const target = nearest(this, enemies);

        if (!target) {
            return;
        }

        const wanted = aimAt(this, target);
        this.gun.rotation = Phaser.Math.Angle.RotateTo(this.gun.rotation, wanted, TURRET_TURN_SPEED * delta);

        const onTarget = Math.abs(Phaser.Math.Angle.Wrap(wanted - this.gun.rotation)) < AIM_TOLERANCE;

        if (onTarget && time >= this.nextShotAt) {
            this.fire();
            this.nextShotAt = time + shotInterval(this.fireRate);
        }
    }

    fire() {
        const bullet = this.bullets.get();

        if (!bullet) {
            return; // Pool exhausted; skip this shot.
        }

        // Spawn at the barrel tip.
        const tip = new Phaser.Math.Vector2(0, -this.gun.displayHeight * 0.8).rotate(this.gun.rotation);
        bullet.fire(this.x + tip.x, this.y + tip.y, this.gun.rotation);
    }
}
