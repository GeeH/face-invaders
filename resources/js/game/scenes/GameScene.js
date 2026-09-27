import Phaser from 'phaser';
import { HEIGHT, WIDTH } from '../config';
import { FaceQueue } from '../faces';
import Bullet from '../objects/Bullet';
import Enemy from '../objects/Enemy';
import Player from '../objects/Player';
import { Waves } from '../waves';

// Pause between waves until the chat vote replaces it (#17, #18).
const BREAK_BETWEEN_WAVES_MS = 3000;

export default class GameScene extends Phaser.Scene {
    constructor() {
        super('game');
    }

    create() {
        const { balance, faces } = this.registry.get('run');
        this.faces = new FaceQueue(faces);

        this.bullets = this.physics.add.group({ classType: Bullet, maxSize: 60, runChildUpdate: true });
        this.enemies = this.physics.add.group({ classType: Enemy, maxSize: 100, runChildUpdate: true });

        this.player = new Player(this, WIDTH / 2, HEIGHT / 2, {
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

        this.waves = new Waves(balance, {
            spawn: ({ health, speed }) => {
                const enemy = this.enemies.get();
                enemy?.spawn({ face: this.faces.next(), health, speed });

                return Boolean(enemy);
            },
            onStart: (wave) => {
                // The HUD (#16) reads the wave number from the registry.
                this.registry.set('wave', wave);
                this.banner(`Wave ${wave}`);
            },
            onCleared: (wave) => {
                this.banner(`Wave ${wave} cleared!`);
                this.time.delayedCall(BREAK_BETWEEN_WAVES_MS, () => this.waves.next());
            },
        });

        this.waves.start();
    }

    update(time, delta) {
        const enemies = this.enemies.getMatching('active', true);

        this.player.update(time, delta, enemies);
        this.waves.update(delta, enemies.length);
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

    /**
     * A big message in the middle of the screen that fades away.
     */
    banner(message) {
        const text = this.add
            .text(WIDTH / 2, HEIGHT / 2 - 200, message, {
                fontFamily: 'system-ui, sans-serif',
                fontSize: '80px',
                fontStyle: 'bold',
                color: '#ffffff',
                stroke: '#000000',
                strokeThickness: 10,
            })
            .setOrigin(0.5)
            .setDepth(30)
            .setAlpha(0);

        this.tweens.chain({
            targets: text,
            tweens: [
                { alpha: 1, scale: { from: 0.8, to: 1 }, duration: 250, ease: 'Back.out' },
                { alpha: 0, delay: 1200, duration: 400 },
            ],
            onComplete: () => text.destroy(),
        });
    }
}
