import Phaser from 'phaser';
import { HEIGHT, WIDTH } from '../config';
import Bullet from '../objects/Bullet';
import Enemy from '../objects/Enemy';
import Turret from '../objects/Turret';

// Temporary steady trickle of enemies until waves arrive (#15).
const SPAWN_EVERY_MS = 900;

export default class GameScene extends Phaser.Scene {
    constructor() {
        super('game');
    }

    create() {
        const { balance } = this.registry.get('run');

        this.bullets = this.physics.add.group({ classType: Bullet, maxSize: 60, runChildUpdate: true });
        this.enemies = this.physics.add.group({ classType: Enemy, maxSize: 100, runChildUpdate: true });

        this.turret = new Turret(this, WIDTH / 2, HEIGHT / 2, {
            fireRate: balance.fire_rate,
            bullets: this.bullets,
        });

        this.physics.add.overlap(this.bullets, this.enemies, (bullet, enemy) => {
            if (!bullet.active || !enemy.active) {
                return;
            }

            bullet.kill();

            if (enemy.hit(balance.bullet_damage)) {
                this.explode(enemy.x, enemy.y);
            }
        });

        this.time.addEvent({
            delay: SPAWN_EVERY_MS,
            loop: true,
            callback: () => this.enemies.get()?.spawn({ health: balance.enemy_health, speed: balance.enemy_speed }),
        });
    }

    update(time, delta) {
        this.turret.update(time, delta, this.enemies.getMatching('active', true));
    }

    explode(x, y) {
        const flash = this.add.image(x, y, 'hit').setDepth(20).setScale(0.6);

        this.tweens.add({
            targets: flash,
            scale: 2,
            alpha: 0,
            angle: 90,
            duration: 250,
            onComplete: () => flash.destroy(),
        });
    }
}
