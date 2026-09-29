import Phaser from 'phaser';
import { TUNING, orbit } from '../arsenal';
import { aimAt, nearest } from '../targeting';

/**
 * Drone wingmen: little ships circling the player that each pick the
 * nearest rock and shoot at it with the player's bolt upgrades.
 */
export default class Drones {
    constructor(scene, player, arsenal, bullets) {
        this.scene = scene;
        this.player = player;
        this.arsenal = arsenal;
        this.bullets = bullets;
        this.sprites = [];
        this.angle = 0;
    }

    update(time, delta, enemies) {
        while (this.sprites.length < this.arsenal.drones) {
            // Stagger new drones' first shots so they don't all fire at once.
            const drone = this.scene.add.image(this.player.x, this.player.y, 'neon-drone').setDepth(11);
            drone.nextShotAt = time + Phaser.Math.Between(0, TUNING.droneShotMs);
            this.sprites.push(drone);
        }

        this.angle -= TUNING.droneSpin * (delta / 1000);

        orbit(this.player, this.sprites.length, TUNING.droneOrbit, this.angle).forEach((spot, i) => {
            const drone = this.sprites[i].setPosition(spot.x, spot.y);
            const target = nearest(drone, enemies);

            if (!target) {
                return;
            }

            drone.setRotation(aimAt(drone, target));

            if (time >= drone.nextShotAt) {
                drone.nextShotAt = time + TUNING.droneShotMs;
                this.bullets.get()?.fire(drone.x, drone.y, drone.rotation, { pierce: this.arsenal.pierce, bounces: this.arsenal.bounces });
            }
        });
    }

    hide() {
        this.sprites.forEach((sprite) => sprite.setVisible(false));
    }
}
