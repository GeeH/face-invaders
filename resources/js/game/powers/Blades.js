import { TUNING, orbit, within } from '../arsenal';
import { play } from '../sfx';

/**
 * Orbiting blades: throwing stars circling the ship that smash any rock
 * they touch. One per stack, spaced evenly.
 */
export default class Blades {
    constructor(scene, player, arsenal) {
        this.scene = scene;
        this.player = player;
        this.arsenal = arsenal;
        this.sprites = [];
        this.angle = 0;
    }

    update(time, delta, enemies) {
        while (this.sprites.length < this.arsenal.blades) {
            this.sprites.push(this.scene.add.image(this.player.x, this.player.y, 'neon-blade').setDepth(11));
        }

        this.angle += TUNING.bladeSpin * (delta / 1000);

        orbit(this.player, this.sprites.length, TUNING.bladeOrbit, this.angle).forEach((spot, i) => {
            const blade = this.sprites[i].setPosition(spot.x, spot.y).setRotation(spot.angle * 3);

            within(blade, enemies, TUNING.bladeRadius).forEach((enemy) => {
                if ((enemy.bladeReadyAt ?? 0) <= time) {
                    enemy.bladeReadyAt = time + TUNING.bladeHitCooldownMs;
                    play('blade');
                    this.scene.hitEnemy(enemy, this.scene.balance.bullet_damage);
                }
            });
        });
    }

    hide() {
        this.sprites.forEach((sprite) => sprite.setVisible(false));
    }
}
