import Phaser from 'phaser';
import { aimAt, nearest, shotInterval, turnRate } from '../targeting';

// Only fire once the ship is roughly facing its target.
const AIM_TOLERANCE = 0.2;

// Where the nose and exhaust are on the ship texture, from its centre.
const NOSE = 52;
const EXHAUST = 32;

/**
 * The player's neon ship: fixed in the centre, it turns toward the nearest
 * enemy and fires automatically.
 */
export default class Player {
    constructor(scene, x, y, { fireRate, turnSpeed, bullets }) {
        this.scene = scene;
        this.x = x;
        this.y = y;
        this.bullets = bullets;
        this.fireRate = fireRate;
        // Degrees per second; upgrades raise it.
        this.turnSpeed = turnSpeed;
        this.nextShotAt = 0;

        this.flame = scene.add.image(x, y, 'neon-flame').setOrigin(0.5, 0.2).setDepth(9);
        this.ship = scene.add.image(x, y, 'neon-ship').setDepth(10);
    }

    update(time, delta, enemies) {
        this.thrust();

        const target = nearest(this, enemies);

        if (!target) {
            return;
        }

        const wanted = aimAt(this, target);
        this.ship.rotation = Phaser.Math.Angle.RotateTo(this.ship.rotation, wanted, turnRate(this.turnSpeed) * delta);

        const onTarget = Math.abs(Phaser.Math.Angle.Wrap(wanted - this.ship.rotation)) < AIM_TOLERANCE;

        if (onTarget && time >= this.nextShotAt) {
            this.fire();
            this.nextShotAt = time + shotInterval(this.fireRate);
        }
    }

    /**
     * The exhaust flickers behind the ship, whichever way it faces.
     */
    thrust() {
        const exhaust = new Phaser.Math.Vector2(0, EXHAUST).rotate(this.ship.rotation);

        this.flame
            .setPosition(this.x + exhaust.x, this.y + exhaust.y)
            .setRotation(this.ship.rotation)
            .setScale(1, Phaser.Math.FloatBetween(0.5, 1.1))
            .setVisible(this.ship.visible);
    }

    /**
     * Flash red when hit.
     */
    flash() {
        this.ship.setTint(0xff3860);
        this.scene.time.delayedCall(150, () => this.ship.clearTint());
    }

    fire() {
        const bullet = this.bullets.get();

        if (!bullet) {
            return; // Pool exhausted; skip this shot.
        }

        // Spawn at the ship's nose.
        const nose = new Phaser.Math.Vector2(0, -NOSE).rotate(this.ship.rotation);
        bullet.fire(this.x + nose.x, this.y + nose.y, this.ship.rotation);
    }
}
