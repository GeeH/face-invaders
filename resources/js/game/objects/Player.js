import Phaser from 'phaser';
import { PLAYER_SHIPS } from '../assets';
import { TURRET_TURN_SPEED } from '../config';
import { aimAt, nearest, shotInterval } from '../targeting';

// Only fire once the ship is roughly facing its target.
const AIM_TOLERANCE = 0.2;

/**
 * The player's ship: fixed in the centre, it turns toward the nearest enemy
 * and fires automatically. A random ship is picked each run for variety.
 */
export default class Player {
    constructor(scene, x, y, { fireRate, bullets }) {
        this.scene = scene;
        this.x = x;
        this.y = y;
        this.bullets = bullets;
        this.fireRate = fireRate;
        this.nextShotAt = 0;

        this.ship = scene.add.image(x, y, Phaser.Utils.Array.GetRandom(PLAYER_SHIPS)).setScale(1.2).setDepth(10);
        scene.registry.set('ship', this.ship.texture.key);
    }

    update(time, delta, enemies) {
        const target = nearest(this, enemies);

        if (!target) {
            return;
        }

        const wanted = aimAt(this, target);
        this.ship.rotation = Phaser.Math.Angle.RotateTo(this.ship.rotation, wanted, TURRET_TURN_SPEED * delta);

        const onTarget = Math.abs(Phaser.Math.Angle.Wrap(wanted - this.ship.rotation)) < AIM_TOLERANCE;

        if (onTarget && time >= this.nextShotAt) {
            this.fire();
            this.nextShotAt = time + shotInterval(this.fireRate);
        }
    }

    /**
     * Flash red when hit.
     */
    flash() {
        this.ship.setTint(0xff4444);
        this.scene.time.delayedCall(150, () => this.ship.clearTint());
    }

    fire() {
        const bullet = this.bullets.get();

        if (!bullet) {
            return; // Pool exhausted; skip this shot.
        }

        // Spawn at the ship's nose.
        const nose = new Phaser.Math.Vector2(0, -this.ship.displayHeight / 2).rotate(this.ship.rotation);
        bullet.fire(this.x + nose.x, this.y + nose.y, this.ship.rotation);
    }
}
